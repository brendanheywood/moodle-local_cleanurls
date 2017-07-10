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
class local_cleanurls_topics_cleanunclean_test extends local_cleanurls_testcase {
    public function test_it_supports_topics_format() {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['shortname' => 'topicscourse', 'format' => 'topics']);
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => 'Forum First Section', 'section' => 1]
        );
        list(, $cm) = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);

        // Give a name to the section.
        $DB->update_record('course_sections', (object)['id' => $cm->section, 'name' => 'Custom Section']);

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $cm->id;
        $expected = 'http://www.example.com/moodle/course/topicscourse/' .
                    "custom-section/{$forum->cmid}-forum-first-section";
        static::assert_clean_unclean($url, $expected);
    }

    public function test_it_works_even_if_section_has_no_name() {
        $course = $this->getDataGenerator()->create_course(['shortname' => 'weekscourse', 'format' => 'topics']);
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => 'Week 1 Discussion', 'section' => 1]
        );
        list(, $cm) = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $cm->id;
        $expected = "http://www.example.com/moodle/course/weekscourse/topic-1/{$forum->cmid}-week-1-discussion";
        static::assert_clean_unclean($url, $expected);
    }

    public function test_it_works_with_section_numbers() {
        $this->getDataGenerator()->create_course(['shortname' => 'name', 'format' => 'topics']);

        $url = 'http://www.example.com/moodle/course/view.php?name=name&section=1';
        $expected = 'http://www.example.com/moodle/course/name/topic-1';
        static::assert_clean_unclean($url, $expected);
    }

    public function test_it_works_with_section_ids() {
        $course = $this->getDataGenerator()->create_course(['shortname' => 'name', 'format' => 'topics']);
        $info = get_fast_modinfo($course);
        $section = $info->get_section_info(1);

        $url = "http://www.example.com/moodle/course/view.php?name=name&sectionid={$section->id}";
        $expected = 'http://www.example.com/moodle/course/name/topic-1';
        $expecteduncleaned = 'http://www.example.com/moodle/course/view.php?name=name&section=1';
        static::assert_clean_unclean($url, $expected, $expecteduncleaned);
    }
}
