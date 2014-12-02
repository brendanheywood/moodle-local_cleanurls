* [Design principals](#design-principals)
* [How it works](#how-it-works)
* [Installation](#installation)

Design principals
=================

Backward compatibility
----------------------
URL's must always work, old and new. Old url's should be seamlessly upgraded to new url's where possible.


Speed is king
-------------
Speed is an integral part of the user experience. So we want to avoid things like 302 redirects, cache internally any expensive processing.


Human readable
--------------
A typical moodle url looks like this:

/mod/forum/view.php?id=6

This is fairly opaque and tells us very little. We should add extra information into the url to make it readable, giving it context, whilst at the same time removing extaneous information such as the php extension. eg

/course/MATH101/lesson/6-how-to-do-matrix-multiplication

Note we have also added redundant heirarchical information, ie the course path compents. This immediately gives context, but is also useful to non humans, such as for Google Analytics to create 'dill down' reports.

Automatic
---------
Moodle already has rich meta data which we can leverage to produce clean url's. We don't want the site admins, let along the teachers, to have to do anything extra. It should Just Work.

This realistic solution
-----------------------

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


# intercept and route traffic to where it should go

- custom apache rewrite + php router which is aware of moodle (ie not just a dumb regex in apache land)



How it works
============

Rewrite outgoing links
----------------------

This plugin add a very small hack to the moodle_url->out() method which cleans the links that moodle
renders onto a page. It applies a variety of safe tranformations, and if the more aggressive settings
are on, it applies some much deeper tranformations by reaching into the moodle navigation heirarchy to
add extra redundant path elements to the url. Unfortunely we can often read this information until we
are on the page that uses them, or a nearby page, so the first time we render that page we clean the
url and cache it for next time.

Rewrite incoming links
----------------------

Incoming links are diverted by an apache rewrite rule to router.php, which then uncleans the url and
passes it back into moodle which doesn't know anything was different.

Base href
---------
TODO

Not every moodle link uses moodle_url, and some may also use relative links. Because the clean url may be
wildy different to the original these legacy link will break. To mitigate this we add a base href tag of
the original url.


Cannoncial link
---------------
TODO

If a robot like google is scraping your page, we don't want to split the pagerank between the old
and clean url, and we want to ensure that google always sends people to the clean url. We acheive
this by rendering a 'canonical' link in the HTML head. This is similar to a 302 redirect but just
for robots, and doesn't incur a roundtrip penalty.

http://en.wikipedia.org/wiki/Canonical_link_element

This also now makes it much easier to manage parts of your site using robots.txt


history.replaceState
--------------------
TODO

The are many ways a url gets shared, copy and paste, a 'share' widget etc. We want the url to be
correct as soon as possibly, so even if the link we clicked on was an normal moodle url, we replace
this as soon as possible using html5 history.replaceState()

We also need to do this early, before things that use the url such as a Google Analytics tracking
code. We want the url's to be nice in GA so we get clean 'drill down' report etc

The only down side to this approach is if you have outbound link tracking on the referring page.

https://developer.mozilla.org/en-US/docs/Web/Guide/API/DOM/Manipulating_the_browser_history#The_replaceState()_method


Installation
============

Install the local module
------------------------

eg using git submodule:

```shell
git submodule add git@github.com:brendanheywood/moodle-local_clean_urls.git local/clean_urls
```


Edit /lib/weblb.php to intercept moodle_url serialization
---------------------------------------------------------


```diff
diff --git a/lib/weblib.php b/lib/weblib.php
index ff3a3ff..8baf2d2 100644
--- a/lib/weblib.php
+++ b/lib/weblib.php
@@ -79,6 +79,7 @@ define('URL_MATCH_PARAMS', 1);
  */
 define('URL_MATCH_EXACT', 2);
 
+require_once($CFG->dirroot.'/local/clean_urls/lib.php');
 // Functions.
 
 /**
@@ -541,6 +542,13 @@ class moodle_url {
      * @return string Resulting URL
      */
     public function out($escaped = true, array $overrideparams = null) {
+        $murl = $this;
+        if (get_config('local_clean_urls', 'cleaningon')){
+            $murl = clean_moodle_url::clean($murl);
+        }
+        return $murl->orig_out($escaped, $overrideparams);
+    }
+    public function orig_out($escaped = true, array $overrideparams = null) {
         if (!is_bool($escaped)) {
             debugging('Escape parameter must be of type boolean, '.gettype($escaped).' given instead.');
         }
```

Add an apache rewrite to the custom router
------------------------------------------


```apache
<Directory /var/www/moodle>
   RewriteEngine on
   RewriteBase /
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ local/clean_urls/router.php?q=$1 [L,QSA]
</Directory>
```

Add the head tag cleanup to your theme
--------------------------------------


Todo
====

* [ ] live url testing in admin settings page
* [ ] rewrite modules using crumb trail heirarchy
* [ ] make route work with /course/ZYX/forum/ - link to index with course id (lookup? or name?)
* [ ] add settings to remove crumb items, or hard code
* [ ] settings to only do 'safe' rewrites or 'extended rewrites'
* [ ] write a typial page render time
* [ ] use the muc for extended rewrites
* [ ] maximum expensive cleanings per request


Contributing
============

* Pull requests welcome!
* You may find that certain links in moodle core, or particular plugins don't use moodle_url - so best patch them and push back upstream





