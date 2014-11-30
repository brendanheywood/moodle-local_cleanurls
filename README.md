
Todo

* [ ] live url testing in admin settings page
* [ ] unit tests
* [ ] don't rewrite plugin, theme, js etc links - never seen in browser
* [ ] rewrite course to name
* [ ] rewrite modules using crumb trail heirarchy
* [ ] make route work with /course/ZYX/forum/ - link to index with course id (lookup? or name?)
* [ ] add settings to remove crumb items, or hard code
* [ ] settings to only do 'safe' rewrites or 'extended rewrites'
* [ ] theme header for cannonical
* [ ] theme header for url replace state
* [ ] write a typial page render time
* [ ] use the muc for extended rewrites
* [ ] maximum expensive cleanings per request


# Principals

- backward compatibility

- speed
- human readable
- heirarchical

- every part of the url should work in it's own right 'go up 1 level'


# Use cases:

- human using a site, should know exactly what a url means
- humans should be able to 'hack' a url and have a high chance of going where they want


# Parameters of the solution:

- speed: no redirects
- speed: rewriting can be expensive, when adding info - cache as much as possible, but do it properly



# In an ideal world

- like drupal or other frameworks the url routes would be built in to each and every plugin
at a deep level
- adding this requires a massive overhaul of everything. It could be added as an optional thing
  and added incrementally. In which case a 'best effort' solution needs to put in place anyway


- what about slash arguments in moodle?


# This realistic solution

Strategies of url manipulation:

0) speed: don't rewrite things which aren't seen by the outside world, ie whose url's don't matter


1) simple opaque rewrite which are 100% reversile.

- remove .php extension and then put it back - this is dangerous if a php file and sibling directory have the same name
- remove /index back to just /
- ?id=123 is so common we can rewrite this as just /3


2) Never removing information

- for speed we want to avoid any sort of extra lookup as route time, so retain all information
  needed for routing that was in the original url, does mean /1-hello-world instead of just /hello-world
- just never ever do it
- special case of course id to course name, only possibly because of core router

3) adding redundant information

- instead of /1 we can have /1-name-of-page


4) adding heirarchicaly information

eg instead of /1-blah we can have /course/XYZ/1-blah

- means it is clear where we are at any time
- means it is 'hackable' and we can go'up'

- pull this info from the moodle navigation heirarchy
 - cons - we can only get this info if we are already on this page (too late for creating links)
          or if the page we are on already knows about the linked to page, either because it is
          already in the navigation structure, or we've cached it from previous times


because we ar adding redundant information this can safely be discarded when converting back
however because we have this, there are extra things we can now do, such as if a page is not
found a 404 can be a lot smart about providing a better context for where to direct the user




# Moodle linking to itself

- intercept the moodleurl writer


# intercept and route traffic to where it should go

- custom apache rewrite + php router which is aware of moodle (ie not just a dumb regex in apache land)


# Google, public pages

- add canonincal url in page header

- easier management of robots.txt if you need to


# Humans bookmarking

we want to see the clean url straight away on the first time they visit the page

add html5 replace state for browser


# Analytics, eg Google analytics

make sure we replace the current url before we send it off to GA

- get nice 'drill down' reports
- make

- cons: the very first link to a page will have a different url to subsequent visits, only
 matters if you are tracking outboun links specifically



Application cache
- only cache proper 'clean' urls, not simply the rewritten ones (don't want to cache the half good one)



#
# insert the moodle url intercept

<pre>
diff --git a/lib/weblib.php b/lib/weblib.php
index ff3a3ff..9950526 100644
--- a/lib/weblib.php
+++ b/lib/weblib.php
@@ -79,6 +79,8 @@ define('URL_MATCH_PARAMS', 1);
  */
 define('URL_MATCH_EXACT', 2);
 
+require_once($CFG->dirroot.'/local/clean_urls/lib.php');
+
 // Functions.
 
 /**
@@ -551,6 +553,9 @@ class moodle_url {
         if ($querystring !== '') {
             $uri .= '?' . $querystring;
         }
+        if (get_config('local_clean_urls', 'cleaningon')){
+            $uri = local_clear_urls_clean($uri);
+        }
         if (!is_null($this->anchor)) {
             $uri .= '#'.$this->anchor;
         }
</pre>

# insert the apache rewrite to the router:


# insert the head cleanup in your theme



