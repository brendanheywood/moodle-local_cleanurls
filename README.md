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
Speed is an integral part of the user experience. So we want to avoid things like 302 redirects, cache internally any expensive processing. If a url is never going to be seen by an end user, then avoid cleaning it.

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

Step 1: Install the local module
--------------------------------

eg using git submodule:

```shell
git submodule add git@github.com:brendanheywood/moodle-local_cleanurls.git local/cleanurls
```


Step 2: Edit /lib/weblb.php to intercept moodle_url serialization
-----------------------------------------------------------------

Apply a tiny patch to core using git:

```
git apply local/cleanurls/weblib.patch
```


Step 3: Add the head tag cleanup to your theme
----------------------------------------------


Step 4: Add the apache rewrite to the custom router
---------------------------------------------------

```apache
<Directory /var/www/your/moodle/path>
   RewriteEngine on
   RewriteBase /
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ local/cleanurls/router.php?q=$1 [L,QSA]
</Directory>
```

Now restart apache

Go to the /admin/settings.php?section=local_cleanurls settings page and it should
show a green success message if it detects the route rewrite is in place and working.

Now you can Tick the box turning on the rewrites. If you have any issues then turn
on the rewrite logging and tail your apache log for details.


Todo
====

* [ ] rewrite modules using crumb trail heirarchy
* [ ] make route work with /course/ZYX/forum/ - link to index with course id (lookup? or name?)
* [ ] add settings to remove crumb items, or hard code
* [ ] settings to only do 'safe' rewrites or 'extended rewrites'
* [ ] write a typial page render time
* [ ] use the muc for extended rewrites
* [ ] maximum expensive cleanings per request
* [ ] add an admin debug page which shows all urls in the cache - can we even do this?
* [ ] almost all id's can't change, but course shortcodes CAN! test how this behaves and make a event trigger fix the index / cache
* [ ] also do this for usernames as well


Contributing
============

* Pull requests welcome!
* You may find that certain links in moodle core, or particular plugins don't use moodle_url - so best patch them and push back upstream


