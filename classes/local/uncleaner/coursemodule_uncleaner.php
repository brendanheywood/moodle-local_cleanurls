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
        global $CFG;

        if (!is_a($parent, hascourse_uncleaner_interface::class)) {
            return false;
        }

        // It requires a modname.
        if (count($parent->subpath) < 1) {
            return false;
        }

        // The modname should be valid.
        if (!file_exists($CFG->dirroot . '/mod/' . $parent->subpath[0])) {
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
     * - Reads the modname.
     * - Reads the the cmid.
     * - Leave the rest as subpaths.
     */
    protected function prepare_path() {
        $this->subpath = $this->parent->subpath;
        $this->modname = array_shift($this->subpath);
        $modid = array_shift($this->subpath);
        $this->mypath = "{$this->modname}/{$modid}";

        list($cmid) = explode('-', $modid);
        $this->cmid = ($cmid === (string)(int)$cmid) ? (int)$cmid : null;
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
            $path = "/mod/{$this->modname}/index.php";
            $this->parameters['id'] = $this->get_course()->id;
        } else {
            $path = "/mod/{$this->modname}/view.php";
            $this->parameters['id'] = $this->cmid;
        }
        return new moodle_url($path, $this->parameters);
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
