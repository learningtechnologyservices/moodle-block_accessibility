// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Accessibility block behaviour.
 *
 * Everything runs in the browser. A single cookie ("bfa") holds the reader's
 * text-scale and colour-theme choice so the setting follows them as they move
 * around Moodle. Read-aloud uses the browser's own Web Speech API; no external
 * library is loaded.
 *
 * The cookie value is "scale|scheme|voice", e.g. "1.3|4|" — the third field is a
 * URL-encoded stable id of the chosen read-aloud voice ("" means the browser default).
 * A tiny inline script in the page head (added by the block's hook) applies the scale
 * and scheme before the page paints, so there is no flash of default styling on
 * navigation.
 *
 * @module     block_accessibility/main
 * @copyright  2026 Brickfield Education Labs <https://www.brickfield.ie/>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const COOKIE_MAX_AGE = 60 * 60 * 24 * 365; // One year, in seconds.

let cfg = {
    cookieName: 'bfa',
    minScale: 0.8,
    maxScale: 2.0,
    step: 0.1,
    defaultScale: 1.0,
    pageLang: 'en',
    strings: {},
};

let state = {scale: 1.0, scheme: 1};
let currentRate = 1.0;

// The chosen read-aloud voice as a stable id ('' = the browser's default voice).
let selectedVoiceId = '';
// Bumped whenever speech starts or is stopped, so a cancelled chunk-chain stops advancing
// instead of starting the next chunk (cancel() fires onerror, which would otherwise chain on).
let speechToken = 0;
// Guards the one-time speechSynthesis 'voiceschanged' listener.
let voicesListenerBound = false;

// Tracks whether we set Bootstrap's dark mode for High contrast, so we only ever
// remove the attribute we added and never clobber a site's own colour mode.
let darkThemeSet = false;

/**
 * Read the preferences cookie.
 *
 * @returns {{scale: number, scheme: number, voiceId: string}}
 */
const readCookie = () => {
    const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + cfg.cookieName + '=([^;]*)'));
    const result = {scale: cfg.defaultScale, scheme: 1, voiceId: ''};
    if (!match) {
        return result;
    }
    // A malformed cookie must never throw and break initialisation (which would take the
    // read-aloud controls down with it).
    try {
        const parts = decodeURIComponent(match[1]).split('|');
        const scale = parseFloat(parts[0]);
        const scheme = parseInt(parts[1], 10);
        if (!isNaN(scale)) {
            result.scale = scale;
        }
        if (!isNaN(scheme)) {
            result.scheme = scheme;
        }
        if (parts.length > 2 && parts[2]) {
            result.voiceId = decodeURIComponent(parts[2]);
        }
    } catch (e) {
        return {scale: cfg.defaultScale, scheme: 1, voiceId: ''};
    }
    return result;
};

/**
 * Persist the current preferences to the cookie. The voice id is encoded on its own so
 * it can never contain the "|" field separator; the pre-paint head script reads only the
 * first two fields, so appending a third is backward compatible.
 */
const writeCookie = () => {
    const raw = state.scale.toFixed(2) + '|' + state.scheme + '|' + encodeURIComponent(selectedVoiceId);
    const value = encodeURIComponent(raw);
    const secure = window.location.protocol === 'https:' ? ';Secure' : '';
    document.cookie = cfg.cookieName + '=' + value + ';path=/;max-age=' + COOKIE_MAX_AGE + ';SameSite=Lax' + secure;
};

/**
 * Apply the current scale and scheme to the document root.
 */
const applyToDocument = () => {
    const el = document.documentElement;

    // Text scale.
    if (Math.abs(state.scale - 1.0) > 0.001) {
        el.style.setProperty('--bfa-scale', state.scale);
        el.classList.add('bfa-font-active');
    } else {
        el.style.removeProperty('--bfa-scale');
        el.classList.remove('bfa-font-active');
    }

    // Colour theme. Scheme 1 is the default (no class).
    for (let i = 2; i <= 5; i++) {
        el.classList.toggle('bfa-scheme-' + i, state.scheme === i);
    }

    // High contrast (scheme 4) and Dark (scheme 5) both flip Bootstrap's own dark
    // mode, so Bootstrap components recolour beyond what the CSS variable overrides
    // alone can reach.
    if (state.scheme === 4 || state.scheme === 5) {
        el.setAttribute('data-bs-theme', 'dark');
        darkThemeSet = true;
    } else if (darkThemeSet) {
        el.removeAttribute('data-bs-theme');
        darkThemeSet = false;
    }
};

/**
 * Round to one decimal place to avoid floating-point drift.
 *
 * @param {number} value
 * @returns {number}
 */
const round1 = (value) => Math.round(value * 10) / 10;

/**
 * Update the disabled state of the size buttons and the pressed state of theme buttons.
 */
const refreshControls = () => {
    const dec = document.getElementById('bfa-dec');
    const inc = document.getElementById('bfa-inc');
    const reset = document.getElementById('bfa-reset');
    if (dec) {
        dec.disabled = state.scale <= cfg.minScale + 0.001;
    }
    if (inc) {
        inc.disabled = state.scale >= cfg.maxScale - 0.001;
    }
    if (reset) {
        reset.disabled = Math.abs(state.scale - cfg.defaultScale) < 0.001;
    }
    document.querySelectorAll('.bfa-theme').forEach((btn) => {
        const scheme = parseInt(btn.getAttribute('data-scheme'), 10);
        btn.setAttribute('aria-pressed', scheme === state.scheme ? 'true' : 'false');
    });
};

/**
 * Announce a message in the block's live region.
 *
 * @param {string} message
 */
const announce = (message) => {
    const status = document.getElementById('bfa-status');
    if (status) {
        status.textContent = message;
    }
};

/**
 * Change the text scale.
 *
 * @param {string} op One of "inc", "dec" or "reset".
 */
const changeSize = (op) => {
    if (op === 'inc') {
        state.scale = round1(Math.min(state.scale + cfg.step, cfg.maxScale));
    } else if (op === 'dec') {
        state.scale = round1(Math.max(state.scale - cfg.step, cfg.minScale));
    } else {
        state.scale = cfg.defaultScale;
    }
    applyToDocument();
    writeCookie();
    refreshControls();
    announce(op === 'reset' ? cfg.strings.sizereset
        : cfg.strings.sizeset.replace('{$a}', Math.round(state.scale * 100)));
};

/**
 * Change the colour theme.
 *
 * @param {number} scheme A value from 1 (default) to 4 (high contrast).
 * @param {string} label The visible button label, for the announcement.
 */
const changeScheme = (scheme, label) => {
    state.scheme = scheme;
    applyToDocument();
    writeCookie();
    refreshControls();
    announce(cfg.strings.themeset.replace('{$a}', label));
};

// --- Read-aloud, using the browser's Web Speech API. ---

/**
 * Whether the browser exposes speech synthesis.
 *
 * @returns {boolean}
 */
const speechSupported = () => 'speechSynthesis' in window && typeof SpeechSynthesisUtterance !== 'undefined';

/**
 * Split long text into speakable chunks. Some browsers stop long utterances
 * early, so we queue several shorter ones.
 *
 * @param {string} text
 * @returns {string[]}
 */
const chunkText = (text) => {
    const sentences = text.replace(/\s+/g, ' ').trim().match(/[^.!?]+[.!?]*/g) || [text];
    const chunks = [];
    let buffer = '';
    sentences.forEach((sentence) => {
        if ((buffer + sentence).length > 200) {
            if (buffer) {
                chunks.push(buffer.trim());
            }
            buffer = sentence;
        } else {
            buffer += sentence;
        }
    });
    if (buffer.trim()) {
        chunks.push(buffer.trim());
    }
    return chunks;
};

/**
 * A stable identifier for a voice, resilient to getVoices() reordering. Newline joins
 * fields that never contain a newline, so the id round-trips through the cookie safely.
 *
 * @param {SpeechSynthesisVoice} voice
 * @returns {string}
 */
const voiceKey = (voice) => [voice.voiceURI, voice.name, voice.lang].join('\n');

/**
 * Find the saved voice in the current voice list: an exact match on the stable id, then
 * a fallback on name + language. Returns null (meaning the browser default) if none match.
 *
 * @param {string} id
 * @param {SpeechSynthesisVoice[]} voices
 * @returns {?SpeechSynthesisVoice}
 */
const findVoice = (id, voices) => {
    if (!id) {
        return null;
    }
    let voice = voices.find((v) => voiceKey(v) === id);
    if (!voice) {
        const parts = id.split('\n');
        if (parts.length >= 3) {
            voice = voices.find((v) => v.name === parts[1] && v.lang === parts[2]);
        }
    }
    return voice || null;
};

/**
 * Choose the best available voice for a language: a voice whose base language matches, with
 * the exact locale and on-device voices preferred. Returns null if the browser exposes no
 * voice for that language.
 *
 * This is used for "System default" reading. Some browsers (notably Firefox) do not pick a
 * voice by the utterance's language on their own — they read with their single default voice
 * whatever `lang` is set — so we choose a matching voice explicitly to read the content in
 * its own language.
 *
 * @param {string} lang
 * @param {SpeechSynthesisVoice[]} voices
 * @returns {?SpeechSynthesisVoice}
 */
const pickVoiceForLang = (lang, voices) => {
    const base = (lang || '').toLowerCase().split('-')[0];
    if (!base) {
        return null;
    }
    const matches = voices.filter((v) => v && v.name && (v.lang || '').toLowerCase().split('-')[0] === base);
    if (!matches.length) {
        return null;
    }
    const target = lang.toLowerCase();
    // Exact locale first, then on-device, then by name.
    matches.sort((a, b) => ((a.lang || '').toLowerCase() === target ? 0 : 1) - ((b.lang || '').toLowerCase() === target ? 0 : 1)
        || ((a.localService ? 0 : 1) - (b.localService ? 0 : 1))
        || (a.name || '').localeCompare(b.name || ''));
    return matches[0];
};

/**
 * (Re)build the voice dropdown from the browser's on-device voices only. Online voices are
 * left out: they stream from a server and can fail or fall silent, whereas on-device voices
 * need no network and always sound. Only voices in the page's language are shown (page locale
 * first); a wrong-language voice is never offered, since it would read the page as nonsense.
 * If no on-device voice matches the page language, only the System default is offered — it
 * still reads in the page language using whatever voice the browser picks. Rebuilding clears
 * the previously added options so repeated "voiceschanged" events never create duplicates. A
 * saved online voice (no longer offered) is reset to the system default; a saved on-device
 * voice in another language is kept as the reader's stored preference.
 */
const populateVoices = () => {
    const select = document.getElementById('bfa-voice');
    if (!select || !speechSupported()) {
        return;
    }
    // A quirk in the browser's voice list (a nameless voice, an odd sort) must never throw
    // and take the read-aloud controls down with it.
    try {
        const voices = window.speechSynthesis.getVoices();
        // The page's own content language drives the list: the `<html lang>` (fr on a French
        // course) first, then Moodle's UI language, then the browser's. Using the content
        // language — not the UI language — is what makes a French page offer French voices.
        const locale = (document.documentElement.lang || cfg.pageLang || navigator.language || 'en').toLowerCase();
        const base = locale.split('-')[0];

        // On-device voices only, named and de-duplicated (some browsers list the same voice
        // more than once). Online voices are excluded as unreliable.
        const seen = {};
        const local = voices.filter((voice) => {
            if (!voice || !voice.name || !voice.localService) {
                return false;
            }
            const key = voiceKey(voice);
            if (seen[key]) {
                return false;
            }
            seen[key] = true;
            return true;
        });

        // Only voices in the page's language — never offer a wrong-language voice, which
        // would read the page as nonsense. If no on-device voice is installed for the page
        // language, the list is empty and only the System default remains.
        const list = local.filter((voice) => (voice.lang || '').toLowerCase().split('-')[0] === base);

        // Exact page locale first, then by name.
        const rank = (voice) => ((voice.lang || '').toLowerCase() === locale ? 0 : 1);
        list.sort((a, b) => rank(a) - rank(b) || (a.name || '').localeCompare(b.name || ''));

        // Reset the saved choice only when it is genuinely unavailable as an on-device voice
        // (e.g. a previously chosen online voice). Do NOT reset just because it is in another
        // language than the current page — the reader keeps their preference as they move
        // between pages. Guard on a loaded list so we never clear on the empty initial call
        // before voices arrive.
        const saved = findVoice(selectedVoiceId, voices);
        const savedOnDevice = !!(saved && saved.localService);
        if (selectedVoiceId && voices.length && !savedOnDevice) {
            selectedVoiceId = '';
            writeCookie();
        }

        // Remove previously added options, keeping the first (System default).
        while (select.options.length > 1) {
            select.remove(1);
        }
        list.forEach((voice) => {
            const option = document.createElement('option');
            option.value = voiceKey(voice);
            option.textContent = voice.name + ' — ' + (voice.lang || '');
            select.appendChild(option);
        });

        // Show the saved voice selected only when it appears in this page-language list;
        // otherwise show System default for this page without discarding the preference.
        select.value = (savedOnDevice && list.indexOf(saved) !== -1) ? voiceKey(saved) : '';

        // If the browser has no voice at all for the page language (on-device OR online), the
        // menu can offer nothing useful. Hide it and its label, and show a help affordance
        // that explains how to add a voice. Guard on a loaded voice list so we never flag
        // "no voice" before the voices have arrived.
        const help = document.getElementById('bfa-voicehelp-open');
        const voicelabel = document.getElementById('bfa-voice-label');
        const noVoiceForLang = voices.length > 0 && !pickVoiceForLang(locale, voices);
        if (help) {
            help.hidden = !noVoiceForLang;
        }
        select.hidden = noVoiceForLang;
        if (voicelabel) {
            voicelabel.hidden = noVoiceForLang;
        }
        if (noVoiceForLang) {
            const intro = document.getElementById('bfa-voicehelp-intro');
            if (intro && cfg.strings.voicehelpintro) {
                intro.textContent = cfg.strings.voicehelpintro.replace(/\{\$a\}/g, langDisplayName(locale));
            }
        }
    } catch (e) {
        return;
    }
};

// How long to wait for a chosen voice to start producing audio before assuming it has
// failed silently and falling back to the default voice. Online voices fetch over the
// network, so this allows for a slow start while still recovering from a dead voice.
const VOICE_START_TIMEOUT = 4000;

/**
 * Speak a list of chunks one at a time with the given voice, advancing when each finishes.
 *
 * Queueing several utterances at once is unreliable in some browsers — especially with a
 * chosen (often online) voice, where the queue can silently stall — so we speak one at a
 * time and only advance while this read is still current (stopping, or a new read, bumps
 * speechToken, so the onerror that cancel() fires cannot chain into the next chunk).
 *
 * If a chosen voice never produces audio — a genuine error before it starts, or nothing at
 * all within VOICE_START_TIMEOUT — we fall back once to the browser's default voice and
 * re-read from the top, so the reader is never left in silence.
 *
 * @param {string[]} chunks
 * @param {?SpeechSynthesisVoice} voice The chosen voice, or null for the browser default.
 * @param {number} myToken The speechToken value that identifies this read.
 * @param {boolean} allowFallback Whether a silent/failed voice may fall back to the default.
 * @param {string} contentLangCode The content's language, used when no voice is chosen.
 */
const speakChunks = (chunks, voice, myToken, allowFallback, contentLangCode) => {
    // A chosen voice carries its own language; otherwise speak in the content's language so
    // the browser's default voice reads it correctly (French content in a French voice, etc.).
    const lang = (voice && voice.lang) ? voice.lang : contentLangCode;
    let index = 0;
    let started = false;
    let watchdog = null;

    const clearWatchdog = () => {
        if (watchdog !== null) {
            clearTimeout(watchdog);
            watchdog = null;
        }
    };

    // Re-read from the top with the browser's default voice. Only ever fires once, only
    // while this read is still current, and only before the chosen voice produced any audio.
    const fallback = () => {
        clearWatchdog();
        if (myToken !== speechToken || started || !allowFallback) {
            return;
        }
        allowFallback = false;
        window.speechSynthesis.cancel();
        announce(cfg.strings.voicefallback);
        speakChunks(chunks, null, myToken, false, contentLangCode);
    };

    const speakNext = () => {
        if (myToken !== speechToken || index >= chunks.length) {
            return;
        }
        const utterance = new SpeechSynthesisUtterance(chunks[index]);
        utterance.lang = lang;
        utterance.rate = currentRate;
        if (voice) {
            utterance.voice = voice;
        }
        utterance.onstart = () => {
            started = true;
            clearWatchdog();
        };
        utterance.onend = () => {
            started = true;
            clearWatchdog();
            index += 1;
            speakNext();
        };
        utterance.onerror = (event) => {
            clearWatchdog();
            // Stop, or a new read, cancels the current utterance — that is not a voice failure.
            const err = event && event.error;
            if (err === 'canceled' || err === 'interrupted') {
                return;
            }
            // A genuine failure before any audio played: try the default voice instead.
            if (!started && allowFallback) {
                fallback();
                return;
            }
            // Otherwise skip the bad chunk and carry on.
            index += 1;
            speakNext();
        };
        window.speechSynthesis.speak(utterance);
        // Watchdog on the first chunk of a chosen voice only: some online voices produce no
        // audio and no error at all. If nothing has started in time, fall back.
        if (index === 0 && allowFallback) {
            watchdog = setTimeout(() => {
                if (!started) {
                    fallback();
                }
            }, VOICE_START_TIMEOUT);
        }
    };
    speakNext();
};

/**
 * The best language for some content: the nearest ancestor `lang` attribute, then the
 * document's `<html lang>`, then Moodle's UI language, then the browser's, then 'en'.
 *
 * Moodle sets `lang` authoritatively — on `<html>` from the page/course language, and per
 * span for multilingual content — so the attribute beats any content-sniffing guess and,
 * unlike the browser's language-detection APIs, works in every browser.
 *
 * @param {?Node} node A node within the content being read (e.g. the selection's anchor).
 * @returns {string}
 */
const contentLang = (node) => {
    let el = (node && node.nodeType === 3) ? node.parentElement : node;
    while (el && el.nodeType === 1) {
        const lang = el.getAttribute ? el.getAttribute('lang') : '';
        if (lang) {
            return lang;
        }
        el = el.parentElement;
    }
    return document.documentElement.lang || cfg.pageLang || navigator.language || 'en';
};

/**
 * Speak the supplied text.
 *
 * @param {string} text
 * @param {string} [lang] The content's language (see contentLang); defaults to the page language.
 */
const speak = (text, lang) => {
    if (!speechSupported()) {
        announce(cfg.strings.notsupported);
        return;
    }
    if (!text || !text.trim()) {
        announce(cfg.strings.nothingselected);
        return;
    }
    window.speechSynthesis.cancel();
    // Starting a new read invalidates any chunk-chain still in flight.
    const myToken = ++speechToken;
    const langCode = lang || contentLang(null);
    let voice = null;
    try {
        voice = findVoice(selectedVoiceId, window.speechSynthesis.getVoices());
    } catch (e) {
        voice = null;
    }
    // Never read with a voice whose language does not match the content — an English voice
    // reading French sounds like nonsense.
    if (voice) {
        const voicebase = (voice.lang || '').toLowerCase().split('-')[0];
        const contentbase = langCode.toLowerCase().split('-')[0];
        if (voicebase && contentbase && voicebase !== contentbase) {
            voice = null;
        }
    }
    // For "System default" (or a discarded mismatched voice), pick a voice for the content
    // language explicitly, so browsers that do not auto-match by language (e.g. Firefox) still
    // read in the right language. If the browser has no voice for that language, this is null
    // and it reads with the browser default (nothing more can be done without an installed voice).
    if (!voice) {
        try {
            voice = pickVoiceForLang(langCode, window.speechSynthesis.getVoices());
        } catch (e) {
            voice = null;
        }
    }
    // Only a chosen voice can fall back; the default voice has nowhere to fall back to.
    speakChunks(chunkText(text), voice, myToken, !!voice, langCode);
    announce(cfg.strings.reading);
};

/**
 * Stop any current speech.
 */
const stopSpeaking = () => {
    // Invalidate the in-flight chunk-chain first, so the onerror that cancel() fires cannot
    // start the next chunk.
    speechToken++;
    if (speechSupported()) {
        window.speechSynthesis.cancel();
    }
    announce(cfg.strings.stopped);
};

/**
 * Read the reader's current text selection.
 */
const readSelection = () => {
    const selection = window.getSelection ? window.getSelection() : null;
    const text = selection ? selection.toString() : '';
    // Read in the language of the selected content (nearest `lang` to where it lives).
    const node = selection && selection.anchorNode ? selection.anchorNode : null;
    speak(text, contentLang(node));
};

/**
 * Read the main content region of the page.
 */
const readPage = () => {
    const selectors = ['#region-main', '[role="main"]', 'main', '#page-content', 'body'];
    let container = null;
    for (let i = 0; i < selectors.length && !container; i++) {
        container = document.querySelector(selectors[i]);
    }
    speak(container ? container.innerText : '', contentLang(container));
};

/**
 * Open a dialog by id, falling back gracefully if <dialog> is unsupported.
 *
 * @param {string} id
 */
const openDialog = (id) => {
    const dialog = document.getElementById(id);
    if (!dialog) {
        return;
    }
    if (typeof dialog.showModal === 'function') {
        dialog.showModal();
    } else {
        dialog.setAttribute('open', 'open');
    }
};

/**
 * Close a dialog by id.
 *
 * @param {string} id
 */
const closeDialog = (id) => {
    const dialog = document.getElementById(id);
    if (!dialog) {
        return;
    }
    if (typeof dialog.close === 'function') {
        dialog.close();
    } else {
        dialog.removeAttribute('open');
    }
};

/**
 * A human-readable name for a language code (e.g. "fr" → "French"), in the viewer's own
 * language where the browser supports it. Falls back to the base code.
 *
 * @param {string} code
 * @returns {string}
 */
const langDisplayName = (code) => {
    const base = (code || '').split('-')[0];
    try {
        return new Intl.DisplayNames(undefined, {type: 'language'}).of(base) || base;
    } catch (e) {
        return base;
    }
};

/**
 * Wire up all the controls.
 */
const bindEvents = () => {
    const on = (id, handler) => {
        const node = document.getElementById(id);
        if (node) {
            node.addEventListener('click', handler);
        }
    };

    on('bfa-dec', () => changeSize('dec'));
    on('bfa-inc', () => changeSize('inc'));
    on('bfa-reset', () => changeSize('reset'));

    document.querySelectorAll('.bfa-theme').forEach((btn) => {
        btn.addEventListener('click', () => {
            changeScheme(parseInt(btn.getAttribute('data-scheme'), 10), btn.getAttribute('data-label'));
        });
    });

    on('bfa-read-selection', readSelection);
    on('bfa-read-page', readPage);
    on('bfa-read-stop', stopSpeaking);

    const rate = document.getElementById('bfa-rate');
    const rateValue = document.getElementById('bfa-rate-value');
    if (rate) {
        rate.addEventListener('input', () => {
            currentRate = parseFloat(rate.value);
            if (rateValue) {
                rateValue.textContent = currentRate.toFixed(1) + '\u00d7';
            }
        });
    }

    const voiceSelect = document.getElementById('bfa-voice');
    if (voiceSelect) {
        voiceSelect.addEventListener('change', () => {
            selectedVoiceId = voiceSelect.value;
            writeCookie();
        });
    }

    // Voices can load asynchronously; populate now and again when the list changes.
    if (speechSupported()) {
        populateVoices();
        if (!voicesListenerBound) {
            window.speechSynthesis.addEventListener('voiceschanged', populateVoices);
            voicesListenerBound = true;
        }
    }

    on('bfa-about-open', () => openDialog('bfa-about'));
    on('bfa-about-close', () => closeDialog('bfa-about'));
    on('bfa-voicehelp-open', () => openDialog('bfa-voicehelp'));
    on('bfa-voicehelp-close', () => closeDialog('bfa-voicehelp'));

    // Hide the read-aloud controls entirely when the browser has no speech engine.
    if (!speechSupported()) {
        const group = document.getElementById('bfa-read-group');
        if (group) {
            group.hidden = true;
        }
    }
};

/**
 * Entry point, called by Moodle once per block instance.
 *
 * @param {object} config Settings and localised strings passed from PHP.
 */
export const init = (config) => {
    cfg = Object.assign(cfg, config || {});
    state = readCookie();
    selectedVoiceId = state.voiceId || '';
    applyToDocument();
    refreshControls();
    bindEvents();
};
