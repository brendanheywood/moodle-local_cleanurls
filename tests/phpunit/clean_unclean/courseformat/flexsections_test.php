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
 * CleanURLS Support for format_flexsections Tests.
 *
 * #####################################################
 * # PLEASE READ BEFORE USING THIS FILE AS AN EXAMPLE! #
 * #####################################################
 *
 * This tests are slightly different as they are made to be independent of Clean URLs,
 * which means that if Clean URLs was not installed, the tests would be skipped without failing.
 *
 * Although in this case this is shipped with Clean URLs (so it will always be available),
 * you can use this file as an example of unit tests for your custom format without requiring Clean URLs.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\activity_path;
use local_cleanurls\local\courseformat\flexsections;
use local_cleanurls\local\uncleaner\root_uncleaner;
use local_cleanurls\local\uncleaner\uncleaner;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for flexsections_support.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_flexsections_support_test extends advanced_testcase {
    /** @var stdClass */
    private $course = null;

    /** @var stdClass[] */
    private $forums = [];

    /** @var stdClass[] */
    private $sections = null;

    protected function setUp() {
        global $CFG;
        parent::setUp();

        // If you are using this as an example for your plugin, check if Clean URLs is installed.
        $testcase = $CFG->dirroot . '/local/cleanurls/tests/phpunit/cleanurls_testcase.php';
        if (!file_exists($testcase)) {
            $this->markTestSkipped('CleanURLs not available.');
            return;
        }
        require_once($testcase);
        local_cleanurls_testcase::enable_cleanurls();

        // If this is included in Clean URLs, check if the plugin is available.
        $plugin = $CFG->dirroot . '/course/format/flexsections';
        if (!file_exists($plugin)) {
            $this->markTestSkipped('format_flexsections not available.');
            return;
        }

        $this->resetAfterTest(true);
    }

    public function test_its_uncleaner_can_be_created() {
        $this->create_course();
        $root = new root_uncleaner($this->get_example_url());
        $format = $root->get_child()->get_child();
        self::assertTrue(flexsections::can_create($format));
        $flex = $format->get_child();
        self::assertInstanceOf(flexsections::class, $flex);
    }

    public function test_its_uncleaner_requires_a_course() {
        $this->create_course();
        $root = new root_uncleaner('/');
        self::assertFalse(flexsections::can_create($root));
    }

    public function test_its_uncleaner_requires_the_correct_format() {
        $this->create_course(['format' => 'topics']);
        $root = new root_uncleaner($this->get_example_url());
        $course = $root->get_child();
        $format = $course->get_child();
        self::assertFalse(flexsections::can_create($format));
    }

    public function test_it_creates_a_section_tree() {
        $this->create_course();
        $root = new root_uncleaner($this->get_example_url());
        $flex = $root->get_child()->get_child()->get_child();
        $tree = $flex->get_section_tree();

        self::assertCount(2, $tree->flexsections);
        self::assertEquals(1, $tree->flexsections[0]->section);
        self::assertEquals(4, $tree->flexsections[1]->section);

        self::assertCount(2, $tree->flexsections[0]->flexsections);
        self::assertEquals(2, $tree->flexsections[0]->flexsections[0]->section);
        self::assertEquals(3, $tree->flexsections[0]->flexsections[1]->section);

        self::assertCount(2, $tree->flexsections[0]->flexsections[0]->flexsections);
        self::assertEquals(5, $tree->flexsections[0]->flexsections[0]->flexsections[0]->section);
        self::assertEquals(6, $tree->flexsections[0]->flexsections[0]->flexsections[1]->section);

        self::assertCount(0, $tree->flexsections[0]->flexsections[0]->flexsections[0]->flexsections);

        self::assertCount(0, $tree->flexsections[0]->flexsections[0]->flexsections[1]->flexsections);

        self::assertCount(1, $tree->flexsections[0]->flexsections[1]->flexsections);
        self::assertEquals(7, $tree->flexsections[0]->flexsections[1]->flexsections[0]->section);

        self::assertCount(0, $tree->flexsections[0]->flexsections[1]->flexsections[0]->flexsections);

        self::assertCount(0, $tree->flexsections[1]->flexsections);
    }

    public function test_it_prepares_the_path_correctly() {
        $this->create_course();
        $forumid = $this->forums[6]->cmid;
        $root = $root = new root_uncleaner($this->get_example_url() . '/abc/def/ghi');
        $flex = $root->get_child()->get_child()->get_child();

        self::assertSame("topic-1/topic-2/topic-6/{$forumid}-forum", $flex->get_mypath());
        self::assertSame(['abc', 'def', 'ghi'], $flex->get_subpath());
        self::assertEquals($forumid, $flex->get_course_module()->id);

        $sectionpath = $flex->get_section_path();
        self::assertCount(4, $sectionpath);
        self::assertSame('General', get_section_name($this->course, $sectionpath[0]->section));
        self::assertSame('Topic 1', get_section_name($this->course, $sectionpath[1]->section));
        self::assertSame('Topic 2', get_section_name($this->course, $sectionpath[2]->section));
        self::assertSame('Topic 6', get_section_name($this->course, $sectionpath[3]->section));
    }

    public function test_it_cleans_and_uncleans() {
        $this->create_course();

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $this->forums[6]->cmid;
        $expected = 'http://www.example.com/moodle/course/mycourse/topic-1/topic-2/topic-6/' .
                    "{$this->forums[6]->cmid}-forum-6";
        local_cleanurls_testcase::assert_clean_unclean($url, $expected);
    }

    public function test_it_cleans_and_uncleans_section_numbers() {
        $this->create_course();

        $url = 'http://www.example.com/moodle/course/view.php?name=mycourse&section=6';
        $expected = 'http://www.example.com/moodle/course/mycourse/topic-1/topic-2/topic-6';
        local_cleanurls_testcase::assert_clean_unclean($url, $expected);
    }

    public function test_it_cleans_and_uncleans_section_id() {
        $this->create_course();
        $sectionid = $this->sections[6]->id;

        $url = "http://www.example.com/moodle/course/view.php?name=mycourse&sectionid={$sectionid}";
        $expected = 'http://www.example.com/moodle/course/mycourse/topic-1/topic-2/topic-6';
        $expecteduncleaned = 'http://www.example.com/moodle/course/view.php?name=mycourse&section=6';
        local_cleanurls_testcase::assert_clean_unclean($url, $expected, $expecteduncleaned);
    }

    public function test_it_uncleans_at_course_level() {
        $this->create_course();

        $clean = 'http://www.example.com/moodle/course/mycourse';
        $unclean = uncleaner::unclean($clean);

        $expected = 'http://www.example.com/moodle/course/view.php?name=mycourse';
        self::assertSame($expected, $unclean->raw_out());
    }

    public function test_it_supports_custom_activity_names() {
        $this->create_course();
        activity_path::save_path_for_cmid($this->forums[6]->cmid, 'an-important-forum');

        $url = "http://www.example.com/moodle/mod/forum/view.php?id={$this->forums[6]->cmid}";
        $expected = 'http://www.example.com/moodle/course/mycourse/topic-1/topic-2/topic-6/an-important-forum';
        local_cleanurls_testcase::assert_clean_unclean($url, $expected);
    }

    protected function create_course($options = []) {
        global $DB;

        $defaults = [
            'shortname'   => 'mycourse',
            'format'      => 'flexsections',
            'numsections' => 7,
        ];
        $options = array_merge($defaults, $options);
        $this->course = $this->getDataGenerator()->create_course($options);
        $sectionstmp = $DB->get_records('course_sections', ['course' => $this->course->id], 'section ASC');
        $this->sections = [];
        foreach ($sectionstmp as $section) {
            $this->sections[$section->section] = $section;
        }

        $this->create_course_section_parent(1, null); // No parent.
        $this->create_course_section_parent(2, 1);
        $this->create_course_section_parent(3, 1);
        $this->create_course_section_parent(4, 0); // Another way for no parent (root parent).
        $this->create_course_section_parent(5, 2);
        $this->create_course_section_parent(6, 2);
        $this->create_course_section_parent(7, 3);
    }

    private function create_course_section_parent($idchild, $numparent) {
        global $DB;
        if (!is_null($numparent)) {
            $DB->insert_record('course_format_options', (object)[
                'courseid'  => $this->course->id,
                'format'    => 'flexsections',
                'sectionid' => $this->sections[$idchild]->id,
                'name'      => 'parent',
                'value'     => $this->sections[$numparent]->section,
            ]);
        }
        $num = $this->sections[$idchild]->section;
        $this->forums[$num] = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $this->course->id, 'name' => "Forum {$num}", 'section' => $num]
        );
    }

    private function get_example_url() {
        return "/course/mycourse/topic-1/topic-2/topic-6/{$this->forums[6]->cmid}-forum";
    }
}
