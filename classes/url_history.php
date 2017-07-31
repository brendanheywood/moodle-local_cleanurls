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
 * @package     local/cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     local/cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url_history {
    const TABLE = 'local_cleanurls_history';

    /**
     * @param string $clean
     * @param string $unclean
     */
    public static function save($clean, $unclean) {
        global $DB;

        $DB->delete_records(self::TABLE, ['clean' => $clean]);

        $data = (object)['clean' => $clean, 'unclean' => $unclean, 'timemodified' => time()];
        $DB->insert_record(self::TABLE, $data);
    }

    public static function get($clean) {
        global $DB;

        $unclean = $DB->get_field(self::TABLE, 'unclean', ['clean' => $clean]);

        if ($unclean === false) {
            return null;
        }

        return $unclean;
    }
}
