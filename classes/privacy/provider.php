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

namespace block_accessibility\privacy;

use context;
use context_block;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadataprovider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\plugin\provider as pluginprovider;

/**
 * Privacy provider for block_accessibility.
 *
 * @package    block_accessibility
 * @author     Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, pluginprovider, core_userlist_provider {
    /**
     * Get information about the user data stored by this plugin.
     *
     * @param  collection $collection An object for storing metadata.
     * @return collection The metadata.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table(
            'block_accessibility',
             [
                'userid' => 'privacy:metadata:block_accessibility:userid',
                'fontsize' => 'privacy:metadata:block_accessibility:fontsize',
                'colourscheme' => 'privacy:metadata:block_accessibility:colourscheme',
                'autoload_atbar' => 'privacy:metadata:block_accessibility:autoload_atbar',

             ],
            'privacy:metadata:block_accessibility'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int $userid The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $params = [
            'contextlevel'  => CONTEXT_USER,
            'userid'        => $userid,
        ];

        $sql = "SELECT ctx.id
                  FROM {block_accessibility} ba
                  JOIN {user} u ON ba.userid = u.id
                  JOIN {context} ctx ON ctx.instanceid = u.id
                   AND ctx.contextlevel = :contextlevel
                 WHERE ba.userid = :userid";
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $records = $DB->get_records('block_accessibility', ['userid' => $userid]);

        foreach ($records as $result) {
            $accesibility[] = (object) [
                'fontsize' => $result->fontsize,
                'colourscheme' => $result->colourscheme,
                'autoload_atbar' => $result->autoload_atbar,
            ];
        }
        if (!empty($accesibility)) {
            $data = (object) [
                'accessibility' => $accesibility,
            ];
            writer::with_context($contextlist->current())->export_data([
                    get_string('pluginname', 'block_accessibility')], $data);
        }
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }
        $DB->delete_records('block_accessibility', ['userid' => $context->instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $DB->delete_records('block_accessibility', ['userid' => $userid]);
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof context_user) {
            return;
        }

        $sql = "SELECT userid FROM {block_accessibility} WHERE userid = ?";
        $params = [$context->instanceid];
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context instanceof \context_user) {
            $DB->delete_records('block_accessibility', ['userid' => $context->instanceid]);
        }
    }
}
