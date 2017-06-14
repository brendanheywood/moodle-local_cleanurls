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
 * Testcase for Clean URLs.
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\clean_moodle_url;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/mocks/unittest_uncleaner.php');
require_once(__DIR__ . '/mocks/external_uncleaner.php');
require_once(__DIR__ . '/mocks/fakeformat_uncleaner.php');
require_once(__DIR__ . '/mocks/cleanurls_support.php');

/**
 * Testcase for Clean URLs.
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_testcase extends advanced_testcase {
    /**
     * Configures and enables Clean URLs.
     *
     * This can be called by other plugins when initializing unit tests for Clean URLs.
     */
    public static function enable_cleanurls() {
        global $CFG;
        $CFG->wwwroot = 'http://www.example.com/moodle'; // Make it consistent across different Moodle versions.
        $CFG->urlrewriteclass = local_cleanurls\url_rewriter::class;
        set_config('enableurlrewrite', 1);
        set_config('cleaningon', true, 'local_cleanurls');
        set_config('cleanusernames', true, 'local_cleanurls');
    }

    /**
     * Ensures the input URL can be cleaned and possibly uncleaned.
     *
     * If $input and $expectedcleaned are the same, it means that the URL is not supposed to be cleaned
     * and it will not test the uncleaning.
     *
     * @param string      $input             URL to test.
     * @param string|null $expectedcleaned   How is the URL supposed to be cleaned.
     * @param string|null $expecteduncleaned If not provided, it should unclean back to the input URL.
     */
    public static function assert_clean_unclean($input, $expectedcleaned = null, $expecteduncleaned = null) {
        $inputurl = new moodle_url($input);
        $clean = clean_moodle_url::clean($inputurl);
        self::assertInstanceOf(moodle_url::class, $clean);
        self::assertSame($expectedcleaned, $clean->out(false), 'Failed CLEANING.');

        if ($input === $expectedcleaned) {
            return; // The URL was not cleaned, do not test uncleaning it.
        }

        $unclean = clean_moodle_url::unclean($clean);
        self::assertInstanceOf(moodle_url::class, $unclean);

        if (is_null($expecteduncleaned)) {
            // Ensure test dos not fail because of parameter order.
            foreach ([$inputurl, $unclean] as $url) {
                $params = $url->params();
                ksort($params);
                $url->remove_all_params();
                $url->params($params);
            }
            $expecteduncleaned = $inputurl->raw_out(false);
        }

        self::assertSame($expecteduncleaned, $unclean->raw_out(false), 'Failed UNCLEANING.');
    }

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        local_cleanurls_unittest_uncleaner::reset();
        static::enable_cleanurls();
    }
}
