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

/**
 * Tests for the Accessibility block class.
 *
 * @package   block_accessibility
 * @copyright 2026 Brickfield Education Labs <https://www.brickfield.ie/>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \block_accessibility
 */
final class block_accessibility_test extends \advanced_testcase {
    /**
     * The block main class is a legacy global class, so load it (and its parent) explicitly.
     */
    protected function setUp(): void {
        parent::setUp();
        require_once(__DIR__ . '/../../moodleblock.class.php');
        require_once(__DIR__ . '/../block_accessibility.php');
    }

    /**
     * Build a block instance ready to render on a system-context page.
     *
     * @return \block_accessibility
     */
    protected function make_block(): \block_accessibility {
        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $page->set_url('/');
        $block = new \block_accessibility();
        $block->page = $page;
        return $block;
    }

    /**
     * get_content() renders the full control set: text size, the five colour-theme
     * swatches, the read-aloud controls and the About dialog.
     *
     * @covers ::get_content
     * @covers ::render_tools
     */
    public function test_get_content_renders_all_controls(): void {
        $this->resetAfterTest();

        $content = $this->make_block()->get_content();

        $this->assertNotNull($content);
        $this->assertStringContainsString('id="bfa-tools"', $content->text);
        for ($scheme = 1; $scheme <= 5; $scheme++) {
            $this->assertStringContainsString('data-scheme="' . $scheme . '"', $content->text);
        }
        $this->assertStringContainsString('id="bfa-inc"', $content->text);
        $this->assertStringContainsString('id="bfa-read-selection"', $content->text);
        $this->assertStringContainsString('id="bfa-about"', $content->text);
        // The read-aloud voice selector renders with its System default option.
        $this->assertStringContainsString('id="bfa-voice"', $content->text);
        $default = get_string('voice_systemdefault', 'block_accessibility');
        $this->assertStringContainsString('<option value="">' . $default, $content->text);
        // The voice-help affordance and its dialog (surfaced when no voice matches the language).
        $this->assertStringContainsString('id="bfa-voicehelp-open"', $content->text);
        $this->assertStringContainsString('id="bfa-voicehelp"', $content->text);
    }

    /**
     * get_content() caches its result and returns the same object on a second call.
     *
     * @covers ::get_content
     */
    public function test_get_content_is_cached(): void {
        $this->resetAfterTest();

        $block = $this->make_block();
        $first = $block->get_content();
        $second = $block->get_content();

        $this->assertSame($first, $second);
    }

    /**
     * The block allows itself everywhere and permits only one instance per page.
     *
     * @covers ::applicable_formats
     * @covers ::instance_allow_multiple
     */
    public function test_block_placement_rules(): void {
        $block = new \block_accessibility();

        $this->assertSame(['all' => true], $block->applicable_formats());
        $this->assertFalse($block->instance_allow_multiple());
    }
}
