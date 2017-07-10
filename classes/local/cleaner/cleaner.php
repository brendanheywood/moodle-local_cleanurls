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

namespace local_cleanurls\local\cleaner;

use cache;
use cache_application;
use cm_info;
use local_cleanurls\clean_moodle_url;
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
    public static function get_section_number_from_id($course, $sectionid) {
        $info = get_fast_modinfo($course);
        foreach ($info->get_section_info_all() as $section) {
            if ($section->id == $sectionid) {
                return $section->section;
            }
        }
        return null;
    }

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

    private function check_path_allowed($path) {
        global $CFG;

        return (!is_dir($CFG->dirroot.$path) && !is_file($CFG->dirroot.$path.".php"));
    }

    private function check_test_url() {
        // This is a special case which will always be cleaned even if the
        // cleaner is off, used for confirming that it all works.
        if (substr($this->originalpath, -30) == '/local/cleanurls/tests/foo.php') {
            clean_moodle_url::log("Rewrite test url");
            $this->cleanedurl = new clean_moodle_url('/local/cleanurls/tests/bar');
            return true;
        }
        if (substr($this->originalpath, -42) == '/local/cleanurls/tests/webserver/index.php') {
            clean_moodle_url::log("Rewrite test url");
            $this->cleanedurl = new clean_moodle_url('/local/cleanurls/tests/webcheck');
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
        $newpath = '';

        // Grab all ancestor slugs.
        while ($catid) {
            $cat = $DB->get_record('course_categories', ['id' => $catid]);
            $slug = clean_moodle_url::sluggify($cat->name, false);
            $newpath = '/'.$slug.'-'.$catid.$newpath;
            $catid = $cat->parent;
        }
        $newpath = '/category'.$newpath;

        if ($this->check_path_allowed($newpath)) {
            $this->path = $newpath;
            unset ($this->params['categoryid']);
            clean_moodle_url::log("Rewrite category page");
        }

        return true;
    }

    private function clean_course_by_id() {
        if (empty($this->params['id'])) {
            return null;
        }

        $course = get_course($this->params['id']);

        $newpath = '/course/' . urlencode($course->shortname);
        if ($this->check_path_allowed($newpath)) {
            $this->path = $newpath;
            unset($this->params['id']);
            clean_moodle_url::log("Rewrite course");
        }

        return $course;
    }

    private function clean_course_by_name() {
        global $DB;

        if (empty($this->params['name'])) {
            return false;
        }

        $courseid = $DB->get_field('course', 'id', ['shortname' => $this->params['name']]);
        $course = get_course($courseid);

        $newpath = '/course/' . urlencode($course->shortname);
        if ($this->check_path_allowed($newpath)) {
            $this->path = $newpath;
            unset($this->params['name']);
            clean_moodle_url::log("Rewrite course by name.");
        }

        return $course;
    }

    private function clean_course_module_view($mod) {
        if (empty($this->params['id'])) {
            return false;
        }

        $id = $this->params['id'];
        list($course, $cm) = get_course_and_cm_from_cmid($id, $mod);

        $subpath = $this->clean_course_module_view_format($course, $cm);
        $shortname = urlencode($course->shortname);
        $newpath = "/course/{$shortname}{$subpath}";

        if ($this->check_path_allowed($newpath)) {
            $this->path = $newpath;
            unset($this->params['id']);
            clean_moodle_url::log("Rewrite mod view: {$this->path}");
        }

        return true;
    }

    private function clean_course_module_view_format(stdClass $course, cm_info $cm) {
        // Try to find a clean handler for the course format.
        $classname = clean_moodle_url::get_format_support($course->format);
        if (!is_null($classname)) {
            return '/' . $classname::get_courseformat_module_clean_subpath($course, $cm);
        }

        // Default behaviour.
        $title = clean_moodle_url::sluggify($cm->name, true);
        return "/{$cm->modname}/{$cm->id}{$title}";
    }

    private function clean_course_modules($mod) {
        global $DB;

        if (empty($this->params['id'])) {
            return false;
        }

        $slug = $DB->get_field('course', 'shortname', ['id' => $this->params['id']]);
        $slug = urlencode($slug);
        $newpath = "/course/{$slug}/{$mod}";
        if ($this->check_path_allowed($newpath)) {
            $this->path = $newpath;
            unset($this->params['id']);
            clean_moodle_url::log("Rewrite mod view: {$this->path}");
        }

        return true;
    }

    private function clean_course_users() {
        global $DB;

        if (empty($this->params['id'])) {
            return false;
        }

        $newpath = $DB->get_field('course', 'shortname', ['id' => $this->params['id']]);
        $newpath = '/course/'.urlencode($newpath).'/user';
        if ($this->check_path_allowed($newpath)) {
            $this->path = $newpath;
            unset($this->params['id']);
            clean_moodle_url::log('Rewrite course users');
        }

        return true;
    }

    private function clean_user_in_course() {
        global $DB;

        if (empty($this->params['id']) || empty($this->params['course'])) {
            return false;
        }

        $username = $DB->get_field('user', 'username', ['id' => $this->params['id']]);
        $username = urlencode($username);
        $newpath = "/user/{$username}";

        if ($this->params['course'] != 1) {
            $coursename = $DB->get_field('course', 'shortname', ['id' => $this->params['course']]);
            $coursename = urlencode($coursename);
            $newpath = "/course/{$coursename}{$newpath}";
            unset($this->params['course']);
        }

        if ($this->check_path_allowed($newpath)) {
            $this->path = $newpath;
            unset($this->params['id']);
            clean_moodle_url::log('Rewrite user profile in course');
        }

        return true;
    }

    private function clean_user_in_forum() {
        global $DB;

        $userid = empty($this->params['id']) ? null : $this->params['id'];
        $mode = empty($this->params['mode']) ? null : $this->params['mode'];
        if (is_null($userid) || ($mode != 'discussions')) {
            return false;
        }

        $username = $DB->get_field('user', 'username', ['id' => $this->params['id']]);
        $username = urlencode($username);
        $mode = urlencode($mode);
        $newpath = "/user/{$username}/{$mode}";

        if ($this->check_path_allowed($newpath)) {
            $this->path = $newpath;
            unset($this->params['id']);
            unset ($this->params['mode']);
            clean_moodle_url::log('Rewrite user profile in forum');
        }

        return true;
    }

    private function clean_user_profile() {
        global $DB;

        if (empty($this->params['id'])) {
            return false;
        }
        $newpath = $DB->get_field('user', 'username', ['id' => $this->params['id']]);
        $newpath = '/user/'.urlencode($newpath);
        if ($this->check_path_allowed($newpath)) {
            $this->path = $newpath;
            unset($this->params['id']);
            clean_moodle_url::log('Rewrite user profile');
        }

        return true;
    }

    private function create_cleaned_url() {
        // Add back moodle path.
        $this->path = $this->moodlepath . '/' . ltrim($this->path, '/');

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
        clean_moodle_url::extract_moodle_path($this->path, $this->moodlepath);
        $this->remove_indexphp();

        $this->clean_path();

        $this->create_cleaned_url();
    }

    private function clean_path() {
        switch ($this->path) {
            case '/course/view.php':
                $this->clean_course();
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

    private function clean_course() {
        $course = $this->clean_course_by_id();
        $course = $course ?: $this->clean_course_by_name();

        if (is_null($course)) {
            return;
        }

        $classname = clean_moodle_url::get_format_support($course->format);
        if (is_null($classname)) {
            return;
        }

        $section = null;
        if (array_key_exists('sectionid', $this->params)) {
            $section = self::get_section_number_from_id($course, $this->params['sectionid']);
        }
        if (array_key_exists('section', $this->params)) {
            $section = $this->params['section'];
        }
        if (is_null($section)) {
            return;
        }

        $sectionpath = $classname::get_courseformat_section_clean_subpath($course, $section);

        if (!is_null($sectionpath)) {
            $this->path .= $sectionpath;
            unset($this->params['section']);
            unset($this->params['sectionid']);
        }
    }
}
