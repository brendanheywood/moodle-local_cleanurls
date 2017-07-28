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
 * The url_rewriter which should be configured in config.php
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls;

defined('MOODLE_INTERNAL') || die();

use local_cleanurls\local\cleaner\cleaner;
use moodle_url;

/**
 * A clean url rewriter
 *
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url_rewriter implements \core\output\url_rewriter {
    /**
     * Convert moodle_url into clean_moodle_url or returns the original moodle_url if not possible.
     *
     * @param moodle_url $url a url to potentially rewrite
     * @return moodle_url|clean_moodle_url
     */
    public static function url_rewrite(moodle_url $url) {
        global $CFG;

        if (empty($CFG->upgraderunning)) {
            return cleaner::clean($url);
        }

        return $url;
    }

    /**
     * Gives a url rewriting plugin a chance to rewrite the current page url
     * avoiding redirects and improving performance.
     *
     * @return string
     */
    public static function html_head_setup() {
        global $CFG, $ME, $PAGE;

        $output = '';

        if (isset($CFG->uncleanedurl)) {
            // This page came through router uncleaning.
            $clean = $PAGE->url->out(false);
            $output = '';
            $output .= self::get_base_href($CFG->uncleanedurl);
            $output .= self::get_anchor_fix_javascript($clean);
        } else {
            // This page came through its legacy address (not clean version).
            $url = new moodle_url($ME);
            $clean = $url->out(false);
            $orig = $PAGE->url->raw_out(false);
            if ($orig != $clean) {
                // This page URL could have been cleaned up, so do it!
                $output .= self::get_base_href($orig);
                $output .= self::get_replacestate_script($clean);
                $output .= self::get_anchor_fix_javascript($clean);
                $output .= self::get_link_canonical();
                self::mark_apache_note($clean);
            }
        }

        return $output;
    }

    /**
     * Rewire #anchor links dynamically
     *
     * This fixes an edge case bug where in the page there are simple links
     * to internal #hash anchors. But because we add a base href tag these
     * links now appear to link to another page and not this one and cause
     * a reload. So on the fly we detect this and insert the clean url base.
     *
     * @param $clean string
     * @return string
     */
    private static function get_anchor_fix_javascript($clean) {
        return <<<HTML
<script>
document.addEventListener('click', function (event) {
    var element = event.target;
    while (element.tagName != 'A') {
        if (!element.parentElement) {
            return;
        }
        element = element.parentElement;
    }
    if (element.getAttribute('href').charAt(0) == '#') {
        element.href = '{$clean}' + element.getAttribute('href');
    }
}, true);
</script>
HTML;
    }

    /**
     * One issue is that when rewriting urls we change their nesting and depth
     * which means legacy urls in the codebase which do NOT use moodle_url and
     * which are also relative links can be broken. To fix this we set the
     * base href to the original uncleaned url.
     *
     * @param $uncleanedurl string
     * @return string
     */
    private static function get_base_href($uncleanedurl) {
        return "<base href=\"{$uncleanedurl}\">\n";
    }

    /**
     * If we have just loaded a legacy url AND we can clean it, instead of
     * cleaning the url, caching it, and waiting for the user or someone
     * else to come back again to see the good url, we can use html5
     * replaceState to fix it imeditately without a page reload.
     *
     * Importantly this needs to happen before any JS on the page uses it,
     * such as any analytics tracking.
     *
     * @param $clean string
     * @return string
     */
    private static function get_replacestate_script($clean) {
        return "<script>history.replaceState && history.replaceState({}, '', '{$clean}');</script>\n";
    }

    /**
     * Now that each page has two valid urls, we need to tell robots like
     * GoogleBot that they are the same, otherwise Google may think they
     * are low quality duplicates and possibly split pagerank between them.
     *
     * We specify that the clean one is the 'canonical' url so this is what
     * will be shown in google search results pages.
     * @return string
     */
    private static function get_link_canonical() {
        global $PAGE;

        $cleanescaped = $PAGE->url->out(true);
        return "<link rel=\"canonical\" href=\"{$cleanescaped}\" />\n";
    }

    /**
     * At this point the url is already clean, so analytics which run in
     * the page like Google Analytics will only use clean urls and so you
     * will get nice drill down reports etc. However analytics software
     * that parses the apache logs will see the raw original url. Worse
     * it will see some as clean and some as unclean and get inconsistent
     * data. To workaround this we publish an apache note so that we can
     * put the clean url into the logs like this:
     *
     * LogFormat "...  %{CLEANURL}n ... \"%{User-Agent}i\"" ...
     *
     * @param $clean string
     */
    private static function mark_apache_note($clean) {
        if (function_exists('apache_note')) {
            apache_note('CLEANURL', $clean);
        }
    }
}
