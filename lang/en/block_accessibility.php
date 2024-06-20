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
 * English Lang Strings                                                (1)
 *
 * @author  Mark Johnson                                              (2)
 * @copyright &copy; Taunton's College 2009                             (3)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (4)
 */
defined('MOODLE_INTERNAL') || die();

$string['autolaunch'] = '(always?)';
$string['blockname'] = 'Accessibility';
$string['clearedoldcache'] = 'Cleared {$a} old files from the cache';
$string['char'] = 'A';
$string['dectext'] = 'Decrease Text Size';
$string['inctext'] = 'Increase Text Size';
$string['invalidop'] = 'The specified operation was invalid!';
$string['jsnocolour'] = 'Error changing colour scheme';
$string['jsnocolourreset'] = 'Error resetting colour scheme';
$string['jsnosave'] = 'Error saving settings';
$string['jsnosize'] = 'Error changing size';
$string['jsnosizereset'] = 'Error resetting text size';
$string['launchtoolbar'] = 'Launch ATbar';
$string['pluginname'] = 'Accessibility';
$string['pluginnameplural'] = 'Accessibility Blocks';
$string['resettext'] = 'Reset Text Size (Clears Saved Setting)';
$string['reset'] = 'Setting Cleared';
$string['save'] = 'Save Setting';
$string['saved'] = 'Setting Saved';
$string['col1text'] = 'Default Colour Scheme (Clears Saved Setting)';
$string['col2text'] = 'Lowered Contrast 1';
$string['col3text'] = 'Lowered Contrast 2';
$string['col4text'] = 'High Contrast';

// Configuration form - please help us translate it on GitHub.
$string['config_showATbar'] = 'ATbar';
$string['config_showATbar_checkbox'] = 'Allow ATbar appearance within Accessibility block';
$string['config_showATbar_help'] = 'Accessibility block also integrates ATbar from Southampton University ECS <a href="http://www.atbar.org">http://www.atbar.org</a>.';
$string['config_fg'] = 'Text colour (not required)';
$string['config_fg_help'] = 'Define colour scheme foreground colour here. Keep in mind that the colour will be applied uniformly to all user interface elements. It is not always desirable to have the same colour in each user interface element. <strong>It is recommended to leave foreground colour field empty</strong> so that elements can keep its default colours. Try to change background colour only.';
$string['config_bg'] = 'Background colour';
$string['config_bg_help'] = 'Define colour scheme background colour here. Keep in mind that the background colour will be applied uniformly to all user interface elements.';
$string['color_input_error'] = 'Please enter a color in a format as such: #FF0050';

$string['accessibility:addinstance'] = 'Add a new Accessibility block';
$string['accessibility:myaddinstance'] = 'Add a new Accessibility block to My home';
$string['jsnotloggedin'] = 'Error! Please check if you are logged-in to the system or contact your administrator';

// Privacy.
$string['privacy:metadata:block_accessibility'] = 'Information about the accessibility settings per user.';
$string['privacy:metadata:block_accessibility:userid'] = 'The ID of the user with this accesibility setting.';
$string['privacy:metadata:block_accessibility:fontsize'] = 'The font size chosen by the user.';
$string['privacy:metadata:block_accessibility:colourscheme'] = 'The color scheme chosen by the user.';
$string['privacy:metadata:block_accessibility:autoload_atbar'] = 'Whether or not the user has ATbar enabled.';
