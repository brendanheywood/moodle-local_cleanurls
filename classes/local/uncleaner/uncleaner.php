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
use local_cleanurls\cache\cleanurls_cache;
use local_cleanurls\url_history;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class uncleaner {
    /**
     * @param string|moodle_url $clean
     * @return moodle_url
     */
    public static function unclean($clean) {
        $unclean = cleanurls_cache::get_unclean_from_clean($clean);
        if (!is_null($unclean)) {
            return $unclean;
        }

        $unclean = url_history::get($clean);
        if (is_null($unclean)) {
            $unclean = self::perform_uncleaning($clean);
        } else {
            $unclean = new moodle_url($unclean);
        }

        cleanurls_cache::save_unclean_for_clean($clean, $unclean);
        return $unclean;
    }

    /**
     * @param $clean
     * @return moodle_url
     * @throws moodle_exception
     */
    private static function perform_uncleaning($clean) {
        $root = new root_uncleaner($clean);
        $node = $root;

        do {
            $lastnode = $node;
            $node = $node->get_child();
        } while (!is_null($node));

        $unclean = $lastnode->get_unclean_url();
        while (is_null($unclean)) {
            $lastnode = $lastnode->parent;
            if (is_null($lastnode)) {
                throw new moodle_exception('At least root_uncleaner should have returned something.');
            }
            $unclean = $lastnode->get_unclean_url();
        }

        if (!empty($lastnode->get_subpath())) {
            $subpath = implode('/', $lastnode->get_subpath());
            $debug = implode("\n", $root->debug_path());
            debugging("Could not unclean until the end of address: {$subpath}\n\n{$clean}\n{$debug}", DEBUG_DEVELOPER);
        }

        return $unclean;
    }

    /** @var uncleaner */
    protected $parent;

    /** @var uncleaner */
    protected $child;

    /** @var string */
    protected $mypath = null;

    /** @var string[] */
    protected $subpath = null;

    /** @var string[] */
    protected $parameters = null;

    /**
     * It defaults to nothing.
     *
     * @return string[] List of uncleaner-derived classes that could be a child of this object.
     */
    public static function list_child_options() {
        return [];
    }

    /**
     * Quick check if this object should be created for the given parent.
     *
     * Defaults to false as it should be overriden by child class.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        return false;
    }

    /**
     * urlparser constructor.
     *
     * @param uncleaner|null $parent
     * @throws invalid_parameter_exception
     */
    public function __construct($parent) {
        if (!static::can_create($parent)) {
            throw new invalid_parameter_exception('Cannot create for given parent.');
        }

        $this->parent = $parent;
        $this->prepare_path();
        $this->prepare_parameters();
        $this->prepare_child();
    }

    /**
     * @return moodle_url
     */
    public abstract function get_unclean_url();

    /**
     * @return uncleaner
     */
    public function get_parent() {
        return $this->parent;
    }

    /**
     * @return uncleaner
     */
    public function get_child() {
        return $this->child;
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
     * It defaults to the parent parameters or empty array if no parent.
     */
    protected function prepare_parameters() {
        $this->parameters = is_null($this->parent) ? [] : $this->parent->parameters;
    }

    /**
     * It defaults to trying all available options if there is a subpath.
     */
    protected function prepare_child() {
        $this->child = null;

        if (empty($this->subpath)) {
            return;
        }

        $options = static::list_child_options();
        foreach ($options as $option) {
            if ($option::can_create($this)) {
                $this->child = new $option($this);
                return;
            }
        }
    }

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

    /**
     * Creates a moodle URL.
     *
     * @param string   $path       Path for the Moodle URL.
     * @param string[] $parameters Override default parameters, use null to unset it.
     * @return moodle_url
     */
    public function create_unclean_url($path, $parameters = []) {
        $parameters = array_merge($this->parameters, $parameters);
        foreach ($parameters as $key => $value) {
            if (is_null($value)) {
                unset($parameters[$key]);
            }
        }
        return new moodle_url($path, $parameters);
    }

    public function get_root() {
        $root = $this;
        while (!is_null($root->parent)) {
            $root = $root->parent;
        }
        return $root;
    }

    public function debug_path() {
        $me = [static::class . ': ' . $this->mypath];

        if (!is_null($this->child)) {
            $me = array_merge($me, $this->child->debug_path());
        }

        return $me;
    }
}
