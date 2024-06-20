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

use block_accessibility\privacy\provider;
use context_system;
use context_user;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

/**
 * Base class for unit tests for block_accessibility.
 *
 * @package    block_accessibility
 * @copyright  2022 Catalyst IT Canada
 * @author     Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider_test extends provider_testcase {

    /**
     * Basic setup for these tests.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Test getting the context for the user ID related to this plugin.
     */
    public function test_get_contexts_for_userid() {

        $user = $this->getDataGenerator()->create_user();
        $context = context_user::instance($user->id);

        $this->add_block_accessibility($user);

        $contextlist = provider::get_contexts_for_userid($user->id);

        $this->assertEquals($context, $contextlist->current());
    }

    /**
     * Test that data is exported correctly for this plugin.
     */
    public function test_export_user_data() {

        $user = $this->getDataGenerator()->create_user();
        $context = context_user::instance($user->id);

        $this->add_block_accessibility($user);
        $this->add_block_accessibility($user);

        $writer = writer::with_context($context);
        $this->assertFalse($writer->has_any_data());
        $this->export_context_data_for_user($user->id, $context, 'block_accessibility');

        $data = $writer->get_data([get_string('pluginname', 'block_accessibility')]);
        $this->assertCount(2, $data->accessibility);
        $accessibility1 = reset($data->accessibility);
        $this->assertEquals('131.0', $accessibility1->fontsize);
        $this->assertEquals('3', $accessibility1->colourscheme);
        $this->assertEquals('1', $accessibility1->autoload_atbar);
    }

    /**
     * Test that only users within a course context are fetched.
     */
    public function test_get_users_in_context() {
        $component = 'block_accessibility';

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);

        $userlist = new userlist($usercontext, $component);
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist);

        $this->add_block_accessibility($user);

        // The list of users within the user context should contain user.
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist);
        $expected = [$user->id];
        $actual = $userlist->get_userids();
        $this->assertEquals($expected, $actual);

        // The list of users within the system context should be empty.
        $systemcontext = context_system::instance();
        $userlist2 = new userlist($systemcontext, $component);
        provider::get_users_in_context($userlist2);
        $this->assertCount(0, $userlist2);
    }

    /**
     * Test that data for users in approved userlist is deleted.
     */
    public function test_delete_data_for_users() {
        $component = 'block_accessibility';

        $user1 = $this->getDataGenerator()->create_user();
        $usercontext1 = context_user::instance($user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $usercontext2 = context_user::instance($user2->id);

        $this->add_block_accessibility($user1);
        $this->add_block_accessibility($user2);

        $userlist1 = new userlist($usercontext1, $component);
        provider::get_users_in_context($userlist1);
        $this->assertCount(1, $userlist1);
        $expected = [$user1->id];
        $actual = $userlist1->get_userids();
        $this->assertEquals($expected, $actual);

        $userlist2 = new userlist($usercontext2, $component);
        provider::get_users_in_context($userlist2);
        $this->assertCount(1, $userlist2);
        $expected = [$user2->id];
        $actual = $userlist2->get_userids();
        $this->assertEquals($expected, $actual);

        // Convert $userlist1 into an approved_contextlist.
        $approvedlist1 = new approved_userlist($usercontext1, $component, $userlist1->get_userids());
        // Delete using delete_data_for_user.
        provider::delete_data_for_users($approvedlist1);

        // Re-fetch users in usercontext1.
        $userlist1 = new userlist($usercontext1, $component);
        provider::get_users_in_context($userlist1);
        // The user data in usercontext1 should be deleted.
        $this->assertCount(0, $userlist1);

        // Re-fetch users in usercontext2.
        $userlist2 = new userlist($usercontext2, $component);
        provider::get_users_in_context($userlist2);
        // The user data in usercontext2 should be still present.
        $this->assertCount(1, $userlist2);

        // Convert $userlist2 into an approved_contextlist in the system context.
        $systemcontext = context_system::instance();
        $approvedlist2 = new approved_userlist($systemcontext, $component, $userlist2->get_userids());
        // Delete using delete_data_for_user.
        provider::delete_data_for_users($approvedlist2);
        // Re-fetch users in usercontext2.
        $userlist2 = new userlist($usercontext2, $component);
        provider::get_users_in_context($userlist2);
        // The user data in systemcontext should not be deleted.
        $this->assertCount(1, $userlist2);
    }

    /**
     * Test that user data is deleted using the context.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $context = context_user::instance($user->id);

        $this->add_block_accessibility($user);

        // Check that we have an entry.
        $accessibility = $DB->get_records('block_accessibility', ['userid' => $user->id]);
        $this->assertCount(1, $accessibility);

        provider::delete_data_for_all_users_in_context($context);

        // Check that it has now been deleted.
        $accessibility = $DB->get_records('block_accessibility', ['userid' => $user->id]);
        $this->assertCount(0, $accessibility);
    }

    /**
     * Test that user data is deleted for this user.
     */
    public function test_delete_data_for_user() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $context = context_user::instance($user->id);

        $this->add_block_accessibility($user);

        // Check that we have an entry.
        $accessibility = $DB->get_records('block_accessibility', ['userid' => $user->id]);
        $this->assertCount(1, $accessibility);

        $approvedlist = new approved_contextlist($user, 'block_accessibility', [$context->id]);
        provider::delete_data_for_user($approvedlist);

        // Check that it has now been deleted.
        $accessibility = $DB->get_records('block_accessibility', ['userid' => $user->id]);
        $this->assertCount(0, $accessibility);
    }

    /**
     * Add dummy block accessibility.
     *
     * @param object $user User object
     */
    private function add_block_accessibility($user) {
        global $DB;

        $accessibility = [
            'userid' => $user->id,
            'fontsize' => 131.0,
            'colourscheme' => 3,
            'autoload_atbar' => 1,
        ];

        $DB->insert_record('block_accessibility', $accessibility);
    }
}
