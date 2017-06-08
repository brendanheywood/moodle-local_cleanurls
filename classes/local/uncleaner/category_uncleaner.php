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
 * Class category_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_uncleaner extends uncleaner {
    /**
     * @return string[]
     */
    public static function list_child_options() {
        return [category_uncleaner::class];
    }

    /**
     * Quick check if this object should be created for the given parent.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        // If parent has no subpaths, do not allow.
        if (empty($parent->subpath)) {
            return false;
        }

        // If coming from root, it requires the "category".
        if (is_a($parent, root_uncleaner::class) && ($parent->subpath[0] == 'category')) {
            return true;
        }

        // Subcategories are allowed.
        if (is_a($parent, category_uncleaner::class)) {
            return true;
        }

        return false;
    }

    /**
     * It:
     * - Consumes 'category' if parent is root.
     * - Adds the next path as 'mypath'.
     * - Leave the rest as subpaths.
     */
    protected function prepare_path() {
        $this->subpath = $this->parent->subpath;
        if (is_a($this->parent, root_uncleaner::class)) {
            array_shift($this->subpath);
        }
        $this->mypath = array_shift($this->subpath);
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        if (!preg_match('#-(\d+)$#', $this->mypath, $matches)) {
            return null;
        }
        $this->parameters['categoryid'] = $matches[1];
        return new moodle_url('/course/index.php', $this->parameters);
    }
}
