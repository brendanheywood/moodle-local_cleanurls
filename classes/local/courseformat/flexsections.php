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
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class flexformat_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flexsections extends uncleaner implements hascourse_uncleaner_interface, courseformat_cleaner_interface {
    /**
     * Quick check if this object should be created for the given parent.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        if (!is_a($parent, hascourse_uncleaner_interface::class)) {
            return false;
        }

        $format = $parent->get_course()->format;
        if ($format !== 'flexsections') {
            return false;
        }

        // Parent (course) must have subpath, otherwise we are at course level.
        if (count($parent->get_subpath()) == 0) {
            return false;
        }

        return true;
    }

    private static function create_section_tree($courseid) {
        self::create_section_tree_database_info($courseid, $sections, $parents);
        $root = null;

        // Initialize child for all sections.
        foreach ($sections as $section) {
            $section->flexsections = [];
        }

        // Add children.
        foreach ($sections as $num => $section) {
            // Hidden root section.
            if ($num == 0) {
                $root = $section;
                continue;
            }

            // If parent information not found, it is a flexsection root.
            if (!array_key_exists($num, $parents)) {
                $parents[$num] = 0;
            }

            if (!array_key_exists($parents[$num], $sections)) {
                debugging("Could not find the parent {$parents[$num]} for {$num}.");
            }
            $parent = $sections[$parents[$num]];
            $parent->flexsections[] = $section;
        }

        return $root;
    }

    private static function create_section_tree_database_info($courseid, &$sections, &$parents) {
        global $DB;
        $sectionstmp = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');
        $parentstmp = $DB->get_records('course_format_options', ['courseid' => $courseid, 'name' => 'parent'], 'sectionid ASC');

        // Create parents with a single 'from->to' using section number.
        $parents = [];
        foreach ($parentstmp as $parent) {
            $parents[$sectionstmp[$parent->sectionid]->section] = $parent->value;
        }

        // Reindex sections based on their number.
        $sections = [];
        foreach ($sectionstmp as $section) {
            $sections[$section->section] = $section;
        }
    }

    /** @var stdClass */
    private $sectiontree;

    /** @var  stdClass[] */
    private $sectionpath;

    /** @var cm_info */
    private $coursemodule = null;

    protected function prepare_path() {
        $this->sectiontree = self::create_section_tree($this->get_course()->id);
        $this->sectionpath = [$this->sectiontree];

        // Let's start with the whole subpath and empty mypath.
        $this->subpath = $this->parent->get_subpath();
        $this->mypath = '';

        // Read each possible section.
        // @codingStandardsIgnoreStart
        while ($this->prepare_path_read_section());
        // @codingStandardsIgnoreEnd

        // Read the activity.
        if (!$this->prepare_path_read_activity()) {
            debugging('Could not find flex section or activity for ['
                      . implode('/', $this->parent->get_subpath()) . '] at [' . implode('/', $this->subpath) . '].');
            return;
        }
    }

    /**
     * @return stdClass[]
     */
    public function get_section_path() {
        return $this->sectionpath;
    }

    /**
     * @return cm_info
     */
    public function get_course_module() {
        return $this->coursemodule;
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        if (is_null($this->coursemodule)) {
            return null;
        }

        $this->parameters['id'] = $this->coursemodule->id;
        return new moodle_url("/mod/{$this->coursemodule->modname}/view.php", $this->parameters);
    }

    /**
     * Finds the course related to this uncleaner, most likely by getting it from its parent.
     *
     * @return stdClass Course data object.
     */
    public function get_course() {
        return $this->parent->get_course();
    }

    public function get_section_tree() {
        return $this->sectiontree;
    }

    private function prepare_path_read_activity() {
        // It requires a subpath.
        if (count($this->subpath) == 0) {
            return false;
        }

        // It must have a '-' to prefix with an cmid.
        $path = $this->subpath[0];
        $index = strpos($path, '-');
        if ($index === false) {
            return false;
        }
        $cmid = substr($path, 0, $index);

        // The cmid must be an integer.
        if ($cmid !== (string)(int)$cmid) {
            return false;
        }
        $cmid = (int)$cmid;

        // The course module should exists.
        $cms = get_fast_modinfo($this->get_course())->get_cms();
        if (!array_key_exists($cmid, $cms)) {
            return false;
        }

        // Found it.
        array_shift($this->subpath);
        $this->mypath = "{$this->mypath}/{$path}";
        $this->coursemodule = $cms[$cmid];
        return true;
    }

    private function prepare_path_read_section() {
        $sectionpath = array_shift($this->subpath);

        $previous = end($this->sectionpath);
        foreach ($previous->flexsections as $candidate) {
            $name = get_section_name($this->get_course(), $candidate->section);
            $slug = clean_moodle_url::sluggify($name, false);
            if ($slug == $sectionpath) {
                // Found it!
                $this->sectionpath[] = $candidate;
                $this->mypath = ltrim("{$this->mypath}/{$sectionpath}", '/');
                return true;
            }
        }

        // Too bad, put back the section path before failing.
        array_unshift($this->subpath, $sectionpath);
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function get_courseformat_module_clean_subpath(stdClass $course, cm_info $cm) {
        global $DB;
        $info = get_fast_modinfo($course);

        // Create section path.
        $sectionnum = $cm->sectionnum;
        $path = [];
        while ($sectionnum) {
            $sectionid = $info->get_section_info($sectionnum)->id;
            $slug = clean_moodle_url::sluggify(get_section_name($course, $sectionnum), false);
            array_unshift($path, $slug);
            $sectionnum = $DB->get_field('course_format_options', 'value', [
                'courseid'  => $course->id,
                'format'    => 'flexsections',
                'sectionid' => $sectionid,
                'name'      => 'parent',
            ]);
        }

        // Add activity path.
        $title = clean_moodle_url::sluggify($cm->name, true);
        $path[] = "{$cm->id}{$title}";

        return implode('/', $path);
    }
}
