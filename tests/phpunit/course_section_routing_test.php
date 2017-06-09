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

use local_cleanurls\clean_moodle_url;

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
        // TODO -- Implement that back.
        $this->markTestSkipped('Not implemented anymore.');

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
        static::assert_clean_unclean($url, $expected);
    }

    public function test_it_supports_social_format() {
        // No special handling for those URLs as it uses the course URL already.

        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $this->getDataGenerator()->create_course(
            [
                'fullname'  => 'Social Course',
                'shortname' => 'Social',
                'visible'   => 1,
                'category'  => $category->id,
                'format'    => 'social',
            ]
        );

        $url = 'http://www.example.com/moodle/course/view.php?name=Social';
        $expected = 'http://www.example.com/moodle/course/Social';
        static::assert_clean_unclean($url, $expected);
    }

    public function provider_for_simple_section_format_tests() {
        return [
            'topics' => ['topics'],
            'weeks' => ['weeks'],
        ];
    }

    /**
     * @dataProvider provider_for_simple_section_format_tests
     */
    public function test_it_supports_simple_sections_format_with_custom_name($format) {
        global $DB;

        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(
            [
                'fullname'  => 'Simple Section: ' . $format,
                'shortname' => 'simple_' . $format,
                'visible'   => 1,
                'category'  => $category->id,
                'format'    => $format,
            ]
        );

        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => "Forum First Section"]
        );
        list(, $cm) = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);

        // Give a name to the section.
        $DB->update_record('course_sections', (object)['id' => $cm->section, 'name' => 'Custom Section']);

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $cm->id;
        $expected = 'http://www.example.com/moodle/course/simple_' . $format . '/' .
                    "custom-section/{$forum->cmid}-forum-first-section";
        static::assert_clean_unclean($url, $expected);
    }

    public function test_it_does_not_unclean_a_topic_if_section_not_found() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(
            [
                'fullname'  => 'Weekly Course',
                'shortname' => 'Weekly',
                'visible'   => 1,
                'category'  => $category->id,
                'format'    => 'topics',
            ]
        );
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => "Forum First Week"]
        );

        // When a URL cannot uncleaned, it must return the same as the input.
        $url = 'http://www.example.com/moodle/course/Weekly/' .
                    "this-section-does-not-exists/{$forum->cmid}-forum-first-week";
        $unclean = clean_moodle_url::unclean($url);
        self::assertSame($url, $unclean->out());
    }

    public function test_it_supports_format_callbacks() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(
            [
                'fullname'  => 'Format Hook',
                'shortname' => 'format_hook',
                'visible'   => 1,
                'category'  => $category->id,
                'format'    => 'cleanurls',
            ]
        );

        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => "Forum First Section"]
        );

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $forum->cmid;
        $expected = 'http://www.example.com/moodle/course/format_hook/customurlforforums/' .
                    "My{$forum->cmid}";
        static::assert_clean_unclean($url, $expected);
        $this->resetDebugging(); // There will be a debugging regarding the invalid 'cleanurls'.
    }

    public function test_it_supports_format_callbacks_at_course_level() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(
            [
                'fullname'  => 'Format Hook',
                'shortname' => 'format_hook',
                'visible'   => 1,
                'category'  => $category->id,
                'format'    => 'cleanurls',
            ]
        );

        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => "Forum First Section"]
        );

        $url = 'http://www.example.com/moodle/course/view.php?name=format_hook';
        $expected = 'http://www.example.com/moodle/course/format_hook';
        static::assert_clean_unclean($url, $expected);
        $this->resetDebugging(); // There will be a debugging regarding the invalid 'cleanurls'.
    }
}
