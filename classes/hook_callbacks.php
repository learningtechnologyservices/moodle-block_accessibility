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

namespace block_accessibility;

use core\hook\output\before_standard_head_html_generation;

/**
 * Hook callbacks for the Accessibility block.
 *
 * @package   block_accessibility
 * @copyright 2026 Brickfield Education Labs <https://www.brickfield.ie/>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Apply the reader's saved display preferences before the page paints.
     *
     * This reads the "bfa" cookie synchronously in the document head and sets a
     * CSS variable and a class on the document root element (<html>), so a saved
     * text scale or colour theme is applied on every page with no flash of
     * default styling.
     * The block itself need not be present on the page for this to work.
     *
     * @param before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(before_standard_head_html_generation $hook): void {
        // Only inject the pre-paint script for readers who have actually set a
        // preference (the "bfa" cookie is present). Everyone else gets nothing, so the
        // vast majority of page views carry no extra markup. We check existence only;
        // the value is re-read and validated inside the script itself.
        if (!isset($_COOKIE['bfa'])) {
            return;
        }

        $script = <<<'JS'
<script>
(function () {
    try {
        var m = document.cookie.match(/(?:^|;\s*)bfa=([^;]*)/);
        if (!m) { return; }
        var v = decodeURIComponent(m[1]).split('|');
        var scale = parseFloat(v[0]);
        var scheme = parseInt(v[1], 10);
        var el = document.documentElement;
        if (scale && Math.abs(scale - 1) > 0.001) {
            el.style.setProperty('--bfa-scale', scale);
            el.classList.add('bfa-font-active');
        }
        if (scheme && scheme > 1) {
            el.classList.add('bfa-scheme-' + scheme);
        }
        if (scheme === 4 || scheme === 5) {
            el.setAttribute('data-bs-theme', 'dark');
        }
    } catch (e) {
        // Never let a preference cookie break the page.
    }
})();
</script>
JS;

        $hook->add_html($script);
    }
}
