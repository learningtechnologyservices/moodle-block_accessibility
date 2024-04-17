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
 * Define metadata for Accessibility block
 *
 * This file is the cornerstone of the block - when the page loads, it
 * checks if the user has a custom settings for the font size and colour
 * scheme (either in the session or the database) and creates a stylesheet
 * to override the standard styles with this setting.
 *
 * @package   block_accessibility
 * @copyright 2021 Brickfield Education Labs <https://www.brickfield.ie/>
 * @author    Jay Churchward <jay@brickfieldlabs.ie>
 * @author    Mark Johnson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_accessibility';
$plugin->version = 2021092203;
$plugin->requires = 2020061500; // Moodle 3.9 and up.
$plugin->release  = '1.39.03 (Build - 2021092201)';
$plugin->cron = 3600;
$plugin->maturity = MATURITY_STABLE;
