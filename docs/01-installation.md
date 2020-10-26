Setting up L'Air du Bois
========================

The following installation instruction suppose that your server is running under a Linux Operating System.
To write this documentation the [Debian 10 "Buster"](https://www.debian.org) distribution is used.
Else you need to adapt it to your configuration.

## Step 0 - Install required softwares

L'Air du Bois uses some important tools you need to install first.

### Install Useful Tools

``` bash
    $ sudo apt-get install curl apt-transport-https ghostscript librsvg2-bin lnav unzip
```

### Install [MySQL](https://www.mysql.com/) - *The database*

``` bash
    $ sudo apt-get install mariadb-server mariadb-client
```

``` bash
    $ sudo mysql_secure_installation
```

### Install [Ningx](https://nginx.org/) - *The webserver*

``` bash
    $ sudo apt-get install nginx
```

You can now configure NGINX.

``` bash
    $ sudo nano /etc/nginx/nginx.conf
```

Be sure you activate the following parameters (by uncomment or replace) :

```
    # /etc/nginx/nginx.conf

    server_tokens off;

    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_buffers 16 8k;
    gzip_http_version 1.1;
    gzip_types text/plain text/css application/json application/javascript application/x-javascript text/xml application/xml application/xml+rss text/javascript image/svg+xml;

```

### Install [PHP](http://www.php.net/) - *The scripting language*

``` bash
    $ sudo apt-get install php7.3 php7.3-cli php7.3-curl php7.3-intl php7.3-gd php7.3-imagick php7.3-mysql php7.3-fpm php7.3-mbstring php7.3-xml php7.3-zip php7.3-bz2 php7.3-gmp php7.3-bcmath
```

You can now configure PHP.

``` bash
    $ sudo nano /etc/php/7.3/fpm/php.ini
```

Be sure you activate the following parameters (by uncomment or replace) :

```
    # /etc/php/7.3/fpm/php.ini

    date.timezone = Europe/Paris
    upload_max_filesize = 60M
    post_max_size = 60M
    memory_limit = 256M
    cgi.fix_pathinfo=0

```

Now configure the process management. You need to adapt this to the available RAM on your server.

``` bash
    $ sudo nano /etc/php/7.3/fpm/pool.d/www.conf
```

```
# /etc/php/7.3/fpm/pool.d/www.conf

pm = dynamic
pm.max_children = 100       # The hard-limit total number of processes allowed
pm.start_servers = 20       # When php-fpm starts, have this many processes waiting for requests
pm.min_spare_servers = 10   # Number spare processes php-fpm will create
pm.max_spare_servers = 20   # Max number of spare (waiting for connections) processes allowed to be created

```

Restart PHP FPM.

``` bash
    $ sudo systemctl restart php7.3-fpm
```

### Install [Memcached](https://memcached.org/) - *The distributed memory object caching system*

``` bash
    $ sudo apt-get install memcached
    $ sudo apt-get install php-memcached
```

Restart PHP FPM.

``` bash
    $ sudo systemctl restart php7.3-fpm
```

### Install [Git](https://git-scm.com/) - *The version control system*

``` bash
    $ sudo apt-get install git
```

### Install [Composer](https://getcomposer.org/) - *The dependency manager for PHP*

``` bash
    $ sudo curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

### Install [NodeJS](https://nodejs.org) and **[Less](http://lesscss.org/) package** - *The CSS pre-processor*

As root :

``` bash
    $ sudo apt-get install nodejs npm
    $ sudo npm install -g less
```

### Install *Java8* - *Used to run Elesticsearch*

``` bash
    $ sudo apt-get install default-jre
```

### Install [Elasticsearch](https://www.elastic.co/products/elasticsearch) - *The search engine*

``` bash
    $ wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
    $ echo "deb https://artifacts.elastic.co/packages/5.x/apt stable main" | sudo tee -a /etc/apt/sources.list.d/elastic-5.x.list
    $ sudo apt-get update
```

``` bash
    $ sudo apt-get install elasticsearch
```

Configure Elasticsearch to automatically start during bootup.

``` bash
    $ sudo /bin/systemctl daemon-reload
    $ sudo /bin/systemctl enable elasticsearch.service
```

### Install [RabbitMQ](https://www.rabbitmq.com/) - *The message broker*

``` bash
    $ sudo apt-get install rabbitmq-server
```

If you want to monitor RabbitMQ, enable the management plugin

``` bash
    $ rabbitmq-plugins enable rabbitmq_management
```

### Install [ImageMagick](http://www.imagemagick.org/) - *The image manipulation library*

``` bash
    $ sudo apt-get install imagemagick
```

### Install [pngquant](https://pngquant.org/), [optipng](http://optipng.sourceforge.net/) and [jpegoptim](https://github.com/tjko/jpegoptim) - *The image optimizers*

``` bash
    $ sudo apt-get install pngquant optipng jpegoptim
```

Now you are ready to setup the website itself !


## Step 1 - Create the website root directory

> If you are on the **PROD** server :

``` bash
    $ sudo mkdir /var/www/www.lairdubois.fr
    $ cd /var/www/www.lairdubois.fr
```

> If you are on the **DEV** server :

``` bash
    $ mkdir /var/www/dev.lairdubois.fr
    $ cd /var/www/dev.lairdubois.fr
```

## Step 2 - Setup the GIT repository

``` bash
    $ sudo git init
    $ sudo git remote add origin https://github.com/lairdubois/lairdubois.git
```

## Step 3 - Clone repository

``` bash
    $ sudo git pull origin master
```

## Step 4 - Run composer to retrieve vendor dependencies

L'Air du Bois uses a lot of external libs and bundles. This step permits to automaticaly download them.

``` bash
    $ sudo composer install
```

At the end of the download process, you will be invite to enter configuration parameters (like database server, etc ...).
This will auto generate the `app/config/parameters.yml` file.

Now you are ready to configure Nginx to access to the webroot directory.

## Step 5 - Generate HTTPS certificates (Not necessary on the **DEV** server)

First you need to install certbot.

``` bash
    $ sudo apt-get install certbot python-certbot-nginx
    $ sudo mkdir -p /var/www/.well-known/acme-challenge
```

You can now generate certificates.

``` bash
    $ sudo certbot certonly -n --text --agree-tos --expand --authenticator webroot --server https://acme-v02.api.letsencrypt.org/directory --rsa-key-size 4096 --email contact@lairdubois.fr -d lairdubois.fr -d www.lairdubois.fr -d lairdubois.com -d www.lairdubois.com --webroot-path /var/www
```

## Step 6 - Setup the virtual host on Nginx

> If you are on the **PROD** server :

``` bash
    $ sudo cp /var/www/www.lairdubois.fr/docs/nginx/conf/www.lairdubois.fr-maintenance.conf /etc/nginx/sites-available/www.lairdubois.fr-maintenance.conf
    $ sudo cp /var/www/www.lairdubois.fr/docs/nginx/conf/www.lairdubois.fr.conf /etc/nginx/sites-available/www.lairdubois.fr.conf
    $ sudo ln -s /etc/nginx/sites-available/www.lairdubois.fr.conf /etc/nginx/sites-enabled/www.lairdubois.fr.conf
```

> If you are on the **DEV** server :

Not that the given DEV config is configured for running on MacOS.

``` bash
    $ sudo cp /var/www/dev.lairdubois.fr/docs/nginx/conf/dev.lairdubois.fr.conf /etc/nginx/sites-available/dev.lairdubois.fr.conf
    $ sudo ln -s /etc/nginx/sites-available/dev.lairdubois.fr.conf /etc/nginx/sites-enabled/dev.lairdubois.fr.conf
```

Restart NGINX.

``` bash
    $ sudo systemctl restart nginx
```

## Step 7 - Generate and configure DKIM keys (Not necessary on the **DEV** server)

Emails sended by L'Air du Bois uses DKIM (DomainKeys Identified Mail) email authentication method.
But as you need to add parameter on the DNS record, it may be usefull only on **PROD** server.

``` bash
    $ openssl genrsa -out keys/private.pem 1024
    $ openssl rsa -in keys/private.pem -outform PEM -pubout -out keys/public.pem
```

Copy public key and remove line breaks by the following command !

``` bash
    $ cat keys/public.pem
```

Add the following parameters to DNS TXT record  (tuto : https://www.mailjet.com/docs/1and1-setup-spf-dkim-record)

```
type    = TXT
prefix  = dkim._domainkey               // dkim is facutative
value   = k=rsa; p=[PUBLIC KEY HERE]
```

## Step 8 - Setup the database

### Create the database

``` bash
    $ ./bin/console doctrine:database:create
```

### Build the schema (tables, etc ...)

``` bash
    $ ./bin/console doctrine:schema:update --force
```

### Build session table

Execute the SQL script located at [`docs/database/schema-sessions.sql`](database/schema-sessions.sql).

## Step 9 - Compile and Minimize CSS and JS

This step will create `web/js` and `web/css` folders and fill them with compiled and minimized assets. 

``` bash
    $ ./bin/console assetic:dump
```

## Step 10 - Install bundle's assets

This step will install base assets (fonts, base images, ...) in `web/bundles` folder.

``` bash
    $ ./bin/console assets:install
```

## Step 11 - Initialize Elasticsearch index

This step will create the initial Elasticsearch index.

```bash
    $ ./bin/console fos:elastica:populate
```

## Step 12 - Create a first admin user

This step will create an admin user for the platform. It will prompt you for :
  - a username
  - an email
  - a password

```bash
    $ ./bin/console fos:user:create
```

## Step 13 - Activate cron commands (Not necessary on the **DEV** server)


Create crontab for www-data user
``` bash
    $ sudo crontab -u www-data -e
```

And add the following lines
```
*/2 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod swiftmailer:spool:send --message-limit=100 &> /dev/null
0 4 * * 5 php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:mailing:weeknews --force &> /dev/null
0 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:spotlight --force &> /dev/null
*/2 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:notification:populate --force &> /dev/null
0 7,11,17,20 * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:notification:email --force &> /dev/null
0 3 * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:sitemaps --force &> /dev/null
*/5 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:workflow:thumbnails --force &> /dev/null
0 3 * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:offers --force &> /dev/null
*/5 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:opencutlist:download:analyze --force &> /dev/null
```

## Step 14 - Create services and launch it for background process

### 14.1 The Workflow web socket server.

Create service file
``` bash
    $ sudo nano /etc/systemd/system/ladb_websocket.service
```

And add the following lines
```
[Unit]
Description=The Workflow web socket server.

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/www.lairdubois.fr/
ExecStart=/usr/bin/php bin/console --env=prod gos:websocket:server
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### 14.2 The RabbitMQ view consumer.

Create service file
``` bash
    $ sudo nano /etc/systemd/system/ladb_consumer_view.service
```

And add the following lines
```
[Unit]
Description=The RabbitMQ view consumer.

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/www.lairdubois.fr/
ExecStart=/usr/bin/php bin/console --env=prod rabbitmq:consumer view
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### 14.3 The RabbitMQ webpush notification consumer.

Create service file
``` bash
    $ sudo nano /etc/systemd/system/ladb_consumer_webpush.service
```

And add the following lines
```
[Unit]
Description=The RabbitMQ webpush notification consumer.

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/www.lairdubois.fr/
ExecStart=/usr/bin/php bin/console --env=prod rabbitmq:consumer webpush_notification
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### 14.4 Enable and start Services

Load these new service files
``` bash
    $ sudo systemctl daemon-reload
```

Enable these services on boot
``` bash
    $ sudo systemctl enable ladb_consumer_view.service
    $ sudo systemctl enable ladb_consumer_webpush.service
    $ sudo systemctl enable ladb_websocket.service
```

Start these services
``` bash
    $ sudo systemctl start ladb_consumer_view.service
    $ sudo systemctl start ladb_consumer_webpush.service
    $ sudo systemctl start ladb_websocket.service
```

## Step 15 - Sending emails

Note : The application is able to connect to any SMTP to send emails. See https://github.com/lairdubois/lairdubois/blob/master/app/config/parameters.yml.dist

But if the distant SMTP is not available or if it rejects the connection for any reason (rate limit…) the app will loose the email.

The solution is to send the email throught a local sender which will manage a mailqueue in case of problem.

``` bash
    $ sudo apt-get install postfix libsasl2-modules
```

In the following dialog, choose « Internet site with smarthost »

Then, create or edit `/etc/postfix/sasl_passwd` with the following syntax. (Replace [ XXX ] by relative values):

```
[SMTP_RELAY_HOST] [SMTP_RELAY_USER]:[SMTP_RELAY_PASSWORD]
```

And compile the file:

``` bash
    $ sudo postmap /etc/postfix/sasl_passwd
```

Then, edit `/etc/postfix/main.cf` to add the following lines at the end:

```
relayhost = [SMTP_RELAY_HOST]
smtp_sasl_auth_enable = yes
smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd
smtp_sasl_security_options = noanonymous
```

And restart Postfix:

``` bash
    $ sudo service postfix restart
```

Now, you have a SMTP server accessible in localhost without authentication. You can see sendings logs, and possible errors, in `/var/log/mail.log`.

And don't forget to change the `mailer_host`, `mailer_user` and `mailer_password` in symfony configuration `/app/config/parameters.yml`.

## Step 16 - System configuration tuning

### Local host

In order to map local call to webserver through localhost, edit the `/etc/hosts` file and add the following lines.
This is important so that all calls where url contains `/internal/` will be accessible from the local server.

```
127.0.0.1 lairdubois.fr www.lairdubois.fr
```

## Step 17 - Security

### Firewall

We need at least to configure a minimal firewall

``` bash
    $ sudo apt-get install iptables-persistent
```

Then, edit `/etc/iptables/rules.v4`, remove the content and add the following lines (be careful to replace `ens3` by your network interface name):

<details>
<summary>Click to see the configuration.</summary>
<p>
    
```
 # Generated by iptables-save v1.6.0 on Mon Nov 18 11:03:17 2019
*filter
:INPUT DROP [50603776:12581438471]
:FORWARD DROP [0:0]
:OUTPUT ACCEPT [47320883:84614059861]

-A INPUT ! -i lo -s 127.0.0.0/8 -j DROP
-A INPUT -s 0.0.0.0 -i eth0 -j DROP
-A INPUT -d 0.0.0.0 -i eth0 -j DROP
-A INPUT -s 255.255.255.255 -i eth0 -j DROP
-A INPUT -d 255.255.255.255 -i eth0 -j DROP
-A INPUT -m conntrack --ctstate INVALID -j DROP

-A INPUT -m conntrack --ctstate RELATED,ESTABLISHED -j ACCEPT
-A INPUT -i lo -j ACCEPT
-A OUTPUT -o lo -j ACCEPT

# Blacklist
#-A INPUT -s XXX.XXX.XXX.XXX -j DROP -m comment --comment "31/02/2019 - Attaque DOS"

# Allow estalished traffic
-A OUTPUT -o ens3 -m state --state RELATED,ESTABLISHED -j ACCEPT
-A INPUT -i ens3 -m state --state RELATED,ESTABLISHED -j ACCEPT

# SSH no more 10 attempts in 3 min
-A INPUT -i ens3 -p tcp --dport 22 -m recent --name SSH --update --hitcount 10 --seconds 180 -j DROP
-A INPUT -i ens3 -p tcp --dport 22 -m recent --name SSH --set -j ACCEPT

# ICMP
-A INPUT -p icmp -j ACCEPT
-A INPUT -p icmp --icmp-type echo-request -m limit --limit 1/s -j ACCEPT

# Whitelist
-A INPUT -s 80.67.179.5 -j ACCEPT -m comment --comment "maethor's VPN"

# Incoming HTTP and HTTPS traffic
-A INPUT -p tcp --dport 80 -j ACCEPT
-A INPUT -p tcp --dport 443 -j ACCEPT

# Incoming FTP traffic
#-A INPUT  -p tcp -m tcp --dport 21 -m conntrack --ctstate ESTABLISHED,NEW -j ACCEPT -m comment --comment "Allow ftp connections on port 21"
#-A INPUT  -p tcp -m tcp --dport 20 -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT -m comment --comment "Allow ftp connections on port 20"
#-A INPUT  -p tcp -m tcp --sport 1024: --dport 1024: -m conntrack --ctstate ESTABLISHED -j ACCEPT -m comment --comment "Allow passive inbound connections"
#-A OUTPUT -p tcp -m tcp --dport 21 -m conntrack --ctstate NEW,ESTABLISHED -j ACCEPT -m comment --comment "Allow ftp connections on port 21"
#-A OUTPUT -p tcp -m tcp --dport 20 -m conntrack --ctstate ESTABLISHED -j ACCEPT -m comment --comment "Allow ftp connections on port 20"
#-A OUTPUT -p tcp -m tcp --sport 1024: --dport 1024: -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT -m comment --comment "Allow passive inbound connections"
#
#-A OUTPUT -p tcp -m tcp --sport 7000:8000 --dport 7000:8000 -m conntrack -j ACCEPT -m comment --comment "Allow passive inbound connections"

COMMIT
# Completed on Mon Nov 18 11:03:17 2019
```

</p>
</details>

``` bash
    $ sudo service netfilter-persistent restart
```

## Step 18 - Installing Chromium

The platforme uses Chromium headless in order to auto generate screenshot of shared links in "Trouvailles".

``` bash
    $ sudo apt-get install chromium chromium-l10n
```

## Step 19 - Some useful aliases

To speedup command line typing you can add some aliases on your user bash profile.
/!\ Those aliases works only if current folder is project folder `/var/www/www.lairdubois.fr`.

``` bash
    $ nano ~/.bash_aliases
```

``` bash
alias ladb='sudo --user=www-data bin/console --env=prod'
alias ladb-cc='sudo --user=www-data bin/console --env=prod cache:clear --no-warmup && sudo --user=www-data bin/console --env=prod cache:warmup'
alias ladb-maintenance-start='sudo scripts/maintenanceStart.sh'
alias ladb-maintenance-stop='sudo scripts/maintenanceStop.sh'
alias ladb-git-pull-master='sudo --user=www-data git pull origin master'
alias ladb-log='sudo --user=www-data lnav var/logs/prod.log'
alias ladb-install='sudo --user=www-data composer install'
```
