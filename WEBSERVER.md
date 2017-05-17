Web Server
----------

While Web Servers usually map a URL to a file, to have the semantic URLs
from CleanURLs working in Moodle it is necessary to tweak that behaviour.

```apache
# APACHE - We will use the mod_rewrite.
<Directory /var/www/moodle> 
   RewriteEngine on     # Enable RewriteEngine on Moodle's wwwroot
   RweriteBase /        # All relative URLs are based from root
   # We will add the configuration here
</Directory>
```

```nginx
# NGINX - We will change URLs are mapped to files.
location / {
    try_files $uri $uri/ =404;
    # We will change the line above.
}
```

To maintain this semantic URLs fully compatible with the old Moodle URLs,
first we need to make sure we do not touch any URL that maps to an existing
file or directory.

```apache
# APACHE
RewriteCond %{REQUEST_FILENAME} !-f # Do not change URLs that point to a file.
RewriteCond %{REQUEST_FILENAME} !-d # Do not change URLs that point to a directory. 
```

```nginx
# NGINX
try_files $uri $uri/;               # Let it try to find a file or a directory for the given URL.
```

At this point, a semantic URL that is not mapped to a file or directory would
return a '404 - Page not found'. We want to intercept that situation and redirect
it to the `local/cleanurls/router.php` file, which will try to find the correct
page for that URL.

```apache
# APACHE
RewriteRule ^(.*)$ local/cleanurls/router.php [L]   # Rewrite any URL to become the Clean URLs router.
                                                    # The [L] flag indicates this is the last rule to rewrite.
```

```nginx
# NGINX
try_files $uri $uri/ /local/cleanurls/router.php;   # Add the Clean URLs router, which will always exist. 
```

With this configuration all '404 - Not found pages' will be redirected to
Clean URLs router. For the router to work, it needs to know what was the
original URL.

A small difference here is that nginx will send the URL with a leading `/`
while Apache will not. This is easily handled by the Clean URLs plugin. 


```apache
# APACHE
RewriteRule ^(.*)$ local/cleanurls/router.php?q=$1       # Sends the matching URL in the RegEx as a GET parameter.
```

```nginx
# NGINX
try_files $uri $uri/ /local/cleanurls/router.php?q=$uri; # Sends the requested URL as a GET parameter.
```

Clean URLs router now is able to define throught the `q` parameter what
was the original URL; however, it lost any other important get parameters
that could have been provided in the original URL. The solution is to
append those parameters back.

```apache
# APACHE
RewriteRule ^(.*)$ local/cleanurls/router.php?q=$1 [L,QSA]     # The QSA adds back the original parameters.
```

```nginx
# NGINX
try_files $uri $uri/ /local/cleanurls/router.php?q=$uri&$args; # Append the original parameters.
```

One last thing for Apache: apache decodes the parameters before parsing them, when
restoring we need to make sure they are properly encoded again.

```apache
# APACHE
RewriteRule ^(.*)$ local/cleanurls/router.php?q=$1 [L,B,QSA]   # The B ensures the parameters are encoded again.
```
