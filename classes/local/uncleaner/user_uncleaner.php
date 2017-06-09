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
 * Class user_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_uncleaner extends uncleaner {
    /**
     * It must be in root, followed by the 'user' subpath.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        // Parent must be root.
        if (!is_a($parent, root_uncleaner::class)) {
            return false;
        }

        // Parents first subpath must be 'user'.
        if ((count($parent->subpath) < 1) || ($parent->subpath[0] != 'user')) {
            return false;
        }

        return true;
    }

    public static function list_child_options() {
        return [user_forum_uncleaner::class];
    }

    /**
     * It:
     * - Consumes 'user' subpath.
     * - Adds the next path as 'mypath'.
     * - Leave the rest as subpaths.
     */
    protected function prepare_path() {
        $this->subpath = $this->parent->subpath;
        array_shift($this->subpath); // Consume the 'user' subpath.
        $this->mypath = array_shift($this->subpath);
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        global $DB;

        $username = urldecode($this->mypath);
        $userid = $DB->get_field('user', 'id', ['username' => $username]);
        if ($userid === false) {
            return null;
        }

        $path = isset($this->parameters['course']) ? '/user/view.php' : '/user/profile.php';
        $this->parameters['id'] = $userid;

        return new moodle_url($path, $this->parameters);
    }
}
