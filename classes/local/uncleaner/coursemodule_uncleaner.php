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

namespace local_cleanurls\local\uncleaner;

use local_cleanurls\activity_path;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class coursemodule_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursemodule_uncleaner extends uncleaner implements hascourse_uncleaner_interface {
    /**
     * Quick check if this object should be created for the given parent.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        if (!is_a($parent, hascourse_uncleaner_interface::class)) {
            return false;
        }

        // It requires a modname.
        if (count($parent->subpath) < 1) {
            return false;
        }

        return true;
    }

    /** @var string */
    protected $modname = null;

    /** @var int|null */
    protected $cmid = null;

    /**
     * It:
     * - Tries to use a custom path first.
     * - If failed, tries to use the default 'modtype/id-name' format.
     */
    protected function prepare_path() {
        if ($this->prepare_path_custom()) {
            return;
        }

        if ($this->prepare_path_default()) {
            return;
        }

        $this->subpath = $this->parent->subpath;
        $this->modname = null;
        $this->mypath = null;
        $this->cmid = null;
    }

    /**
     * It:
     * - Tries to find a possible CMID that matches the custom path.
     */
    protected function prepare_path_custom() {
        $paths = $this->prepare_path_custom_possibilities();
        $subpath = $this->parent->subpath;

        foreach ($paths as $cmid => $path) {
            if (count($subpath) < count($path)) {
                continue; // Subpath is too short for this course module custom path.
            }

            $expected = array_slice($subpath, 0, count($path));
            if ($expected === $path) {
                $this->prepare_path_custom_found($cmid, $path);
                return true;
            }
        }

        return false;
    }

    /**
     * Get all possible paths, ensuring that they are:
     * - in reverse order (longer paths first)
     * - broken into an array of subpaths
     * - indexed by the cmid
     *
     * @return string[][]
     */
    protected function prepare_path_custom_possibilities() {
        global $DB;

        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $cmids = array_keys($modinfo->cms);
        $paths = $DB->get_records_list(activity_path::PATHS_TABLE, 'cmid', $cmids);

        $result = [];
        rsort($paths);
        foreach ($paths as $path) {
            $result[(int)$path->cmid] = explode('/', $path->path);
        }

        return $result;
    }

    protected function prepare_path_custom_found($cmid, $path) {
        $modinfo = get_fast_modinfo($this->get_course());

        $this->subpath = array_slice($this->parent->subpath, count($path));
        $this->mypath = implode('/', $path);
        $this->cmid = $cmid;
        $this->modname = $modinfo->cms[$cmid]->modname;
    }

    /**
     * It:
     * - Reads the modname.
     * - Reads the the cmid.
     * - Leave the rest as subpaths.
     */
    protected function prepare_path_default() {
        global $CFG;

        $this->subpath = $this->parent->subpath;
        $modid = array_shift($this->subpath);
        $this->mypath = "{$modid}";

        list($cmid) = explode('-', $modid);
        $this->cmid = ($cmid === (string)(int)$cmid) ? (int)$cmid : null;

        if (is_null($this->cmid)) {
            // This could be a course module index page.
            if (!file_exists($CFG->dirroot . '/mod/' . $modid)) {
                return false;
            }
            $this->modname = $modid;
            return true;
        }

        $modinfo = get_fast_modinfo($this->get_course());
        if (!isset($modinfo->cms[$this->cmid])) {
            return false;
        }

        $this->modname = $modinfo->cms[$this->cmid]->modname;

        return true;
    }

    public function get_modname() {
        return $this->modname;
    }

    public function get_cmid() {
        return $this->cmid;
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        global $DB;

        if ($DB->count_records('modules', ['name' => $this->modname]) != 1) {
            return null;
        }

        if (is_null($this->cmid)) {
            return $this->create_unclean_url(
                "/mod/{$this->modname}/index.php",
                ['id' => $this->get_course()->id]);
        }

        return $this->create_unclean_url(
            "/mod/{$this->modname}/view.php",
            ['id' => $this->cmid]);
    }

    /**
     * Finds the course related to this uncleaner, most likely by getting it from its parent.
     *
     * @return stdClass Course data object.
     */
    public function get_course() {
        return $this->parent->get_course();
    }
}
