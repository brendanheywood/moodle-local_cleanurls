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
 * A class inheriting 'uncleaner' for testing purposes.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\local\uncleaner\uncleaner;

defined('MOODLE_INTERNAL') || die();

/**
 * Implements uncleaner for tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_unittest_uncleaner extends uncleaner {
    /** @var string[] */
    public static $childoptions = [];

    /** @var callable */
    public static $cancreate = null;

    /**
     * Uses a callback to determine the result, or true
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        $callback = self::$cancreate;

        if (is_callable($callback)) {
            return $callback($parent);
        }

        return true;
    }

    /**
     * @return string[]
     */
    public static function list_child_options() {
        return self::$childoptions;
    }

    /** @var array */
    public $options;

    public function __construct($parent = null, $options = []) {
        $this->options = $options;
        parent::__construct($parent);
    }

    protected function prepare_path() {
        parent::prepare_path();

        if (isset($this->options['subpath'])) {
            $this->subpath = $this->options['subpath'];
        }
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        return new moodle_url('/');
    }

    public function prepare_child() {
        if (isset($this->options['test_it_may_have_a_child:parent'])) {
            $this->child = new local_cleanurls_unittest_uncleaner($this, ['test_it_may_have_a_child:child' => true]);
            return;
        }

        if (isset($this->options['test_it_may_have_a_child:child'])) {
            return;
        }

        parent::prepare_child();
    }
}
