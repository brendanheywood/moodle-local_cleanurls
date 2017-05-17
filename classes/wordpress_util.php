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
 * Functions borrowed from WordPress.
 *
 * @package    local_cleanurls
 */

namespace local_cleanurls;

defined('MOODLE_INTERNAL') || die();

/**
 * The main cleaning and uncleaning logic
 *
 * @package    local_cleanurls
 */
abstract class wordpress_util {
    /**
     * Borrowed from WordPress
     *
     * https://developer.wordpress.org/reference/functions/sanitize_title_with_dashes/
     *
     * @param string $title
     * @return string
     */
    public static function sanitize_title_with_dashes($title) {
        $title = strip_tags($title);
        // Preserve escaped octets.
        $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
        // Remove percent signs that are not part of an octet.
        $title = str_replace('%', '', $title);
        // Restore octets.
        $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
        $title = mb_strtolower($title, 'UTF-8');
        $title = self::utf8_uri_encode($title);
        $title = strtolower($title);

        $title = preg_replace('/&.+?;/', '', $title); // Kill entities.
        $title = str_replace('.', '-', $title);
        $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
        $title = preg_replace('/\s+/', '-', $title);
        $title = preg_replace('|-+|', '-', $title);
        $title = trim($title, '-');
        return $title;
    }

    /**
     * Borrowed from WordPress
     *
     * @param string $utf8string
     * @return string
     *
     * https://developer.wordpress.org/reference/functions/utf8_uri_encode/
     */
    private static function utf8_uri_encode($utf8string) {
        $unicode = '';
        $values = [];
        $numoctets = 1;
        $unicodelength = 0;
        self::mbstring_binary_safe_encoding();
        $stringlength = strlen($utf8string);
        self::reset_mbstring_encoding();
        for ($i = 0; $i < $stringlength; $i++) {
            $value = ord($utf8string[$i]);
            if ($value < 128) {
                $unicode .= chr($value);
                $unicodelength++;
            } else {
                if (count($values) == 0) {
                    if ($value < 224) {
                        $numoctets = 2;
                    } else {
                        if ($value < 240) {
                            $numoctets = 3;
                        } else {
                            $numoctets = 4;
                        }
                    }
                }
                $values[] = $value;
                if (count($values) == $numoctets) {
                    for ($j = 0; $j < $numoctets; $j++) {
                        $unicode .= '%'.dechex($values[$j]);
                    }
                    $unicodelength += $numoctets * 3;
                    $values = [];
                    $numoctets = 1;
                }
            }
        }
        return $unicode;
    }

    /**
     * Borrowed from WordPress
     *
     * https://developer.wordpress.org/reference/functions/mbstring_binary_safe_encoding/
     *
     * @param bool $reset
     */
    private static function mbstring_binary_safe_encoding($reset = false) {
        static $encodings = [];
        static $overloaded = null;

        if (is_null($overloaded)) {
            $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);
        }

        if (false === $overloaded) {
            return;
        }

        if (!$reset) {
            $encoding = mb_internal_encoding();
            array_push($encodings, $encoding);
            mb_internal_encoding('ISO-8859-1');
        }

        if ($reset && $encodings) {
            $encoding = array_pop($encodings);
            mb_internal_encoding($encoding);
        }
    }

    /**
     * Borrowed from WordPress
     *
     * https://developer.wordpress.org/reference/functions/reset_mbstring_encoding/
     */
    private static function reset_mbstring_encoding() {
        self::mbstring_binary_safe_encoding(true);
    }
}
