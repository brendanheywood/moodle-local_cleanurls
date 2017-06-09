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

use local_cleanurls\local\uncleaner\root_uncleaner;
use local_cleanurls\local\uncleaner\uncleaner;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Class course_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_uncleaner extends uncleaner {
    /**
     * @return string[]
     */
    public static function list_child_options() {
        return [];
    }

    /**
     * Quick check if this object should be created for the given parent.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        // It must be child of root.
        if (!is_a($parent, root_uncleaner::class)) {
            return false;
        }

        return true;
    }

    /**
     * It does not return an URL, it relies on a child instead.
     *
     * @return moodle_url
     */
    public function get_unclean_url() {
        return null;
    }
}
