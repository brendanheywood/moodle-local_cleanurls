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
require_once(__DIR__ . '/../cleanurls_testcase.php');

/**
 * Tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_coursemodule_cleanunclean_test extends local_cleanurls_testcase {
    public function test_it_cleans_course_module_view_urls() {
        global $DB;

        $category = $this->getDataGenerator()->create_category(['name' => 'category']);

        $course = $this->getDataGenerator()->create_course(
            [
                'fullname'  => 'course long name',
                'shortname' => 'shortname',
                'visible'   => 1,
                'category'  => $category->id,
            ]
        );
        // We are enforcing 'customformat' to not trigger format-specific cleaning/uncleaning.
        $course->format = 'customformat';
        $DB->update_record('course', $course);

        $forum = $this->getDataGenerator()->create_module('forum', [
            'course' => $course->id,
            'name'   => 'A Test Forum',
        ]);

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $forum->cmid;
        $expected = 'http://www.example.com/moodle/course/shortname/' . $forum->cmid . '-a-test-forum';
        static::assert_clean_unclean($url, $expected);
        $this->resetDebugging(); // There can be a debugging regarding the invalid format.
    }

    public function test_it_cleans_course_modules_urls() {
        $course = $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'unknownformat']);

        static::assert_clean_unclean('http://www.example.com/moodle/mod/forum/index.php?id=' . $course->id,
                                     'http://www.example.com/moodle/course/shortname/forum');
        $this->resetDebugging(); // There can be a debugging regarding the invalid 'customformat'.
    }

    public function test_it_supports_custom_activity_names() {
        $course = $this->getDataGenerator()->create_course(['shortname' => 'shortcourse', 'format' => 'unknownformat']);
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id, 'name' => 'Test Forum']);
        list(, $cm) = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);
        activity_path::save_path_for_cmid($forum->cmid, 'myforum');

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $cm->id;
        $expected = 'http://www.example.com/moodle/course/shortcourse/myforum';
        static::assert_clean_unclean($url, $expected);

        $this->resetDebugging(); // There can be a debugging regarding the invalid 'customformat'.
    }
}
