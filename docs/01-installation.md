Setting up L'Air du Bois
========================

The following installation instruction suppose that your server is running under a Linux Operating System.
To write this documentation the [Debian 9 "Stretch"](https://www.debian.org) distribution is used.
Else you need to adapt it to your configuration.

## Step 0 - Install required softwares

L'Air du Bois uses some important tools you need to install first.

### Install Useful Tools

``` bash
    $ sudo apt-get install curl apt-transport-https ghostscript librsvg2-bin
```

### Install [MySQL](https://www.mysql.com/) - *The database*

``` bash
    $ sudo apt-get install mariadb-server mariadb-client
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
    $ sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
    $ sudo sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
    $ sudo apt update
```

``` bash
    $ sudo apt-get install php7.1 php7.1-cli php7.1-curl php7.1-intl php7.1-gd php7.1-imagick php7.1-mysql php7.1-fpm php7.1-mbstring php7.1-xml php7.1-zip php7.1-bz2 php7.1-gmp php7.1-bcmath
```

You can now configure PHP.

``` bash
    $ sudo nano /etc/php/7.1/fpm/php.ini
```

Be sure you activate the following parameters (by uncomment or replace) :

```
    # /etc/php/7.1/fpm/php.ini

    date.timezone = Europe/Paris
    upload_max_filesize = 60M
    post_max_size = 60M
    memory_limit = 256M
    cgi.fix_pathinfo=0

```

Now configure the process management. You need to adapt this to the available RAM on your server.

``` bash
    $ sudo nano /etc/php/7.1/fpm/pool.d/www.conf
```

```
# /etc/php/7.1/fpm/pool.d/www.conf

pm = dynamic
pm.max_children = 100       # The hard-limit total number of processes allowed
pm.start_servers = 20       # When php-fpm starts, have this many processes waiting for requests
pm.min_spare_servers = 10   # Number spare processes php-fpm will create
pm.max_spare_servers = 20   # Max number of spare (waiting for connections) processes allowed to be created

```

Restart PHP FPM.

``` bash
    $ sudo service php7.1-fpm restart
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
    $ curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -
    $ apt-get install -y nodejs
    $ npm install -g less
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

## Step 5 - Setup the virtual host on Nginx

> If you are on the **PROD** server :

``` bash
    $ sudo cp /var/www/www.lairdubois.fr/docs/nginx/conf/www.lairdubois.fr-maintenance.conf /etc/nginx/sites-available/www.lairdubois.fr-maintenance.conf
    $ sudo cp /var/www/www.lairdubois.fr/docs/nginx/conf/www.lairdubois.fr.conf /etc/nginx/sites-available/www.lairdubois.fr.conf
    $ sudo ln -s /etc/nginx/sites-available/www.lairdubois.fr.conf /etc/nginx/sites-enabled/www.lairdubois.fr.conf
    $ service nginx restart
```

> If you are on the **DEV** server :

Not that the given DEV config is configured for running on MacOS.

``` bash
    $ sudo cp /var/www/dev.lairdubois.fr/docs/nginx/conf/dev.lairdubois.fr.conf /etc/nginx/sites-available/dev.lairdubois.fr.conf
    $ sudo ln -s /etc/nginx/sites-available/dev.lairdubois.fr.conf /etc/nginx/sites-enabled/dev.lairdubois.fr.conf
    $ service nginx restart
```

## Step 6 - Generate HTTPS certificates (Not necessary on the **DEV** server)

First you need to install certbot.

``` bash
    $ sudo apt-get install certbot
```

Before generate the certificates, you need to stop NGINX.

``` bash
    $ sudo service nginx stop
```

You can now generate certificates.

``` bash
    $ certbot certonly --standalone --email contact@lairdubois.fr -d lairdubois.fr -d www.lairdubois.fr -d lairdubois.com -d www.lairdubois.com
```

Restart NGINX.

``` bash
    $ sudo service nginx start
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
    $ bin/console doctrine:database:create
```

### Build the schema (tables, etc ...)

``` bash
    $ bin/console doctrine:schema:update --force
```

### Build session table

``` bash
CREATE TABLE `sessions` (
    `sess_id` VARCHAR(128) NOT NULL PRIMARY KEY,
    `sess_data` BLOB NOT NULL,
    `sess_time` INTEGER UNSIGNED NOT NULL,
    `sess_lifetime` MEDIUMINT NOT NULL
) COLLATE utf8_bin, ENGINE = InnoDB;
```

## Step 8 - Compile and Minimize CSS and JS

This step will create `web/js` and `web/css` folders and fill them with compiled and minimized assets. 

``` bash
    $ bin/console assetic:dump
```

## Step 10 - Install bundle's assets

This step will install base assets (fonts, base images, ...) in `web/bundles` folder.

``` bash
    $ bin/console assets:install
```

## Step 11 - Activate cron commands (Not necessary on the **DEV** server)

``` bash
    $ sudo crontab -e
```

And add the following lines

```
*/2 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod swiftmailer:spool:send &> /dev/null
0 4 * * 5 php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:mailing:weeknews --force &> /dev/null
0 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:spotlight --force &> /dev/null
*/2 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:notification:populate --force &> /dev/null
*/30 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:notification:email --force &> /dev/null
0 3 * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:sitemaps --force &> /dev/null
*/5 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:workflow:thumbnails --force &> /dev/null
```

## Setp 12 - Launch background tasks

``` bash
    $ sudo bin/console --env=prod gos:websocket:server &
    $ sudo bin/console --env=prod rabbitmq:consumer view &
    $ sudo bin/console --env=prod rabbitmq:consumer webpush_notification &
```
