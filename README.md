# StorjMap

A 2 hours project using Storj.io bridge API to display online nodes on a Google Map.

## How does it work?

The `storjmap:cache-contacts` gets all Storj nodes from Storj.io bridge API and then ask for IP address geolocation.

The result is cached on filesystem and ready to be served to the Google Maps API as clustered markers.

## Run it

Run the command `bin/console storjmap:cache-contacts` to retrieve Storj online nodes and their geolocation.

If you use Apache, a vhost as follow should do the trick:

```
<VirtualHost *:80>
    ServerName storjmap.tld

    DocumentRoot /path/to/storjmap/web
    <Directory /path/to/storjmap/web>
        AllowOverride None
        Order Allow,Deny
        Allow from All
        <IfModule mod_rewrite.c>
            Options -MultiViews
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^api(.*)$ app.php [QSA,L]
        </IfModule>
    </Directory>

    ErrorLog /var/log/apache2/storjmap_error.log
    CustomLog /var/log/apache2/storjmap_access.log combined
</VirtualHost>
```

## Contribute

It would be nice to display nodes data on click or have a global panel with network statistics. If you are motivated, all
the data needed should be already there, ready to be used! Feel free to fork and enhance :-)
