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
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\activity_path;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../cleanurls_testcase.php');

/**
 * Tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_weeks_cleanunclean_test extends local_cleanurls_testcase {
    public function test_it_supports_weeks_format() {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['shortname' => 'weekscourse', 'format' => 'weeks']);
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => 'Week 1 Discussion', 'section' => 1]
        );
        list(, $cm) = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);

        // Give a name to the section.
        $DB->update_record('course_sections', (object)['id' => $cm->section, 'name' => 'First Week']);

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $cm->id;
        $expected = 'http://www.example.com/moodle/course/weekscourse/' .
                    "first-week/{$forum->cmid}-week-1-discussion";
        static::assert_clean_unclean($url, $expected);
    }

    public function test_it_works_even_if_section_has_no_name() {
        $course = $this->getDataGenerator()->create_course(['shortname' => 'weekscourse', 'format' => 'weeks']);
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => 'Week 1 Discussion', 'section' => 1]
        );
        list(, $cm) = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $cm->id;
        $expected = "http://www.example.com/moodle/course/weekscourse/1-january-7-january/{$forum->cmid}-week-1-discussion";
        static::assert_clean_unclean($url, $expected);
    }

    public function test_it_supports_custom_activity_names() {
        $course = $this->getDataGenerator()->create_course(['shortname' => 'weekscourse', 'format' => 'weeks']);
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => 'Week 1 Discussion', 'section' => 1]
        );
        list(, $cm) = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);
        activity_path::save_path_for_cmid($forum->cmid, 'myweekforum');

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $cm->id;
        $expected = "http://www.example.com/moodle/course/weekscourse/1-january-7-january/myweekforum";
        static::assert_clean_unclean($url, $expected);

        $this->resetDebugging(); // There can be a debugging regarding the invalid 'customformat'.
    }
}
