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

namespace local_cleanurls\local\uncleaner\courseformat;

use local_cleanurls\clean_moodle_url;
use local_cleanurls\local\uncleaner\hascourse_uncleaner_interface;
use local_cleanurls\local\uncleaner\uncleaner;
use moodle_url;
use ReflectionClass;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class simplesection_uncleaner_base
 *
 * Provides a base for course formats consisting of a simple (non-nested) subsections.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class simplesection_uncleaner_base extends uncleaner implements hascourse_uncleaner_interface {
    private static function get_my_format() {
        $class = new ReflectionClass(static::class);
        $class = $class->getShortName();
        $format = substr($class, 0, -10); // Remove the '_uncleaner' suffix.
        return $format;
    }

    public static function find_section_by_slug($courseid, $sectionslug) {
        global $DB;

        $sections = $DB->get_records('course_sections', ['course' => $courseid]);
        foreach ($sections as $section) {
            $slug = clean_moodle_url::sluggify($section->name, false);
            if ($slug == $sectionslug) {
                return $section;
            }
        }

        return null;
    }

    /**
     * It must:
     * - Have a course with the correct format
     * - Have a subpath
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        if (!is_a($parent, hascourse_uncleaner_interface::class)) {
            return false;
        }

        $format = $parent->get_course()->format;
        if ($format !== static::get_my_format()) {
            return false;
        }

        // It requires a section and a course module.
        if (count($parent->subpath) < 2) {
            return false;
        }

        return true;
    }

    /** @var string */
    protected $section = null;

    /** @var int */
    protected $cmid = null;

    public function get_section() {
        return $this->section;
    }

    public function get_cmid() {
        return $this->cmid;
    }

    protected function prepare_path() {
        $this->subpath = is_null($this->parent) ? [] : $this->parent->subpath;
        $section = array_shift($this->subpath);
        $coursemodule = array_shift($this->subpath);

        $this->section = self::find_section_by_slug($this->get_course()->id, $section);

        list($cmid) = explode('-', $coursemodule);
        $this->cmid = (int)$cmid;

        $this->mypath = "{$section}/{$coursemodule}";
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        if (is_null($this->section)) {
            return null;
        }

        if (empty($this->cmid)) {
            return null;
        }

        $cms = get_fast_modinfo($this->get_course())->get_cms();
        if (!array_key_exists($this->cmid, $cms)) {
            return null;
        }

        $cm = $cms[$this->cmid];

        $this->parameters['id'] = $cm->id;
        return new moodle_url("/mod/{$cm->modname}/view.php", $this->parameters);
    }

    /**
     * Finds the course related to this uncleaner, most likely by getting it from its parent.
     *
     * @return stdClass Course data object.
     */
    public function get_course() {
        return $this->parent->get_course();
    }
}