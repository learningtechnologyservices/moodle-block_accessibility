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

/**
 * Tests for the Accessibility block privacy provider.
 *
 * @package   block_accessibility
 * @copyright 2026 Brickfield Education Labs <https://www.brickfield.ie/>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \block_accessibility\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * The provider is a null provider whose reason string exists in the language file.
     *
     * @covers ::get_reason
     */
    public function test_get_reason_names_an_existing_metadata_string(): void {
        $reason = provider::get_reason();
        $this->assertSame('privacy:metadata', $reason);
        $this->assertTrue(
            get_string_manager()->string_exists($reason, 'block_accessibility'),
            'The privacy reason string must exist in the component language file.'
        );
    }
}
