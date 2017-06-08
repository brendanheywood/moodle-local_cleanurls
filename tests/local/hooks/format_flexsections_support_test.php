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
 * For more information, please check:
 * https://github.com/brendanheywood/moodle-local_cleanurls
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
    protected function setUp() {
        global $CFG;
        parent::setUp();

        $testcase = $CFG->dirroot . '/local/cleanurls/tests/cleanurls_testcase.php';
        if (!file_exists($testcase)) {
            $this->markTestSkipped('CleanURLs not available.');
            return;
        }
        require_once($testcase);
        local_cleanurls_testcase::enable_cleanurls();

        $this->resetAfterTest(true);
    }

    public function test_it_cleans_and_uncleans() {
        global $DB;
        $course = $this->getDataGenerator()->create_course([
                                                               'shortname'   => 'mycourse',
                                                               'format'      => 'flexsections',
                                                               'numsections' => 3,
                                                           ]);
        // Set flex 'Topic 1' -> 'Topic 2' -> 'Topic 3'
        $sections = $DB->get_records('course_sections', ['course' => $course->id], 'section ASC');
        $sections = array_values($sections);
        for ($i = 1; $i < 3; $i++) {
            $DB->insert_record('course_format_options', (object)[
                'courseid'  => $course->id,
                'format'    => 'flexsections',
                'sectionid' => $sections[$i + 1]->id,
                'name'      => 'parent',
                'value'     => $i,
            ]);
        }
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => 'The Forum', 'section' => 3]
        );

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $forum->cmid;
        $expected = 'http://www.example.com/moodle/course/mycourse/topic-1/topic-2/topic-3/' .
                    "{$forum->cmid}-the-forum";
        local_cleanurls_testcase::assert_clean_unclean($url, $expected);
    }
}
