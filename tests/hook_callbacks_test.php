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
 * Tests for the Accessibility block hook callbacks.
 *
 * @package   block_accessibility
 * @copyright 2026 Brickfield Education Labs <https://www.brickfield.ie/>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \block_accessibility\hook_callbacks
 */
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * Remove the preferences cookie after each test so it cannot leak between tests.
     */
    protected function tearDown(): void {
        unset($_COOKIE['bfa']);
        parent::tearDown();
    }

    /**
     * No pre-paint script is added when the reader has no preferences cookie.
     *
     * @covers ::before_standard_head_html_generation
     */
    public function test_no_output_without_cookie(): void {
        global $PAGE;
        $this->resetAfterTest();
        unset($_COOKIE['bfa']);

        $hook = new before_standard_head_html_generation($PAGE->get_renderer('core'));
        hook_callbacks::before_standard_head_html_generation($hook);

        $this->assertSame('', $hook->get_output());
    }

    /**
     * The pre-paint script is added, including the dark-mode branch, when the cookie is set.
     *
     * @covers ::before_standard_head_html_generation
     */
    public function test_script_emitted_with_cookie(): void {
        global $PAGE;
        $this->resetAfterTest();
        $_COOKIE['bfa'] = '1.3|4';

        $hook = new before_standard_head_html_generation($PAGE->get_renderer('core'));
        hook_callbacks::before_standard_head_html_generation($hook);
        $output = $hook->get_output();

        $this->assertStringContainsString('bfa', $output);
        $this->assertStringContainsString('bfa-scheme-', $output);
        $this->assertStringContainsString('data-bs-theme', $output);
    }
}
