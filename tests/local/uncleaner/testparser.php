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
 * Implements parser for tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\local\uncleaner\uncleaner;

defined('MOODLE_INTERNAL') || die();

/**
 * Implements parser for tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_testparser extends uncleaner {
    public static function create(uncleaner $parent) {
        // Only allow if the parent is testparser (one level only).
        if (is_a($parent, local_cleanurls_testparser::class) && is_null($parent->get_parent())) {
            return new local_cleanurls_testparser($parent);
        }

        return null;
    }

    /** @var string[] */
    public static $childoptions = [];

    /**
     * @return string[]
     */
    public static function list_child_options() {
        return self::$childoptions;
    }

    /** @var array */
    public $testparameters;

    public function __construct($parent = null, ...$parameters) {
        $this->testparameters = $parameters;
        parent::__construct($parent);
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        return new moodle_url('/' . implode('/', $this->testparameters));
    }

    public function prepare_child() {
        if (['test_it_may_have_a_child', 'parent'] === $this->testparameters) {
            $this->child = new local_cleanurls_testparser($this, 'test_it_may_have_a_child', 'child');
            return;
        }

        parent::prepare_child();
    }
}
