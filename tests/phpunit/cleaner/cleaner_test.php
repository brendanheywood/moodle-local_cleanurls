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
 * Tests for cleaner.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\cache\cleanurls_cache;
use local_cleanurls\local\cleaner\cleaner;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../cleanurls_testcase.php');

/**
 * Tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_cleaner_test extends local_cleanurls_testcase {
    public function test_it_exists() {
        self::assertTrue(class_exists('\local_cleanurls\local\cleaner\cleaner'));
    }

    public function test_it_keeps_trailing_slash() {
        // It is important to keep the trailing slash to avoid a redirect from the webserver.

        $unclean = new moodle_url('/login/index.php?foo=bar');
        $clean = cleaner::clean($unclean);
        self::assertSame('http://www.example.com/moodle/login/?foo=bar', $clean->raw_out());
    }

    public function test_it_adds_to_cache_after_cleaning() {
        self::getDataGenerator()->create_course(['shortname' => 'shortname']);
        $unclean = new moodle_url('/course/view.php?name=shortname');
        cleaner::clean($unclean);

        $clean = cleanurls_cache::get_clean_from_unclean($unclean->raw_out());
        self::assertNotNull($clean);

        $clean = $clean->raw_out();
        self::assertSame('http://www.example.com/moodle/course/shortname', $clean);
    }

    public function test_it_should_never_clean_urls_with_sesskey() {
        global $DB;

        self::getDataGenerator()->create_course(['shortname' => 'shortname']);
        $unclean = new moodle_url('/course/view.php?name=shortname&sesskey=' . sesskey());

        $clean = cleaner::clean($unclean);
        self::assertSame($unclean->raw_out(), $clean->raw_out());

        // It should not pollute the cache.
        $clean = cleanurls_cache::get_clean_from_unclean($unclean->raw_out());
        self::assertNull($clean);

        // It should not pollute the history.
        $unclean = $unclean->raw_out();
        $found = $DB->record_exists('local_cleanurls_history', ['unclean' => $unclean]);
        self::assertFalse($found);
    }

    public function provider_for_test_it_has_a_cleaning_blacklist() {
        return [
            ['/something.js', true],
            ['/something.css', true],
            ['/something.html', true],
            ['/somewhere/index.php', false],
            ['/somewhere/', false],
        ];
    }

    /**
     * @dataProvider provider_for_test_it_has_a_cleaning_blacklist
     */
    public function test_it_has_a_cleaning_blacklist($uncleanpath, $blacklisted) {
        global $DB;
        $unclean = new moodle_url($uncleanpath);

        if ($blacklisted) {
            $clean = cleaner::clean($unclean);
            self::assertSame($unclean->raw_out(), $clean->raw_out(), "It should not clean: {$uncleanpath}");

            $clean = cleanurls_cache::get_clean_from_unclean($unclean->raw_out());
            self::assertNull($clean, "It should not pollute the cache: {$uncleanpath}");

            $uncleanraw = $unclean->raw_out();
            $found = $DB->record_exists('local_cleanurls_history', ['unclean' => $uncleanraw]);
            self::assertFalse($found, "It should not pollute the history: {$uncleanpath}");
        }

        // Now add a version to cache, blacklisted paths shouldn't even try to use it.
        cleanurls_cache::save_clean_for_unclean($unclean, new moodle_url('/fakeclean'));
        $clean = cleaner::clean($unclean);

        if ($blacklisted) {
            self::assertSame($unclean->raw_out(), $clean->raw_out(), "It should not try to get from cache: {$uncleanpath}");
        } else {
            self::assertSame('http://www.example.com/moodle/fakeclean', $clean->raw_out(), "It have gotten from cache: {$uncleanpath}");
        }
    }
}
