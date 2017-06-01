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
        $this->subpath = $this->prepare_subpath();
        $this->parameters = $this->prepare_parameters();
    }

    /**
     * @return urlparser
     */
    public function get_parent() {
        return $this->parent;
    }

    /**
     * It defaults to removing one level of path from the parent or empty if no parent.
     *
     * @return string[]
     */
    protected function prepare_subpath() {
        if (is_null($this->parent)) {
            return [];
        }
        $subpath = $this->parent->subpath;
        array_shift($subpath);
        return $subpath;
    }

    /**
     * @return string[]
     */
    abstract protected function prepare_parameters();

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
