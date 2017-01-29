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
 * Class cleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls;

use cache;
use cache_application;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class cleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleaner {
    /**
     * Takes a moodle_url and either returns a clean_moodle_url object with
     * clean cloned properties or if nothing is done the original object.
     *
     * @param moodle_url $originalurl a url to clean
     * @return moodle_url
     */
    public static function clean(moodle_url $originalurl) {
        $cleaner = new self();
        $cleaner->originalurl = $originalurl;
        $cleaner->execute();
        return $cleaner->cleanedurl;
    }

    /** @var cache_application */
    private $cache;

    /** @var moodle_url */
    private $cleanedurl;

    /** @var stdClass */
    private $config;

    /** @var string */
    private $moodlepath;

    /** @var string */
    private $originalpath;

    /** @var moodle_url */
    private $originalurl;

    /** @var string */
    private $originalurlraw;

    /** @var string[] */
    private $params;

    /** @var string */
    private $path;

    private function check_cached() {
        $this->cache = cache::make('local_cleanurls', 'outgoing');
        $cached = $this->cache->get($this->originalurlraw);
        if ($cached) {
            $clean = new clean_moodle_url($cached);
            clean_moodle_url::log("Found cached: {$this->originalurlraw} => {$cached}");
            $this->cleanedurl = $clean;
            return true;
        }
        return false;
    }

    private function check_cleaner_disabled() {
        // Check if cleaning is on.
        if (empty($this->config->cleaningon)) {
            clean_moodle_url::log("Cleaning is not on");
            $this->cleanedurl = $this->originalurl;
            return true;
        }
        return false;
    }

    private function check_test_url() {
        // This is a special case which will always be cleaned even if the
        // cleaner is off, used for confirming that it all works.
        if (substr($this->originalpath, -30) == '/local/cleanurls/tests/foo.php') {
            clean_moodle_url::log("Rewrite test url");
            $this->cleanedurl = new clean_moodle_url('/local/cleanurls/tests/bar');
            return true;
        }
        return false;
    }

    private function clean_category() {
        global $DB;

        if (!isset($this->params['categoryid'])) {
            return false;
        }

        // Clean up category list urls.
        $catid = $this->params['categoryid'];
        $this->path = '';

        // Grab all ancestor slugs.
        while ($catid) {
            $cat = $DB->get_record('course_categories', array('id' => $catid));
            $slug = clean_moodle_url::sluggify($cat->name, false);
            $this->path = '/' . $slug . '-' . $catid . $this->path;
            $catid = $cat->parent;
        }
        $this->path = '/category' .  $this->path;
        unset ($this->params['categoryid']);
        clean_moodle_url::log("Rewrite category page");
        return true;
    }

    private function clean_course_by_id() {
        global $DB, $CFG;

        if (empty($this->params['id'])) {
            return false;
        }

        $slug = $DB->get_field('course', 'shortname', array('id' => $this->params['id'] ));
        $slug = urlencode($slug);
        $newpath = "/course/$slug";
        if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
            $this->path = $newpath;
            unset ($this->params['id']);
            clean_moodle_url::log("Rewrite course");
        }
        return true;
    }

    private function clean_course_by_name() {
        global $CFG;
        if (empty($this->params['name'])) {
            return false;
        }

        $slug = urlencode($this->params['name']);
        $newpath = "/course/$slug";
        if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
            $this->path = $newpath;
            unset ($this->params['name']);
            clean_moodle_url::log("Rewrite course by name.");
        }
        return true;
    }

    private function clean_course_module_view($mod) {
        global $CFG;
        // Clean up mod view pages.

        if (empty($this->params['id'])) {
            return false;
        }

        $id = $this->params['id'];
        list ($course, $cm) = get_course_and_cm_from_cmid($id, $mod);

        $slug = clean_moodle_url::sluggify($cm->name, true);
        $shortcode = urlencode($course->shortname);

        $newpath = "/course/$shortcode/$mod/$id$slug";
        if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
            $this->path = $newpath;
            unset ($this->params['id']);

            clean_moodle_url::log("Rewrite mod view: $this->path");
        }
        return true;
    }

    private function clean_course_modules($mod) {
        global $DB, $CFG;
        // Clean up mod view pages /index has already been removed earlier.
        if (empty($this->params['id'])) {
            return false;
        }

        $slug = $DB->get_field('course', 'shortname', array('id' => $this->params['id'] ));
        $slug = urlencode($slug);
        $newpath = "/course/$slug/$mod";
        if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
            $this->path = $newpath;
            unset ($this->params['id']);

            clean_moodle_url::log("Rewrite mod view: $this->path");
        }
        return true;
    }

    private function clean_course_users() {
        global $DB, $CFG;

        if (empty($this->params['id'])) {
            return false;
        }


        // Clean up user course list urls.

        $slug = $DB->get_field('course', 'shortname', array('id' => $this->params['id'] ));
        $slug = urlencode($slug);
        $newpath = "/course/$slug/user";
        if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
            $this->path = $newpath;
            unset ($this->params['id']);

            clean_moodle_url::log("Rewrite user profile");
        }
        return true;
    }

    private function clean_user_in_course() {
        global $DB, $CFG;

        if (empty($this->params['id']) || empty($this->params['course'])) {
            return false;
        }

        // Clean up user profile urls inside course.
        $slug = $DB->get_field('user', 'username', array('id' => $this->params['id'] ));
        $slug = urlencode($slug);
        $newpath = "/user/$slug";

        if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
            $this->path = $newpath;

            if ($this->params['course'] != 1) {
                $slug = $DB->get_field('course', 'shortname', array('id' => $this->params['course'] ));
                $slug = urlencode($slug);
                $this->path = "/course/$slug$this->path";
                unset ($this->params['course']);
            }
            unset ($this->params['id']);
            clean_moodle_url::log("Rewrite user profile");
        }

        return true;
    }

    private function clean_user_in_forum() {
        global $CFG, $DB;

        $userid = empty($this->params['id']) ? null : $this->params['id'];
        $mode = empty($this->params['mode']) ? null : $this->params['mode'];
        if (is_null($userid) || ($mode != 'discussions')) {
            return false;
        }

        // Clean up user profile urls in forum posts
        // ie http://moodle.com/mod/forum/user.php?id=123&mode=discussions
        // should become http://moodle.com/user/username/discussions .
        $slug = $DB->get_field('user', 'username', array('id' => $this->params['id']));
        $slug = urlencode($slug);
        $newpath = "/user/$slug";
        if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
            $this->path = $newpath . '/' . $this->params['mode'];
            unset ($this->params['id']);
            unset ($this->params['mode']);
            clean_moodle_url::log("Rewrite user profile");
        }

        return true;
    }

    private function clean_user_profile() {
        global $CFG, $DB;
        if (empty($this->params['id'])) {
            return false;
        }
        // In profile urls.
        $slug = $DB->get_field('user', 'username', array('id' => $this->params['id'] ));
        $slug = urlencode($slug);
        $newpath = "/user/$slug";

        if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
            $this->path = $newpath;
            unset ($this->params['id']);
            clean_moodle_url::log("Rewrite user profile");
        }

        return true;
    }

    private function create_cleaned_url() {
        // Add back moodle path.
        $this->path = $this->moodlepath.$this->path;

        // URL was not rewritten.
        if ($this->path == $this->originalpath) {
            $this->cleanedurl = $this->originalurl;
            return;
        }

        // Create new URL.
        $this->cleanedurl = new clean_moodle_url($this->originalurl);
        $this->cleanedurl->set_path($this->path);
        $this->cleanedurl->remove_all_params();
        $this->cleanedurl->params($this->params);

        // Cache and log it.
        $cleaned = $this->cleanedurl->raw_out(false);
        $this->cache->set($this->originalurlraw, $cleaned);
        clean_moodle_url::log("Clean: {$cleaned}");
    }

    private function execute() {
        $this->originalurlraw = $this->originalurl->raw_out(false);
        $this->originalpath = $this->originalurl->get_path(false);
        $this->cleanedurl = null;
        $this->config = get_config('local_cleanurls');

        // The order of the checks below is important.
        if ($this->check_test_url() || $this->check_cleaner_disabled() || $this->check_cached()) {
            return;
        }

        clean_moodle_url::log("Cleaning: {$this->originalurlraw} Path is: {$this->originalpath}");

        $this->path = $this->originalpath;
        $this->params = $this->originalurl->params();
        $this->extract_moodle_path();

        $this->remove_indexphp();

        $this->clean_path();

        $this->create_cleaned_url();
    }

    private function clean_path() {
        switch ($this->path) {
            case '/course/view.php':
                $this->clean_course_by_id() || $this->clean_course_by_name();
                return;
            case '/user/':
                $this->clean_course_users();
                return;
            case '/course/':
                $this->clean_category();
                return;
        }

        if (preg_match('#^/mod/(\w+)/$#', $this->path, $matches)) {
            $this->clean_course_modules($matches[1]);
            return;
        }

        if (preg_match('#^/mod/(\w+)/view.php$#', $this->path, $matches)) {
            $this->clean_course_module_view($matches[1]);
            return;
        }

        if (!empty($this->config->cleanusernames) && $this->config->cleanusernames) {
            $this->clean_user();
            return;
        }
    }

    private function clean_user() {
        switch ($this->path) {
            case '/user/profile.php':
                $this->clean_user_profile();
                return;
            case '/user/view.php':
                $this->clean_user_in_course();
                return;
            case '/mod/forum/user.php':
                $this->clean_user_in_forum();
                return;
        }
    }

    private function remove_indexphp() {
        // Remove /index.php from end.
        if (substr($this->path, -10) == '/index.php') {
            clean_moodle_url::log("Removing /index.php");
            $this->path = substr($this->path, 0, -9);
        }
    }

    private function extract_moodle_path() {
        global $CFG;

        // If moodle is installed inside a dir like example.com/somepath/moodle/index.php
        // then remove the 'somepath/moodle' part and store for later.
        $slashstart = strlen(parse_url($CFG->wwwroot, PHP_URL_SCHEME)) + 3;
        $slashpos = strpos($CFG->wwwroot, '/', $slashstart);

        $this->moodlepath = '';
        if ($slashpos) {
            $this->moodlepath = substr($CFG->wwwroot, $slashpos);
            $this->path = substr($this->path, strlen($this->moodlepath));
            clean_moodle_url::log("Removed wwwroot ({$this->moodlepath}) from path: {$this->path}");
        }
    }
}
