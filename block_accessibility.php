<?php
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
 * The Accessibility block class.
 *
 * Renders the text-size, colour-theme and read-aloud controls. All behaviour
 * lives in the browser (see amd/src/main.js); this class only builds accessible
 * markup and hands a small configuration object to the JavaScript module.
 *
 * @package   block_accessibility
 * @copyright 2026 Brickfield Education Labs <https://www.brickfield.ie/>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Accessibility block.
 */
class block_accessibility extends block_base {
    /**
     * Set the block title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_accessibility');
    }

    /**
     * Only one instance of the block is needed per page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Allow the block everywhere.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => true];
    }

    /**
     * Build the block content.
     *
     * @return stdClass|null
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = $this->render_tools();

        // Hand the reusable strings and scale bounds to the browser module.
        $config = [
            'cookieName'   => 'bfa',
            'minScale'     => 0.8,
            'maxScale'     => 2.0,
            'step'         => 0.1,
            'defaultScale' => 1.0,
            'pageLang'     => current_language(),
            'strings'      => [
                'reading'         => get_string('status_reading', 'block_accessibility'),
                'stopped'         => get_string('status_stopped', 'block_accessibility'),
                'nothingselected' => get_string('status_nothingselected', 'block_accessibility'),
                'notsupported'    => get_string('status_notsupported', 'block_accessibility'),
                'sizeset'         => get_string('status_sizeset', 'block_accessibility'),
                'sizereset'       => get_string('status_sizereset', 'block_accessibility'),
                'themeset'        => get_string('status_themeset', 'block_accessibility'),
                'voicefallback'   => get_string('status_voicefallback', 'block_accessibility'),
                'voicehelpintro'  => get_string('voice_help_intro', 'block_accessibility'),
            ],
        ];

        $this->page->requires->js_call_amd('block_accessibility/main', 'init', [$config]);

        return $this->content;
    }

    /**
     * Assemble the accessible control markup.
     *
     * @return string
     */
    protected function render_tools(): string {
        global $OUTPUT;

        // Pre-fetch the visible strings so the template below stays readable.
        $s = function (string $key): string {
            return get_string($key, 'block_accessibility');
        };

        $sizelabel   = $s('sizelabel');
        $decrease    = $s('decrease');
        $reset       = $s('reset');
        $increase    = $s('increase');

        $themelabel  = $s('themelabel');
        $themedefault = $s('theme_default');
        $themecream  = $s('theme_cream');
        $themeblue   = $s('theme_blue');
        $themehigh   = $s('theme_highcontrast');
        $themedefaultlabel = $s('theme_default_label');
        $themecreamlabel   = $s('theme_cream_label');
        $themebluelabel    = $s('theme_blue_label');
        $themehighlabel    = $s('theme_highcontrast_label');
        $themedark         = $s('theme_dark');
        $themedarklabel    = $s('theme_dark_label');

        $readlabel   = $s('readlabel');
        $readselection = $s('read_selection');
        $readpage    = $s('read_page');
        $readselectionshort = $s('read_selection_short');
        $readpageshort = $s('read_page_short');
        $stop        = $s('read_stop');
        $ratelabel   = $s('rate_label');
        $voicelabel  = $s('voice');
        $voicedefault = $s('voice_systemdefault');
        $voicehelpbtn   = $s('voice_help_button');
        $voicehelptitle = $s('voice_help_title');
        $voicehelptip   = $s('voice_help_tip');
        $voicehelplink  = $s('voice_help_link');

        $noscript    = $s('noscript');

        $aboutbtn    = $s('about_button');
        $abouttitle  = $s('about_title');
        $aboutbody   = $s('about_body');
        $aboutclose  = $s('about_close');

        $linktoolkit = $s('link_toolkit');
        $linkscriofa = $s('link_scriofa');
        $linksite    = $s('link_site');
        $newtab      = $s('opens_new_tab');

        $logo = $OUTPUT->image_url('logo', 'block_accessibility')->out();
        $logoalt = $s('logo_alt');

        // Screen-reader-only descriptive suffixes keep the visible symbol short
        // while still meeting "Label in Name" (the visible text is included).
        $srdec = html_writer::span(' ' . $s('decrease'), 'sr-only');
        $srinc = html_writer::span(' ' . $s('increase'), 'sr-only');

        $html = <<<HTML
<div class="block_accessibility_tools" id="bfa-tools">

  <div role="group" aria-labelledby="bfa-size-label" class="bfa-group">
    <p id="bfa-size-label" class="bfa-label">{$sizelabel}</p>
    <div class="bfa-btnrow">
      <button type="button" class="bfa-btn" id="bfa-dec" title="{$decrease}">A&#8722;{$srdec}</button>
      <button type="button" class="bfa-btn" id="bfa-reset" disabled>{$reset}</button>
      <button type="button" class="bfa-btn" id="bfa-inc" title="{$increase}">A+{$srinc}</button>
    </div>
  </div>

  <div role="group" aria-labelledby="bfa-theme-label" class="bfa-group">
    <p id="bfa-theme-label" class="bfa-label">{$themelabel}</p>
    <div class="bfa-btnrow">
      <button type="button" class="bfa-btn bfa-theme bfa-swatch bfa-swatch-1" id="bfa-theme-1"
              data-scheme="1" data-label="{$themedefault}" aria-label="{$themedefaultlabel}"
              aria-pressed="true"><span aria-hidden="true">A</span></button>
      <button type="button" class="bfa-btn bfa-theme bfa-swatch bfa-swatch-2" id="bfa-theme-2"
              data-scheme="2" data-label="{$themecream}" aria-label="{$themecreamlabel}"
              aria-pressed="false"><span aria-hidden="true">A</span></button>
      <button type="button" class="bfa-btn bfa-theme bfa-swatch bfa-swatch-3" id="bfa-theme-3"
              data-scheme="3" data-label="{$themeblue}" aria-label="{$themebluelabel}"
              aria-pressed="false"><span aria-hidden="true">A</span></button>
      <button type="button" class="bfa-btn bfa-theme bfa-swatch bfa-swatch-4" id="bfa-theme-4"
              data-scheme="4" data-label="{$themehigh}" aria-label="{$themehighlabel}"
              aria-pressed="false"><span aria-hidden="true">A</span></button>
      <button type="button" class="bfa-btn bfa-theme bfa-swatch bfa-swatch-5" id="bfa-theme-5"
              data-scheme="5" data-label="{$themedark}" aria-label="{$themedarklabel}"
              aria-pressed="false"><span aria-hidden="true">A</span></button>
    </div>
  </div>

  <div role="group" aria-labelledby="bfa-read-label" class="bfa-group bfa-read" id="bfa-read-group">
    <p id="bfa-read-label" class="bfa-label">{$readlabel}</p>
    <div class="bfa-btnrow">
      <button type="button" class="bfa-btn" id="bfa-read-selection"><span aria-hidden="true">{$readselectionshort}</span><span
        class="sr-only">{$readselection}</span></button>
      <button type="button" class="bfa-btn" id="bfa-read-page"><span aria-hidden="true">{$readpageshort}</span><span
        class="sr-only">{$readpage}</span></button>
      <button type="button" class="bfa-btn" id="bfa-read-stop">{$stop}</button>
    </div>
    <div class="bfa-voice">
      <label for="bfa-voice" id="bfa-voice-label">{$voicelabel}</label>
      <select id="bfa-voice"><option value="">{$voicedefault}</option></select>
      <button type="button" class="bfa-btn bfa-voicehelp" id="bfa-voicehelp-open" hidden><span
        aria-hidden="true">&#9432;</span> {$voicehelpbtn}</button>
    </div>
    <div class="bfa-rate">
      <label for="bfa-rate">{$ratelabel}</label>
      <input type="range" id="bfa-rate" min="0.5" max="2" step="0.1" value="1">
      <span id="bfa-rate-value" aria-hidden="true">1.0&#215;</span>
    </div>
  </div>

  <p id="bfa-status" class="bfa-status" role="status" aria-live="polite"></p>

  <noscript>
    <p class="bfa-noscript">{$noscript}</p>
  </noscript>

  <div class="bfa-footer">
    <button type="button" class="bfa-about-link" id="bfa-about-open">{$aboutbtn}</button>
  </div>

  <dialog id="bfa-about" class="bfa-dialog" aria-labelledby="bfa-about-title">
    <div class="bfa-dialog-inner">
      <img src="{$logo}" alt="{$logoalt}" class="bfa-logo" width="180" height="41">
      <h2 id="bfa-about-title">{$abouttitle}</h2>
      <p>{$aboutbody}</p>
      <ul class="bfa-links">
        <li><a href="https://www.brickfield.ie/accessibility-toolkit/" target="_blank"
          rel="noopener">{$linktoolkit} {$newtab}</a></li>
        <li><a href="https://www.brickfield.ie/scriofa/" target="_blank" rel="noopener">{$linkscriofa} {$newtab}</a></li>
        <li><a href="https://www.brickfield.ie/" target="_blank" rel="noopener">{$linksite} {$newtab}</a></li>
      </ul>
      <button type="button" class="bfa-btn" id="bfa-about-close">{$aboutclose}</button>
    </div>
  </dialog>

  <dialog id="bfa-voicehelp" class="bfa-dialog" aria-labelledby="bfa-voicehelp-title">
    <div class="bfa-dialog-inner">
      <h2 id="bfa-voicehelp-title">{$voicehelptitle}</h2>
      <p id="bfa-voicehelp-intro"></p>
      <p>{$voicehelptip}</p>
      <p><a href="https://docs.brickfield.ie/block_accessibility/faq/" target="_blank"
        rel="noopener">{$voicehelplink} {$newtab}</a></p>
      <button type="button" class="bfa-btn" id="bfa-voicehelp-close">{$aboutclose}</button>
    </div>
  </dialog>

</div>
HTML;

        return $html;
    }
}
