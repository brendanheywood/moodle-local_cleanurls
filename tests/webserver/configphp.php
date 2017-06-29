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
 * This file is used for the webserver test.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\url_rewriter;

$CFG = new stdClass();
$CFG->clean_urls_please_do_not_unset_me = true;

require(__DIR__ . '/../../../../config.php');

if (isset($CFG->clean_urls_please_do_not_unset_me) && $CFG->clean_urls_please_do_not_unset_me) {
    echo '$CFG OK';
} else {
    echo '$CFG lost';
}
echo "<br />\n";

if (!empty($CFG->urlrewriteclass) && (ltrim($CFG->urlrewriteclass, '\\') == ltrim(url_rewriter::class, '\\'))) {
    echo '$CFG->urlrewriteclass OK';
} else {
    echo '$CFG->urlrewriteclass is not correct';
}
echo "<br />\n";
