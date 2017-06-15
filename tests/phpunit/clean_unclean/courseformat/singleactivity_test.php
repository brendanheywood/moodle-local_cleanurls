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

use local_cleanurls\local\courseformat\singleactivity;
use local_cleanurls\local\uncleaner\root_uncleaner;

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
class local_cleanurls_simpleactivity_cleanunclean_test extends local_cleanurls_testcase {
    public function test_it_supports_single_activity_format() {
        $course = $this->getDataGenerator()->create_course(['shortname' => 'SingleActivity', 'format' => 'singleactivity']);
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id, 'name' => 'The Single Forum']);

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $forum->cmid;
        $expected = 'http://www.example.com/moodle/course/SingleActivity';
        static::assert_clean_unclean($url, $expected);
    }

    public function test_it_can_be_created_inside_courseformat_uncleaner() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'singleactivity']);
        $root = new root_uncleaner('/course/shortname/forum/123-idme');
        $format = $root->get_child()->get_child();
        self::assertTrue(singleactivity::can_create($format));
        self::assertInstanceOf(singleactivity::class, $format->get_child());
    }

    public function test_it_cannot_be_created_if_parent_has_no_course() {
        $root = new root_uncleaner('/course');
        self::assertFalse(singleactivity::can_create($root));
    }

    public function test_it_cannot_be_created_if_course_has_the_wrong_format() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'weeks']);
        $root = new root_uncleaner('/course/shortname/forum/123-idme');
        $format = $root->get_child()->get_child();
        self::assertFalse(singleactivity::can_create($format));
    }
}
