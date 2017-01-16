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

use \moodle_url;

/**
 * A clean url rewriter
 *
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url_rewriter implements \core\output\url_rewriter {

    /**
     * Convert moodle_urls into clean_moodle_urls if possible
     *
     * @param moodle_url $url a url to potentially rewrite
     * @return moodle_url
     */
    public static function url_rewrite(\moodle_url $url) {

        global $CFG;

        if (empty($CFG->upgraderunning)) {
            return clean_moodle_url::clean($url);
        }

        return $url;
    }

    /**
     * Gives a url rewriting plugin a chance to rewrite the current page url
     * avoiding redirects and improving performance.
     *
     * @return void
     */
    public static function html_head_setup() {
        global $CFG, $PAGE;

        $clean = $PAGE->url->out(false);
        $output = '';
        $anchorfixjs = <<<HTML
<script>
document.addEventListener('click', function (event) {
    var element = event.srcElement;
    while (element.tagName != 'A') {
        if (!element.parentElement) {
            return;
        }
        element = element.parentElement;
    }
    if (element.getAttribute('href').charAt(0) == '#') {
        element.href = '$clean' + element.getAttribute('href');
    }
}, true);
</script>
HTML;

        if (isset($CFG->uncleanedurl)) {

            // One issue is that when rewriting urls we change their nesting and depth
            // which means legacy urls in the codebase which do NOT use moodle_url and
            // which are also relative links can be broken. To fix this we set the
            // base href to the original uncleaned url.
            $output .= "<base href='$CFG->uncleanedurl'>\n";

            // Use the canonical URL for anchors, not the base href.
            $output .= $anchorfixjs;

        } else {

            $orig = $PAGE->url->raw_out(false);
            if ($orig != $clean) {
                // One issue is that when rewriting urls we change their nesting and depth
                // which means legacy urls in the codebase which do NOT use moodle_url and
                // which are also relative links can be broken. To fix this we set the
                // base href to our current uncleaned url.
                $output .= "<base href='$orig'>\n";

                // If we have just loaded a legacy url AND we can clean it, instead of
                // cleaning the url, caching it, and waiting for the user or someone
                // else to come back again to see the good url, we can use html5
                // replaceState to fix it imeditately without a page reload.
                //
                // Importantly this needs to happen before any JS on the page uses it,
                // such as any analytics tracking.
                $output .= "<script>history.replaceState && history.replaceState({}, '', '$clean');</script>\n";

                // Use the replaced URL for anchors, not the base href.
                $output .= $anchorfixjs;

                // Now that each page has two valid urls, we need to tell robots like
                // GoogleBot that they are the same, otherwise Google may think they
                // are low quality duplicates and possibly split pagerank between them.
                //
                // We specify that the clean one is the 'canonical' url so this is what
                // will be shown in google search results pages.
                $cleanescaped = $PAGE->url->out(true);
                $output .= "<link rel='canonical' href='$cleanescaped' />\n";

                // At this point the url is already clean, so analytics which run in
                // the page like Google Analytics will only use clean urls and so you
                // will get nice drill down reports etc. However analytics software
                // that parses the apache logs will see the raw original url. Worse
                // it will see some as clean and some as unclean and get inconsistent
                // data. To workaround this we publish an apache note so that we can
                // put the clean url into the logs like this:
                //
                // LogFormat "...  %{CLEANURL}n ... \"%{User-Agent}i\"" ...
                if (function_exists('apache_note')) {
                    apache_note('CLEANURL', $clean);
                }

            }

        }
        return $output;
    }
}
