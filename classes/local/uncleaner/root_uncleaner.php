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

use invalid_parameter_exception;
use local_cleanurls\clean_moodle_url;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Class root_parser
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class root_uncleaner extends uncleaner {
    /**
     * It can only be created without any parent.
     *
     * @param uncleaner|null $parent
     * @return bool
     */
    public static function can_create($parent) {
        return is_null($parent);
    }

    /**
     * @return string[]
     */
    public static function list_child_options() {
        return [
            selftest_uncleaner::class,
            category_uncleaner::class,
            user_uncleaner::class,
            course_uncleaner::class,
        ];
    }

    /** @var moodle_url */
    protected $originalurl;

    /** @var clean_moodle_url */
    protected $cleanurl = null;

    /** @var string */
    protected $moodlepath = null;

    /**
     * root_parser constructor.
     *
     * @param string|moodle_url $url
     * @throws invalid_parameter_exception
     */
    public function __construct($url) {
        global $CFG;

        if (!is_string($url) && !is_a($url, moodle_url::class)) {
            throw new invalid_parameter_exception('URL must be a string or moodle_url.');
        }

        $this->cleanurl = new clean_moodle_url($url);
        $this->originalurl = $this->cleanurl->raw_out(false);

        // Save subpath where Moodle resides.
        $path = parse_url($CFG->wwwroot, PHP_URL_PATH);
        $path = trim($path, '/');
        $this->moodlepath = empty($path) ? '' : "/{$path}";

        parent::__construct(null);
    }

    /**
     * @return moodle_url
     */
    public function get_original_raw_url() {
        return $this->originalurl;
    }

    /**
     * @return string
     */
    public function get_moodle_path() {
        return $this->moodlepath;
    }

    /**
     * @return clean_moodle_url
     */
    public function get_clean_url() {
        return $this->cleanurl;
    }

    /**
     * The 'mypath' for root is empty, while subpath contains the whole path of the URL.
     */
    public function prepare_path() {
        global $CFG;
        $this->mypath = $CFG->wwwroot;

        $path = $this->get_clean_url()->get_path();
        $path = substr($path, strlen($this->get_moodle_path()));
        $path = trim($path, '/');
        $this->subpath = ($path === '') ? [] : explode('/', $path);
    }

    /**
     * Just use the given parameters on the URL.
     */
    public function prepare_parameters() {
        $this->parameters = $this->get_clean_url()->params();
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        // If called it failed uncleaning any part of the URL, return original (will probably get 404).
        return $this->cleanurl;
    }
}
