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
 * Version metadata for the Accessibility block.
 *
 * A complete rewrite and simplification. The block now runs entirely in the
 * browser: a single cookie holds the reader's display preferences, and read-aloud
 * uses the browser's built-in Web Speech API. No third-party libraries,
 * no server round-trips, no custom database table.
 *
 * @package   block_accessibility
 * @copyright 2026 Brickfield Education Labs <https://www.brickfield.ie/>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_accessibility';
$plugin->version   = 2026071025;
$plugin->requires  = 2024100700;       // Moodle 4.5 and up.
$plugin->supported = [405, 500];       // Moodle 4.5 to 5.0.
$plugin->release   = '2.0.0 (Build - 2026071025)';
$plugin->maturity  = MATURITY_STABLE;
