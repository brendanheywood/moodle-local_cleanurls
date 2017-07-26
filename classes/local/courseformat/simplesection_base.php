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

namespace local_cleanurls\local\courseformat;

use cm_info;
use local_cleanurls\clean_moodle_url;
use local_cleanurls\local\cleaner\courseformat_cleaner_interface;
use local_cleanurls\local\uncleaner\hascourse_uncleaner_interface;
use local_cleanurls\local\uncleaner\uncleaner;
use moodle_url;
use ReflectionClass;
use section_info;
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
abstract class simplesection_base extends uncleaner implements hascourse_uncleaner_interface, courseformat_cleaner_interface {
    private static function get_my_format() {
        $class = new ReflectionClass(static::class);
        $format = $class->getShortName();
        return $format;
    }

    /**
     * @param $course
     * @param $sectionslug
     * @return section_info
     */
    public static function find_section_by_slug($course, $sectionslug) {
        $cminfo = get_fast_modinfo($course);
        $sections = $cminfo->get_section_info_all();
        foreach ($sections as $sectioninfo) {
            if ($sectioninfo->section == 0) {
                continue; // Ignore root section.
            }
            $name = get_section_name($course, $sectioninfo->section);
            $slug = clean_moodle_url::sluggify($name, false);
            if ($slug == $sectionslug) {
                return $sectioninfo;
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

        // It requires a section.
        if (count($parent->subpath) < 1) {
            return false;
        }

        return true;
    }

    /** @var section_info */
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

        $this->section = self::find_section_by_slug($this->get_course(), $section);
        if (is_null($coursemodule)) {
            $this->mypath = $section;
        } else {
            list($cmid) = explode('-', $coursemodule);
            $this->cmid = (int)$cmid;
            $this->mypath .= "{$section}/{$coursemodule}";
        }
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        // Section must always be provided, regardless if uncleaning section or course module.
        if (is_null($this->section)) {
            return null;
        }

        if (!empty($this->cmid)) {
            return $this->get_unclean_coursemodule_url();
        }

        return $this->get_unclean_section_url();
    }

    /**
     * Finds the course related to this uncleaner, most likely by getting it from its parent.
     *
     * @return stdClass Course data object.
     */
    public function get_course() {
        return $this->parent->get_course();
    }

    /**
     * Adds the section name followed by the activity slug.
     *
     * @param stdClass $course The Course being cleaned.
     * @param cm_info  $cm     The Course Module being cleaned.
     * @return string          The relative path from the course in which this course module will be accessed.
     */
    public static function get_courseformat_module_clean_subpath(stdClass $course, cm_info $cm) {
        $section = get_section_name($course, $cm->sectionnum);
        $section = clean_moodle_url::sluggify($section, false);

        $title = clean_moodle_url::sluggify($cm->name, true);

        return "{$section}/{$cm->id}{$title}";
    }

    /**
     * Adds the section name.
     *
     * @param stdClass $course  The Course being cleaned.
     * @param int      $section The section number requested.
     * @return string The relative path from the course in which this section is.
     */
    public static function get_courseformat_section_clean_subpath(stdClass $course, $section) {
        if (is_null($section)) {
            return '';
        }

        $section = get_section_name($course, $section);
        $section = clean_moodle_url::sluggify($section, false);
        return "/{$section}";
    }

    private function get_unclean_coursemodule_url() {
        $cms = get_fast_modinfo($this->get_course())->get_cms();
        if (!array_key_exists($this->cmid, $cms)) {
            return null;
        }

        $cm = $cms[$this->cmid];

        return $this->create_unclean_url("/mod/{$cm->modname}/view.php", ['id' => $cm->id]);
    }

    private function get_unclean_section_url() {
        return $this->create_unclean_url('/course/view.php', [
            'name'    => $this->get_course()->shortname,
            'section' => $this->section->section,
        ]);
    }
}
