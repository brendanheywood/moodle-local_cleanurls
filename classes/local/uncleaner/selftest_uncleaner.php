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
 * Class selftest_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class selftest_uncleaner extends uncleaner {
    /**
     * Checks if inside
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        if (!is_a($parent, root_uncleaner::class)) {
            return false;
        }

        if (count($parent->subpath) < 3) {
            return false;
        }

        $subpath = array_slice($parent->subpath, 0, 3);
        if ($subpath !== ['local', 'cleanurls', 'tests']) {
            return false;
        }

        return true;
    }

    /**
     * It:
     * - Reads 4 subpaths as mypath.
     * - The rest is subpath.
     */
    protected function prepare_path() {
        $this->subpath = $this->parent->subpath;
        $path = array_splice($this->subpath, 0, 4);
        $this->mypath = implode('/', $path);
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        switch ($this->mypath) {
            case 'local/cleanurls/tests/oldbar':
            case 'local/cleanurls/tests/bar':
                return new moodle_url('/local/cleanurls/tests/foo.php');
            case 'local/cleanurls/tests/webcheck':
                return new moodle_url('/local/cleanurls/tests/webserver/index.php');
            default:
                return null;
        }
    }
}
