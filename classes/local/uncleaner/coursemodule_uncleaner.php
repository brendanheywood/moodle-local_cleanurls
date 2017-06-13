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

defined('MOODLE_INTERNAL') || die();

/**
 * Class coursemodule_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursemodule_uncleaner extends uncleaner {
    /**
     * Quick check if this object should be created for the given parent.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        global $CFG;

        if (!is_a($parent, uncleaner::class)) {
            return false;
        }

        // It requires a modname and cmid slug, ex: forum/123-myforum.
        if (count($parent->subpath) < 2) {
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
        $path = "/mod/{$this->modname}/view.php";
        $this->parameters['id'] = $this->cmid;
        return new moodle_url($path, $this->parameters);
    }
}
