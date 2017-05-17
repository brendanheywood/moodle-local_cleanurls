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
 * Tests for cleaner and uncleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\clean_moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for cleaner and uncleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleaner_uncleaner_test extends advanced_testcase {
    public function test_it_always_cleans_the_test_url() {
        // Test with cleaning on.
        set_config('cleaningon', true, 'local_cleanurls');
        $this->assert_clean_unclean('/local/cleanurls/tests/foo.php',
                                    'http://www.example.com/moodle/local/cleanurls/tests/bar');

        // Test with cleaning off.
        set_config('cleaningon', false, 'local_cleanurls');
        $this->assert_clean_unclean('/local/cleanurls/tests/foo.php',
                                    'http://www.example.com/moodle/local/cleanurls/tests/bar');
    }

    public function test_it_cannot_clean_if_destination_is_a_directory() {
        global $CFG;
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'How to use ajax',
                                                            'shortname' => 'ajax',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        self::assertTrue(is_dir($CFG->dirroot.'/course/ajax'), 'Directory required for the test.');

        $url = 'http://www.example.com/moodle/course/view.php?id='.urlencode($course->id);
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_cannot_clean_if_destination_is_a_php_file() {
        global $CFG;
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'How to enrol to a course',
                                                            'shortname' => 'enrol',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        self::assertTrue(is_file($CFG->dirroot.'/course/enrol.php'), 'File required for the test.');
        $url = 'http://www.example.com/moodle/course/view.php?id='.urlencode($course->id);
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_cleans_category_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);

        $this->assert_clean_unclean('http://www.example.com/moodle/course/index.php?categoryid='.$category->id,
                                    'http://www.example.com/moodle/category/category-'.$category->id);
    }

    public function test_it_cleans_course_module_view_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'course long name',
                                                            'shortname' => 'shortname',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id,
                                                                    'name'   => 'A Test Forum']);

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id='.$forum->cmid;
        $expected = 'http://www.example.com/moodle/course/shortname/forum/'.$forum->cmid.'-a-test-forum';
        $this->assert_clean_unclean($url, $expected);
    }

    public function test_it_cleans_course_modules_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'course long name',
                                                            'shortname' => 'shortname',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        $this->assert_clean_unclean('http://www.example.com/moodle/mod/forum/index.php?id='.$course->id,
                                    'http://www.example.com/moodle/course/shortname/forum');
    }

    public function test_it_cleans_course_urls_by_id() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'full name of the course',
                                                            'shortname' => 'shortname',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        $this->assert_clean_unclean('http://www.example.com/moodle/course/view.php?id='.$course->id,
                                    'http://www.example.com/moodle/course/shortname',
                                    'http://www.example.com/moodle/course/view.php?name=shortname');
    }

    public function test_it_cleans_course_with_hash_in_shortname() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'full name of the course #3',
                                                            'shortname' => 'short#name',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        $this->assert_clean_unclean('http://www.example.com/moodle/course/view.php?id='.$course->id,
                                    'http://www.example.com/moodle/course/short%23name',
                                    'http://www.example.com/moodle/course/view.php?name=short%2523name');
    }

    public function test_it_cleans_course_urls_by_name() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $this->getDataGenerator()->create_course(['fullname'  => 'full name',
                                                  'shortname' => 'theshortname',
                                                  'visible'   => 1,
                                                  'category'  => $category->id]);

        $this->assert_clean_unclean('http://www.example.com/moodle/course/view.php?name=theshortname',
                                    'http://www.example.com/moodle/course/theshortname');
    }

    public function test_it_cleans_course_users_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'a course name',
                                                            'shortname' => 'shortcoursename',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        $this->assert_clean_unclean('http://www.example.com/moodle/user/index.php?id='.$course->id,
                                    'http://www.example.com/moodle/course/shortcoursename/user');
    }

    public function test_it_cleans_subcategory_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $subcategory = $this->getDataGenerator()->create_category(['name'   => 'subcategory',
                                                                   'parent' => $category->id]);

        $url = 'http://www.example.com/moodle/course/index.php?categoryid='.$subcategory->id;
        $expected = 'http://www.example.com/moodle/category/category-'.$category->id.'/subcategory-'.$subcategory->id;
        $this->assert_clean_unclean($url, $expected);
    }

    public function test_it_cleans_username_in_course() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'a course',
                                                            'shortname' => 'mycourse',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        $this->assert_clean_unclean(
            "http://www.example.com/moodle/user/view.php?course=1&id={$user->id}&course={$course->id}",
            'http://www.example.com/moodle/course/mycourse/user/theusername'
        );
    }

    public function test_it_cleans_username_in_forum_discussion() {
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        $this->assert_clean_unclean('http://www.example.com/moodle/mod/forum/user.php?mode=discussions&id='.$user->id,
                                    'http://www.example.com/moodle/user/theusername/discussions');
    }

    public function test_it_cleans_username_in_site_course() {
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        $this->assert_clean_unclean('http://www.example.com/moodle/user/view.php?course=1&id='.$user->id,
                                    'http://www.example.com/moodle/user/theusername?course=1');
    }

    public function test_it_cleans_username_urls() {
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        $this->assert_clean_unclean('http://www.example.com/moodle/user/profile.php?id='.$user->id,
                                    'http://www.example.com/moodle/user/theusername');
    }

    public function test_it_does_not_clean_draftfile_urls() {
        $url = 'http://moodle.test/moodle/draftfile.php/5/user/draft/949704188/daniel-roperto.jpg';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_help_urls() {
        $url = 'http://www.example.com/moodle/help.php?blah=foo';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_lib_urls() {
        $url = 'http://www.example.com/moodle/lib/whatever.php';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_plugin_urls() {
        $url = 'http://www.example.com/moodle/pluginfile.php/12345/foo/bar';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_pluginfile_urls() {
        $url = 'http://www.example.com/moodle/pluginfile.php/12345/foo/bar';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_theme_urls() {
        $url = 'http://www.example.com/moodle/theme/whatever.php';
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_username_in_forum_discussion_if_not_discussions_mode() {
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        $url = 'http://www.example.com/moodle/mod/forum/user.php?mode=somethingelse&id='.$user->id;
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_does_not_clean_usernames_if_config_disabled() {
        $user = $this->getDataGenerator()->create_user(['email'    => 'someone@example.com',
                                                        'username' => 'theusername']);

        set_config('cleanusernames', false, 'local_cleanurls');
        $url = 'http://www.example.com/moodle/user/profile.php?id='.$user->id;
        $this->assert_clean_unclean($url, $url);
    }

    public function test_it_returns_the_same_url_if_cleaning_is_off() {
        set_config('cleaningon', false, 'local_cleanurls');

        $url = 'http://www.example.com/moodle/cache/disabled-test.php';

        cache::make('local_cleanurls', 'outgoing')->set($url, 'http://www.example.com/moodle/disabledcachedurl');
        $this->assert_clean_unclean($url, $url); // Cleaning disabled, should not get cached version.
    }

    public function test_it_should_use_a_cache() {
        $url = 'http://www.example.com/moodle/cache/test.php';
        $cached = 'http://www.example.com/moodle/cachedurl.php';

        cache::make('local_cleanurls', 'outgoing')->set($url, $cached);
        $this->assert_clean_unclean($url, $cached, $cached);
    }

    protected function setUp() {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest(true);
        $CFG->urlrewriteclass = local_cleanurls\url_rewriter::class;
        set_config('enableurlrewrite', 1);
        set_config('cleaningon', true, 'local_cleanurls');
        set_config('cleanusernames', true, 'local_cleanurls');
    }

    /**
     * Ensures the input URL can be cleaned and possibly uncleaned.
     *
     * If $input and $expectedcleaned are the same, it means that the URL is not supposed to be cleaned
     * and it will not test the uncleaning.
     *
     * @param string      $input             URL to test.
     * @param string|null $expectedcleaned   How is the URL supposed to be cleaned.
     * @param string|null $expecteduncleaned If not provided, it should unclean back to the input URL.
     */
    private function assert_clean_unclean($input, $expectedcleaned = null, $expecteduncleaned = null) {
        $inputurl = new moodle_url($input);
        $clean = clean_moodle_url::clean($inputurl);
        self::assertInstanceOf(moodle_url::class, $clean);
        self::assertSame($expectedcleaned, $clean->out(false), 'Failed CLEANING.');

        if ($input === $expectedcleaned) {
            return; // The URL was not cleaned, do not test uncleaning it.
        }

        $unclean = clean_moodle_url::unclean($clean);
        self::assertInstanceOf(moodle_url::class, $unclean);

        if (is_null($expecteduncleaned)) {
            // Ensure test dos not fail because of parameter order.
            foreach ([$inputurl, $unclean] as $url) {
                $params = $url->params();
                ksort($params);
                $url->remove_all_params();
                $url->params($params);
            }
            $expecteduncleaned = $inputurl->raw_out(false);
        }

        self::assertSame($expecteduncleaned, $unclean->raw_out(false), 'Failed UNCLEANING.');
    }

    public function test_local_cleanurls_simple() {
        global $CFG;

        // Create some test users, courses and modules.
        $this->resetAfterTest(true);

        $thiscategory = $this->getDataGenerator()->create_category(array('name' => 'sciences'));
        $thiscategory2 = $this->getDataGenerator()->create_category(array('name' => 'compsci', 'parent' => $thiscategory->id));

        $thiscourse   = $this->getDataGenerator()->create_course(array('fullname' => 'full#course',
                                                                       'shortname' => 'short#course',
                                                                       'visible' => 1, 'category' => $thiscategory->id));

        $thismancourse = $this->getDataGenerator()->create_course(array('fullname' => 'Some course',
                                                                        'shortname' => 'management',
                                                                        'visible' => 1, 'category' => $thiscategory->id));

        $thispublishcourse = $this->getDataGenerator()->create_course(array('fullname' => 'Full!course@name',
                                                                            'shortname' => 'publish',
                                                                            'visible' => 1, 'category' => $thiscategory->id));

        $thisforum = $this->getDataGenerator()->create_module('forum',
                                                              array('course' => $thiscourse->id, 'name' => 'A!test@FORUM#5'));

        $thisstaff = $this->getDataGenerator()->create_user(array('email' => 'head1@example.com', 'username' => 'head1'));
        $this->setUser($thisstaff);

        $CFG->urlrewriteclass = local_cleanurls\url_rewriter::class;
        set_config('cleaningon', 1, 'local_cleanurls');
        set_config('enableurlrewrite', 1);
        purge_all_caches();

        // Test the cleaning and uncleaning rules.
        set_config('cleaningon', 0, 'local_cleanurls');
        set_config('cleanusernames', 0, 'local_cleanurls');
        purge_all_caches();

        $url = 'http://www.example.com/moodle/course/view.php?id=' . $thiscourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Urls shouldn't be touched if cleaning setting is off");

        $url = 'http://www.example.com/moodle/local/cleanurls/tests/foo.php';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/local/cleanurls/tests/bar',
                            $clean, "Test url should be cleaned even if cleaning is off");

        $CFG->urlrewriteclass = local_cleanurls\url_rewriter::class;
        set_config('cleaningon', 1, 'local_cleanurls');
        set_config('enableurlrewrite', 1);
        purge_all_caches();

        $url = 'http://www.example.com/moodle/course/view.php?id=' . $thispublishcourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals(
            'http://www.example.com/moodle/course/view.php?id=' . $thispublishcourse->id,
            $clean,
            "Urls to course with name \"publish\" are not supposed to be cleaned because they clash with a directory."
        );

        $url = 'http://www.example.com/moodle/theme/whatever.php';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Nothing: Theme files should not be touched");

        $url = 'http://www.example.com/moodle/lib/whatever.php';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Nothing: Lib files should not be touched");

        $url = 'http://www.example.com/moodle/help.php?blah=foo';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Nothing: Help files should not be touched");

        $url = 'http://www.example.com/moodle/pluginfile.php/12345/foo/bar';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Nothing: Plugin files should not be touched");

        $url = 'http://moodle.test/moodle/draftfile.php/5/user/draft/949704188/daniel-roperto.jpg';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Nothing: File draftfile.php should not be touched");

        $url = 'http://www.example.com/moodle/course/view.php?edit=1&id=' . $thiscourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/short%23course?edit=1', $clean, "Clean: course with param");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out(false);
        $this->assertEquals('http://www.example.com/moodle/course/view.php?edit=1&name=short%2523course', $unclean,
                            "Unclean: course with param");

        $url = 'http://www.example.com/moodle/foo/bar.php';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/foo/bar.php', $clean, "Clean: Don't remove php extension");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals($url, $unclean, "Unclean: Put php extension back");

        $url = 'http://www.example.com/moodle/foo/bar.php?ding=pop';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/foo/bar.php?ding=pop', $clean,
                            "Clean: Do not remove php extension with params");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals($url, $unclean, "Unclean: Put php extension back with params");

        $url = 'http://www.example.com/moodle/foo/bar.php#hash';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/foo/bar.php#hash', $clean,
                            "Clean: Don't remove php extension with hash");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals($url, $unclean, "Unclean: Put php extension back with hash");

        $url = 'http://www.example.com/moodle/course/index.php?foo=bar#hash';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/?foo=bar#hash', $clean, "Clean: Remove index");

        $url = 'http://www.example.com/moodle/admin/settings.php?section=local_cleanurls';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Clean: Don't clean any admin paths");

        $url = 'http://www.example.com/moodle/auth/foo/bar.php';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Clean: Don't clean any auth paths");

        $url = 'http://www.example.com/moodle/course/view.php?id=' . $thiscourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/short%23course', $clean, "Clean: course");

        $url = 'http://www.example.com/moodle/course/view.php?name=' . urlencode($thiscourse->shortname);
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/short%23course', $clean, "Clean: course by name");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals('http://www.example.com/moodle/course/view.php?name=short%2523course', $unclean, "Unclean: course");

        $url = 'http://www.example.com/moodle/course/view.php?id=' . $thismancourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/view.php?id=' . $thismancourse->id, $clean,
                            "Clean: course is ignored because it's shortname clashes with dir or file");

        $url = 'http://www.example.com/moodle/course/index.php';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/', $clean, "Clean: index.php off url");

        // Nothing to unclean because these urls will get routed directly by apache not router.php.

        $url = 'http://www.example.com/moodle/user/profile.php?id=' . $thisstaff->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Not Cleaned: user profile url with username");

        $url = 'http://www.example.com/moodle/user/view.php?id=' . $thisstaff->id . '&course=' . $thiscourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out(false);
        $this->assertEquals('http://www.example.com/moodle/user/view.php?id=' . $thisstaff->id . '&course=' . $thiscourse->id,
                            $clean, "Not Cleaned: user profile url with username inside course");

        set_config('cleanusernames', 1, 'local_cleanurls');

        // If we change url config then we need to throw away the cache.
        purge_all_caches();

        $url = 'http://www.example.com/moodle/mod/forum/user.php?' . 'mode=discussions' . '&id=' . $thisstaff->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals(
            'http://www.example.com/moodle/user/head1/discussions',
            $clean,
            "Clean: Forum posts for user page"
        );
        $url = 'http://www.example.com/moodle/mod/forum/user.php?id=' . $thisstaff->id . '&mode=discussions';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals(
            'http://www.example.com/moodle/user/head1/discussions',
            $clean,
            "Clean: Forum posts for user page"
        );
        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out(false);
        $this->assertEquals(
            $url,
            $unclean,
            "Unclean: Forum posts for user page"
        );

        $url = 'http://www.example.com/moodle/user/profile.php?id=' . $thisstaff->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/user/' . $thisstaff->username, $clean,
                            "Clean: user profile url with username");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals($url, $unclean, "Unclean: user profile url inside course");

        $url = 'http://www.example.com/moodle/user/view.php?id=' . $thisstaff->id . '&course=' . $thiscourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/short%23course/user/head1',
                            $clean, "Clean: user profile url with username inside course");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out(false);
        $this->assertEquals($url, $unclean, "Unclean: user view url inside course");

        $url = 'http://www.example.com/moodle/user/view.php?course=1&id=' . $thisstaff->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/user/' . $thisstaff->username . '?course=1',
                            $clean, "Clean: user profile url with username inside site course");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out(false);
        $this->assertEquals($url, $unclean, "Unclean: user view url inside site course");

        $url = 'http://www.example.com/moodle/user/index.php?id=' . $thiscourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/'.urlencode($thiscourse->shortname).'/user', $clean,
                            "Clean: user list in course");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out(false);
        $this->assertEquals($url, $unclean, "Unclean: user list inside course");

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $thisforum->cmid;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals(
            'http://www.example.com/moodle/course/short%23course/forum/' . $thisforum->cmid . '-atestforum5',
            $clean, "Clean: Module view page");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out(false);
        $this->assertEquals($url, $unclean, "Unclean: Module view page");

        $url = 'http://www.example.com/moodle/mod/forum/index.php?id=' . $thiscourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/short%23course/forum', $clean, "Clean: course mod index page");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals('http://www.example.com/moodle/mod/forum/index.php?id=' . $thiscourse->id, $unclean,
                            "Unclean: course mod index page");

        $c1 = $thiscategory->id;
        $c2 = $thiscategory2->id;
        $url = "http://www.example.com/moodle/course/index.php?categoryid=$c2";
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals("http://www.example.com/moodle/category/sciences-$c1/compsci-$c2",
                            $clean, "Clean: category index page");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals($url, $unclean, "Unclean: category page");
    }
}
