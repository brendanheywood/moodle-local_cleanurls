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
 * Class user_forum_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_forum_uncleaner extends uncleaner {
    /**
     * It must be in user.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        // Parent must be 'user'.
        if (!is_a($parent, user_uncleaner::class)) {
            return false;
        }

        return true;
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        /** @var user_uncleaner $parent */
        $parent = $this->get_parent();
        $userid = $parent->get_userid();

        if (empty($userid)) {
            return null;
        }

        return $this->create_unclean_url('/mod/forum/user.php', [
            'id'   => $userid,
            'mode' => $this->mypath,
        ]);
    }
}
