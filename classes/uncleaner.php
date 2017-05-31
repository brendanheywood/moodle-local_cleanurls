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
 * Class uncleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls;

use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class uncleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class uncleaner {
    /**
     * Takes a string and converts it into an unclean moodle_url object
     *
     * @param string $clean the incoming url
     * @return moodle_url the original moodle_url
     */
    public static function unclean($clean) {
        $uncleaner = new self();
        $uncleaner->clean = $clean;
        $uncleaner->execute();
        return $uncleaner->uncleanurl;
    }

    /** @var string */
    private $clean;

    /** @var clean_moodle_url */
    private $cleanurl;

    /** @var string */
    private $cleanurlraw;

    /** @var string */
    private $moodlepath;

    /** @var string[] */
    private $params;

    /** @var string */
    private $path;

    /** @var clean_moodle_url */
    private $uncleanurl;

    private function create_uncleaned_url() {
        // Put back .php extension if doesn't end in .php or slash.
        if (substr($this->path, -1, 1) !== '/' && substr($this->path, -4) !== '.php') {
            $this->path .= '.php';
        }

        clean_moodle_url::log("Rewritten to: {$this->path}");
        $this->uncleanurl = new clean_moodle_url($this->cleanurl);;
        $this->uncleanurl->set_path($this->moodlepath.$this->path);
        $this->uncleanurl->remove_all_params();
        $this->uncleanurl->params($this->params);
    }

    private function execute() {
        $this->cleanurl = new clean_moodle_url($this->clean);
        $this->cleanurlraw = $this->cleanurl->raw_out(false);
        $this->path = $this->cleanurl->get_path();
        $this->params = $this->cleanurl->params();
        $this->uncleanurl = null;
        clean_moodle_url::log("Incoming url: {$this->cleanurlraw} - Path: {$this->path}");
        clean_moodle_url::extract_moodle_path($this->path, $this->moodlepath);

        // The order here is important as it will stop on first success.
        $uncleaned = false
                     || $this->unclean_test_url()
                     || $this->unclean_course_format()
                     || $this->unclean_user_in_course()
                     || $this->unclean_course_users()
                     || $this->unclean_user_in_forum()
                     || $this->unclean_user_profile_or_in_course()
                     || $this->unclean_course_module_view()
                     || $this->unclean_course_modules()
                     || $this->unclean_course()
                     || $this->unclean_category();

        if ($uncleaned) {
            $this->create_uncleaned_url();
        } else {
            $this->uncleanurl = $this->cleanurl;
        }
    }

    private function unclean_test_url() {
        if ($this->path == '/local/cleanurls/tests/bar') {
            $this->path = '/local/cleanurls/tests/foo.php';
            clean_moodle_url::log("Rewritten to: {$this->path}");
            return true;
        }
        if ($this->path == '/local/cleanurls/tests/webcheck') {
            $this->path = '/local/cleanurls/tests/webserver/index.php';
            clean_moodle_url::log("Rewritten to: {$this->path}");
            return true;
        }
        return false;
    }

    private function unclean_user_in_course() {
        global $DB;

        if (preg_match('#^/course/(.+)/user/(.+)$#', $this->path, $matches)) {
            $this->path = "/user/view.php";
            $this->params['id'] = $DB->get_field('user', 'id', ['username' => urldecode($matches[2])]);
            $this->params['course'] = $DB->get_field('course', 'id', ['shortname' => urldecode($matches[1])]);
            clean_moodle_url::log("Rewritten to: {$this->path}");
            return true;
        }
        return false;
    }

    private function unclean_course_users() {
        global $DB;
        if (preg_match('#^/course/(.+)/user/?$#', $this->path, $matches)) {
            $this->path = "/user/index.php";
            $this->params['id'] = $DB->get_field('course', 'id', ['shortname' => urldecode($matches[1])]);
            clean_moodle_url::log("Rewritten to: {$this->path}");
            return true;
        }
        return false;
    }

    private function unclean_user_in_forum() {
        global $DB;

        if (preg_match('#^/user/(\w+)/(discussions)$#', $this->path, $matches)) {
            // Unclean paths
            // e.g.: http://moodle.com/user/username/discussions
            // into http://moodle.com/mod/forum/user.php?id=123&mode=discussions .
            $this->path = "/mod/forum/user.php";
            $this->params['id'] = $DB->get_field('user', 'id', ['username' => urldecode($matches[1])]);
            $this->params['mode'] = $matches[2];
            clean_moodle_url::log("Rewritten to: {$this->path}");
            return true;
        }
        return false;
    }

    private function unclean_user_profile_or_in_course() {
        global $DB;

        if (preg_match('#^/user/(.+)$#', $this->path, $matches)) {
            // Clean up user profile urls.
            $this->path = "/user/profile.php";
            if (isset($this->params['course'])) {
                $this->path = "/user/view.php";
            }
            $this->params['id'] = $DB->get_field('user', 'id', ['username' => urldecode($matches[1])]);
            clean_moodle_url::log("Rewritten to: {$this->path}");
            return true;
        }
        return false;
    }

    private function unclean_course_format() {
        global $DB;

        if (!preg_match('#^/course/([^/]+)/?(.*)$#', $this->path, $matches)) {
            return false;
        }

        $shortname = $matches[1];
        $parameters = empty($matches[2]) ? [] : explode('/', $matches[2]);

        $course = $DB->get_record('course', ['shortname' => $shortname]);
        if (!$course) {
            return false;
        }

        switch ($course->format) {
            case 'singleactivity':
                return $this->unclean_course_format_singleactivity($course);
            case 'topics':
            case 'weeks':
                return $this->unclean_course_format_simple_section($course, $parameters);
            default:
                return $this->unclean_course_format_hook($course, $parameters);
        }
    }

    private function unclean_course_format_hook(stdClass $course, array $parameters) {
        $classname = "\\format_{$course->format}\\cleanurls_support";
        if (!class_exists($classname)) {
            return false;
        }

        $cmid = $classname::get_cmid_for_path($course, $parameters);
        list(, $cm) = get_course_and_cm_from_cmid($cmid);

        $this->path = "/mod/{$cm->modname}/view.php";
        $this->params['id'] = $cm->id;
        clean_moodle_url::log("Rewritten to: {$this->path}");

        return true;
    }

    private function unclean_course_format_singleactivity(stdClass $course) {
        global $DB;
        $cm = $DB->get_record('course_modules', ['course' => $course->id], 'id,module', MUST_EXIST);
        $modname = $DB->get_field('modules', 'name', ['id' => $cm->module], MUST_EXIST);

        $this->path = "/mod/$modname/view.php";
        $this->params['id'] = $cm->id;
        clean_moodle_url::log("Rewritten to: {$this->path}");

        return true;
    }

    private function unclean_course_format_simple_section(stdClass $course, array $parameters) {
        global $DB;

        if (count($parameters) != 2) {
            return false;
        }
        list($sectionslug, $moduleslug) = $parameters;
        list($cmid) = explode('-', $moduleslug);

        $section = $this->find_section_by_slug($course->id, $sectionslug);
        if (is_null($section)) {
            return false;
        }

        $cm = $DB->get_record('course_modules', ['id' => $cmid], 'id,module', MUST_EXIST);
        $modname = $DB->get_field('modules', 'name', ['id' => $cm->module], MUST_EXIST);

        $this->path = "/mod/$modname/view.php";
        $this->params['id'] = $cm->id;
        clean_moodle_url::log("Rewritten to: {$this->path}");

        return true;
    }

    private function unclean_course_module_view() {
        if (preg_match('#^/course/(.+)/(\w+)/(\d+)(-.*)?$#', $this->path, $matches)) {
            $this->path = "/mod/$matches[2]/view.php";
            $this->params['id'] = $matches[3];
            clean_moodle_url::log("Rewritten to: {$this->path}");
            return true;
        }
        return false;
    }

    private function unclean_course_modules() {
        global $DB;

        if (!preg_match('#^/course/(.+)/(\w+)/?$#', $this->path, $matches)) {
            return false;
        }

        $modulename = $matches[2];
        if ($DB->count_records('modules', ['name' => $modulename]) != 1) {
            return false;
        }

        // Clean up course mod index.
        $this->path = "/mod/$modulename/index.php";
        $this->params['id'] = $DB->get_field('course', 'id', ['shortname' => urldecode($matches[1])]);
        clean_moodle_url::log("Rewritten to: {$this->path}");
        return true;
    }

    private function unclean_course() {
        if (preg_match('#^/course/([^/]+)/?$#', $this->path, $matches)) {
            $this->path = "/course/view.php";
            $this->params['name'] = $matches[1];
            clean_moodle_url::log("Rewritten to: {$this->path}");
            return true;
        }
        return false;
    }

    private function unclean_category() {
        if (preg_match('/^\/category(\/.*-(\d+))?$/', $this->path, $matches)) {
            $this->path = "/course/index.php";
            // We need at least 2 matches to get a valid id. Use the last match (last part of path).
            $this->params['categoryid'] = (count($matches) <= 1) ? 0 : array_pop($matches);
            clean_moodle_url::log("Rewritten to: {$this->path}");
            return true;
        }
        return false;
    }

    private function find_section_by_slug($courseid, $sectionslug) {
        global $DB;

        $sections = $DB->get_records('course_sections', ['course' => $courseid]);
        foreach ($sections as $section) {
            $slug = clean_moodle_url::sluggify($section->name, false);
            if ($slug == $sectionslug) {
                return $section;
            }
        }

        return null;
    }
}
