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

namespace local_cleanurls\cache;

use cache;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Class cleanurls_cache
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanurls_cache {
    public static function get_incoming_cache() {
        return cache::make('local_cleanurls', 'incoming');
    }

    /**
     * @param string|moodle_url $clean
     * @return moodle_url
     * @throws moodle_exception
     */
    public static function get_clean_from_unclean($clean) {
        if (get_config('local_cleanurls', 'nocache')) {
            return null;
        }

        if (!is_string($clean)) {
            $clean = $clean->raw_out();
        }
        $unclean = self::get_incoming_cache()->get($clean);

        if (!$unclean) {
            return null;
        }
        return new moodle_url($unclean);
    }

    /**
     * @param string|moodle_url $clean
     * @param string|moodle_url $unclean
     */
    public static function save_unclean_for_clean($clean, $unclean) {
        if (!is_string($clean)) {
            $clean = $clean->raw_out();
        }
        if (!is_string($unclean)) {
            $unclean = $unclean->raw_out();
        }
        self::get_incoming_cache()->set($clean, $unclean);
    }
}
