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
     * Tries to create a child for that parent, returns null if not possible.
     *
     * @param uncleaner $parent
     * @return uncleaner|null
     */
    public static function create(uncleaner $parent) {
        if (count($parent->subpath) < 3) {
            return null;
        }

        $subpath = array_slice($parent->subpath, 0, 3);
        if ($subpath !== ['local', 'cleanurls', 'tests']) {
            return null;
        }

        return new selftest_uncleaner($parent);
    }

    /**
     * It:
     * - Consumes 3 subpaths (local/cleanurls/tests).
     * - Adds the rest as 'mypath'.
     * - Has no subpaths.
     */
    protected function prepare_path() {
        $path = array_slice($this->parent->subpath, 3);
        $this->mypath = implode('/', $path);
        $this->subpath = [];
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        switch ($this->mypath) {
            case 'bar':
                return new moodle_url('/local/cleanurls/tests/foo.php');
            case 'webcheck':
                return new moodle_url('/local/cleanurls/tests/webserver/index.php');
            default:
                return null;
        }
    }
}
