Web Server
----------

While Web Servers usually map a URL to a file, to have the semantic URLs
from CleanURLs working in Moodle it is necessary to tweak that behaviour.

We need to:

* Keep the old URLs working - if the URL maps to a file, use it.
* Redirect the requests that would be a 404 to `local/cleanurls/router.php`.
* Add a query parameter `q` with the original requested URL.
* Append (keep) all previous parameters if existing.

If using apache, this is the suggested configuration:

```apache
# Replace the path below with your Moodle wwwroot.
<Directory /var/www/moodle>
    # Enable RewriteEngine
    RewriteEngine on
    # All relative URLs are based from root
    RewriteBase /
    # Do not change URLs that point to an existing file.
    RewriteCond %{REQUEST_FILENAME} !-f
    # Do not change URLs that point to an existing directory.
    RewriteCond %{REQUEST_FILENAME} !-d

    # Rewrite URLs matching ^(.*)$ as $1 - this means all URLs.
    # Rewrite it to the cleanurls router
    # Use ?q=$1 to forward the original URL as a query parameter
    # Use the flags:
    # - L (do not continue rewriting)
    # - B (encode back the parameters)
    # - QSA (append the original query string parameters)
    RewriteRule ^(.*)$ local/cleanurls/router.php?q=$1 [L,B,QSA]
</Directory>
```

If using nginx, all you need to do is add one more `try_files` entry pointing to the router, as follows:

```nginx
location / {
    # For more details, see: http://nginx.org/en/docs/varindex.html
    try_files $uri $uri/ /local/cleanurls/router.php?q=$uri&$args;
}
```

Reminder: nginx addresses will have a '/' at the beginning of the URL, whereas Apache will not.
This is addressed in the Clean URLs plugin by simply trimming the initial slashes.
