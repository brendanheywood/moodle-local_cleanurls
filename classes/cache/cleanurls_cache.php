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
    private static $outgoingdebug = [];

    private static $enabled = true;

    public static function get_outgoing_debug() {
        return self::$outgoingdebug;
    }

    public static function enable() {
        self::$enabled = true;
    }

    public static function disable() {
        self::$enabled = false;
    }

    public static function is_enabled() {
        if (!self::$enabled) {
            return false;
        }

        if (self::is_disabled_at_config()) {
            return false;
        }

        return true;
    }

    public static function is_disabled_at_config() {
        return get_config('local_cleanurls', 'nocache');
    }

    public static function is_debugging() {
        return isset($_GET['CLEANURLS_DEBUG']);
    }

    /**
     * Maps URLs: clean to unclean.
     */
    public static function get_incoming_cache() {
        if (!self::is_enabled()) {
            return null;
        }

        return cache::make('local_cleanurls', 'incoming');
    }

    /**
     * Maps URLs: unclean to clean.
     */
    protected static function get_outgoing_cache() {
        if (!self::is_enabled()) {
            return null;
        }

        return cache::make('local_cleanurls', 'outgoing');
    }

    /**
     * @param string|moodle_url $clean
     * @return moodle_url
     */
    public static function get_unclean_from_clean($clean) {
        if (!self::is_enabled()) {
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
     * @param string|moodle_url $unclean
     * @return moodle_url
     */
    public static function get_clean_from_unclean($unclean) {
        if (!self::is_enabled()) {
            return null;
        }

        if (!is_string($unclean)) {
            $unclean = $unclean->raw_out();
        }

        if (self::is_debugging()) {
            if (isset(self::$outgoingdebug[$unclean])) {
                self::$outgoingdebug[$unclean]++;
            } else {
                self::$outgoingdebug[$unclean] = 1;
            }
        }

        $clean = self::get_outgoing_cache()->get($unclean);

        return $clean ? new moodle_url($clean) : null;
    }

    /**
     * @param string|moodle_url $clean
     * @param string|moodle_url $unclean
     */
    public static function save_unclean_for_clean($clean, $unclean) {
        if (!self::is_enabled()) {
            return;
        }

        if (!is_string($clean)) {
            $clean = $clean->raw_out();
        }

        if (!is_string($unclean)) {
            $unclean = $unclean->raw_out();
        }

        self::get_incoming_cache()->set($clean, $unclean);
    }

    /**
     * @param string|moodle_url $unclean
     * @param string|moodle_url $clean
     */
    public static function save_clean_for_unclean($unclean, $clean) {
        if (!self::is_enabled()) {
            return;
        }

        if (!is_string($unclean)) {
            $unclean = $unclean->raw_out();
        }

        if (!is_string($clean)) {
            $clean = $clean->raw_out();
        }

        self::get_outgoing_cache()->set($unclean, $clean);
    }

    /**
     * @param string|moodle_url $clean
     */
    public static function delete_unclean_for_clean($clean) {
        if (!self::is_enabled()) {
            return;
        }

        if (!is_string($clean)) {
            $clean = $clean->raw_out();
        }

        self::get_incoming_cache()->delete($clean);
    }

    /**
     * @param string|moodle_url $unclean
     */
    public static function delete_clean_for_unclean($unclean) {
        if (!self::is_enabled()) {
            return;
        }

        if (!is_string($unclean)) {
            $unclean = $unclean->raw_out();
        }

        self::get_outgoing_cache()->delete($unclean);
    }
}
