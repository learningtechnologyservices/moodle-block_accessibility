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
 * Upgrade steps for the Accessibility block.
 *
 * @package   block_accessibility
 * @copyright 2026 Brickfield Education Labs <https://www.brickfield.ie/>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Run the upgrade.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_accessibility_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Version 2.0.0 moves all preferences into a browser cookie and removes the
    // server-side store. Drop the legacy table if it still exists.
    if ($oldversion < 2026070900) {
        $table = new xmldb_table('block_accessibility');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        upgrade_block_savepoint(true, 2026070900, 'accessibility');
    }

    return true;
}
