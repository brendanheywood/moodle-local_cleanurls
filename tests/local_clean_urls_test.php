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
 * Tests
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');

/**
 * Tests
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_test extends advanced_testcase {

    /** @var A course category */
    private $category;

    /** @var A test course */
    private $course;

    /**
     * Create some test users, courses and modules
     */
    protected function setUp() {
        parent::setup();
        $this->resetAfterTest(true);

        $this->category = $this->getDataGenerator()->create_category(array('name' => 'sciences'));
        $this->category2 = $this->getDataGenerator()->create_category(array('name' => 'compsci', 'parent' => $this->category->id));

        $this->course   = $this->getDataGenerator()->create_course(array('fullname' => 'full#course',
                                                                 'shortname' => 'short#course',
                                                                 'visible' => 1, 'category' => $this->category->id));

        $this->mancourse = $this->getDataGenerator()->create_course(array('fullname' => 'Some course',
                                                                 'shortname' => 'management',
                                                                 'visible' => 1, 'category' => $this->category->id));

        $this->publishcourse = $this->getDataGenerator()->create_course(array('fullname' => 'Full!course@name',
            'shortname' => 'publish',
            'visible' => 1, 'category' => $this->category->id));

        $this->forum = $this->getDataGenerator()->create_module('forum',
            array('course' => $this->course->id, 'name' => 'A!test@FORUM#5'));

        $this->staff = $this->getDataGenerator()->create_user(array('email' => 'head1@example.com', 'username' => 'head1'));
        $this->setUser($this->staff);

        $this->enable_cleaner();
    }

    /**
     * Test the cleaning and uncleaning rules
     */
    public function test_local_cleanurls_simple() {
        $this->resetAfterTest(true);

        set_config('cleaningon', 0, 'local_cleanurls');
        set_config('cleanusernames', 0, 'local_cleanurls');
        purge_all_caches();

        $url = 'http://www.example.com/moodle/course/view.php?id=' . $this->course->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Urls shouldn't be touched if cleaning setting is off");

        $url = 'http://www.example.com/moodle/local/cleanurls/tests/foo.php';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/local/cleanurls/tests/bar',
                            $clean, "Test url should be cleaned even if cleaning is off");

        $this->enable_cleaner();

        $url = 'http://www.example.com/moodle/course/view.php?id=' . $this->publishcourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals(
            'http://www.example.com/moodle/course/view.php?id=' . $this->publishcourse->id,
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

        $url = 'http://www.example.com/moodle/course/view.php?edit=1&id=' . $this->course->id;
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

        $url = 'http://www.example.com/moodle/course/view.php?id=' . $this->course->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/short%23course', $clean, "Clean: course");

        $url = 'http://www.example.com/moodle/course/view.php?name=' . urlencode($this->course->shortname);
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/short%23course', $clean, "Clean: course by name");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals('http://www.example.com/moodle/course/view.php?name=short%2523course', $unclean, "Unclean: course");

        $url = 'http://www.example.com/moodle/course/view.php?id=' . $this->mancourse->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/view.php?id=' . $this->mancourse->id, $clean,
            "Clean: course is ignored because it's shortname clashes with dir or file");

        $url = 'http://www.example.com/moodle/course/index.php';
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/', $clean, "Clean: index.php off url");

        // Nothing to unclean because these urls will get routed directly by apache not router.php.

        $url = 'http://www.example.com/moodle/user/profile.php?id=' . $this->staff->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals($url, $clean, "Not Cleaned: user profile url with username");

        $url = 'http://www.example.com/moodle/user/view.php?id=' . $this->staff->id . '&course=' . $this->course->id;
        $murl = new moodle_url($url);
        $clean = $murl->out(false);
        $this->assertEquals('http://www.example.com/moodle/user/view.php?id=' . $this->staff->id . '&course=' . $this->course->id,
            $clean, "Not Cleaned: user profile url with username inside course");

        set_config('cleanusernames', 1, 'local_cleanurls');

        // If we change url config then we need to throw away the cache.
        purge_all_caches();

        $url = 'http://www.example.com/moodle/mod/forum/user.php?' . 'mode=discussions' . '&id=' . $this->staff->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals(
            'http://www.example.com/moodle/user/head1/discussions',
            $clean,
            "Clean: Forum posts for user page"
        );
        $url = 'http://www.example.com/moodle/mod/forum/user.php?id=' . $this->staff->id . '&mode=discussions';
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

        $url = 'http://www.example.com/moodle/user/profile.php?id=' . $this->staff->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/user/' . $this->staff->username, $clean,
            "Clean: user profile url with username");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals($url, $unclean, "Unclean: user profile url inside course");

        $url = 'http://www.example.com/moodle/user/view.php?id=' . $this->staff->id . '&course=' . $this->course->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/short%23course/user/head1',
            $clean, "Clean: user profile url with username inside course");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out(false);
        $this->assertEquals($url, $unclean, "Unclean: user view url inside course");

        $url = 'http://www.example.com/moodle/user/view.php?course=1&id=' . $this->staff->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/user/' . $this->staff->username . '?course=1',
            $clean, "Clean: user profile url with username inside site course");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out(false);
        $this->assertEquals($url, $unclean, "Unclean: user view url inside site course");

        $url = 'http://www.example.com/moodle/user/index.php?id=' . $this->course->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/'.urlencode($this->course->shortname).'/user', $clean,
            "Clean: user list in course");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out(false);
        $this->assertEquals($url, $unclean, "Unclean: user list inside course");

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $this->forum->cmid;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals(
            'http://www.example.com/moodle/course/short%23course/forum/' . $this->forum->cmid . '-atestforum5',
            $clean, "Clean: Module view page");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out(false);
        $this->assertEquals($url, $unclean, "Unclean: Module view page");

        $url = 'http://www.example.com/moodle/mod/forum/index.php?id=' . $this->course->id;
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals('http://www.example.com/moodle/course/short%23course/forum', $clean, "Clean: course mod index page");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals('http://www.example.com/moodle/mod/forum/index.php?id=' . $this->course->id, $unclean,
            "Unclean: course mod index page");

        $c1 = $this->category->id;
        $c2 = $this->category2->id;
        $url = "http://www.example.com/moodle/course/index.php?categoryid=$c2";
        $murl = new moodle_url($url);
        $clean = $murl->out();
        $this->assertEquals("http://www.example.com/moodle/category/sciences-$c1/compsci-$c2",
                            $clean, "Clean: category index page");

        $unclean = local_cleanurls\clean_moodle_url::unclean($clean)->raw_out();
        $this->assertEquals($url, $unclean, "Unclean: category page");
    }

    private function enable_cleaner() {
        global $CFG;

        $CFG->urlrewriteclass = local_cleanurls\url_rewriter::class;

        set_config('cleaningon', 1, 'local_cleanurls');
        set_config('enableurlrewrite', 1);
        purge_all_caches();
    }
}
