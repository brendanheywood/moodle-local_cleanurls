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

namespace local_cleanurls\local\urlparser;

use invalid_parameter_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Class urlparser
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class urlparser {
    /** @var urlparser */
    protected $parent;

    /** @var string */
    protected $mypath = null;

    /** @var string[] */
    protected $subpath = null;

    /** @var string[] */
    protected $parameters = null;

    /**
     * urlparser constructor.
     *
     * @param urlparser|null $parent
     * @throws invalid_parameter_exception
     */
    public function __construct($parent) {
        if (!is_a($parent, self::class) && !is_null($parent)) {
            throw new invalid_parameter_exception('parent must be urlparser or null.');
        }

        $this->parent = $parent;
        $this->prepare_path();
        $this->prepare_parameters();
    }

    /**
     * @return urlparser
     */
    public function get_parent() {
        return $this->parent;
    }

    /**
     * It defaults to:
     * - subpath = removing one level of path from the parent or empty if no parent.
     * - mypath = the removed path or empty if not available.
     */
    protected function prepare_path() {
        $this->subpath = is_null($this->parent) ? [] : $this->parent->subpath;
        $this->mypath = array_shift($this->subpath);

        if (is_null($this->mypath)) {
            $this->mypath = '';
        }
    }

    /**
     * @return string[]
     */
    abstract protected function prepare_parameters();

    /**
     * @return string
     */
    public function get_mypath() {
        return $this->mypath;
    }

    /**
     * @return string[]
     */
    public function get_subpath() {
        return $this->subpath;
    }

    /**
     * @return string[]
     */
    public function get_parameters() {
        return $this->parameters;
    }
}
