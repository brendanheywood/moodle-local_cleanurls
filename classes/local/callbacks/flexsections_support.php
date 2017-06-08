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

namespace local_cleanurls\local\callbacks;

use cm_info;
use local_cleanurls\clean_moodle_url;
use local_cleanurls\cleanurls_support_interface;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class flexsections_support
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flexsections_support implements cleanurls_support_interface {
    /**
     * @inheritdoc
     */
    public static function get_clean_subpath(stdClass $course, cm_info $cm) {
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

    /**
     * @inheritdoc
     */
    public static function get_cmid_for_path(stdClass $course, array $path) {
        if (count($path) == 0) {
            return null;
        }
        $last = array_pop($path);
        list($id) = explode('-', $last);
        return (int)$id;
    }
}
