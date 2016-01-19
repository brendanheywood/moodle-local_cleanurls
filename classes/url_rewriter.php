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
 * @package    local
 * @subpackage cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls;

defined('MOODLE_INTERNAL') || die();

use \moodle_url;

/**
 * A clean url rewriter
 */
class url_rewriter {

    /**
     * Convert moodle_urls into clean_moodle_urls if possible
     *
     * @param $url moodle_url a url to potentially rewrite
     * @return moodle_url
     */
    public static function url_rewrite(moodle_url $url) {

        global $CFG;

        if (empty($CFG->upgraderunning)) {
            if (get_config('local_cleanurls', 'cleaningon')) {
                return clean_moodle_url::clean($url);
            }
        }

        return $url;
    }
}

