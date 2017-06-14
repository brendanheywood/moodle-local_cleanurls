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

use local_cleanurls\local\uncleaner\courseformat\flexsections_uncleaner;
use local_cleanurls\local\uncleaner\root_uncleaner;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for flexsections_support.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_callbacks_flexsections_support extends advanced_testcase {
    /** @var stdClass */
    private $course = null;

    /** @var stdClass */
    private $forum = null;

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
        $root = new root_uncleaner('/course/mycourse/topic-1/topic-2/topic-3/');
        $course = $root->get_child();
        $format = $course->get_child();
        self::assertTrue(flexsections_uncleaner::can_create($format));
        $flex = $format->get_child();
        self::assertInstanceOf(flexsections_uncleaner::class, $flex);
    }

    public function test_its_uncleaner_requires_a_course() {
        $this->create_course();
        $root = new root_uncleaner('/');
        self::assertFalse(flexsections_uncleaner::can_create($root));
    }

    public function test_its_uncleaner_requires_the_correct_format() {
        $this->getDataGenerator()->create_course(['shortname' => 'mycourse', 'format' => 'topics']);
        $root = new root_uncleaner('/course/mycourse/topic-1/topic-2/topic-3/');
        $course = $root->get_child();
        $format = $course->get_child();
        self::assertFalse(flexsections_uncleaner::can_create($format));
    }

    public function test_it_cleans_and_uncleans() {
        $this->create_course();

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $this->forum->cmid;
        $expected = 'http://www.example.com/moodle/course/mycourse/topic-1/topic-2/topic-3/' .
                    "{$this->forum->cmid}-the-forum";
        local_cleanurls_testcase::assert_clean_unclean($url, $expected);
    }

    protected function create_course() {
        global $DB;

        $this->course = $this->getDataGenerator()->create_course([
                                                                     'shortname'   => 'mycourse',
                                                                     'format'      => 'flexsections',
                                                                     'numsections' => 3,
                                                                 ]);
        // Set flex 'Topic 1' -> 'Topic 2' -> 'Topic 3'.
        $sections = $DB->get_records('course_sections', ['course' => $this->course->id], 'section ASC');
        $sections = array_values($sections);
        for ($i = 1; $i < 3; $i++) {
            $DB->insert_record('course_format_options', (object)[
                'courseid'  => $this->course->id,
                'format'    => 'flexsections',
                'sectionid' => $sections[$i + 1]->id,
                'name'      => 'parent',
                'value'     => $i,
            ]);
        }
        $this->forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $this->course->id, 'name' => 'The Forum', 'section' => 3]
        );
    }
}
