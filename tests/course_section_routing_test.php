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
 * CleanURLs unit tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/cleanurls_testcase.php');

/**
 * Tests cleaning/uncleaning course section activities.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_course_section_routing_test extends local_cleanurls_testcase {
    public function test_it_supports_single_activity_format() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(
            [
                'fullname'  => 'Single Activity Course',
                'shortname' => 'SingleActivity',
                'visible'   => 1,
                'category'  => $category->id,
                'format'    => 'singleactivity',
            ]
        );
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => 'The Single Forum']
        );

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $forum->cmid;
        $expected = 'http://www.example.com/moodle/course/SingleActivity';
        $this->assert_clean_unclean($url, $expected);
    }
}
