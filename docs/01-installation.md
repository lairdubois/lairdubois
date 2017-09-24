Setting up L'Air du Bois
========================

The following installation instruction suppose that your server is running under a Linux Operating System.
To write this documentation the [Debian 9 "Stretch"](https://www.debian.org) distribution is used.
Else you need to adapt it to your configuration.

## Step 0 - Install required softwares

L'Air du Bois uses some important tools you need to install first.

### Install [MySQL](https://www.mysql.com/) - *The database*

``` bash
    $ sudo apt-get install mysql-server mysql-client
```

### Install [Ningx](https://nginx.org/) - *The webserver*

``` bash
    $ sudo apt-get install nginx
```

### Install [PHP](http://www.php.net/) - *The scripting language*

``` bash
    $ sudo apt-get install apt-transport-https
    $ sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
    $ sudo sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
    $ sudo apt update
```

``` bash
    $ sudo apt-get install php7.1 php7.1-cli php7.1-curl php7.1-intl php7.1-gd php7.1-imagick php7.1-mysql php7.1-fpm php7.0-mbstring php7.1-xml php7.1-zip
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

``` bash
    $ curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -
    $ sudo apt-get install -y nodejs
    $ sudo npm install -g less
```

### Install *Java8* - *Used to run Elesticsearch*

``` bash
    $ apt-get install default-jre

    $ echo "deb http://ppa.launchpad.net/webupd8team/java/ubuntu trusty main" | sudo tee /etc/apt/sources.list.d/webupd8team-java.list
    $ echo "deb-src http://ppa.launchpad.net/webupd8team/java/ubuntu trusty main" | sudo tee -a /etc/apt/sources.list.d/webupd8team-java.list
    $ sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys EEA14886
    $ sudo apt-get update
    $ sudo apt-get install oracle-java8-installer
    $ sudo apt-get install oracle-java8-set-default
```

### Install [Elasticsearch](https://www.elastic.co/products/elasticsearch) - *The search engine*

``` bash
    $ sudo apt-get install apt-transport-https
    $ echo "deb https://artifacts.elastic.co/packages/5.x/apt stable main" | sudo tee -a /etc/apt/sources.list.d/elastic-5.x.list
    $ sudo apt-get update
    $ sudo apt-get install elasticsearch
```

Configure Elasticsearch to automatically start during bootup.

``` bash
    $ sudo /bin/systemctl daemon-reload
    $ sudo /bin/systemctl enable elasticsearch.service
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

## Step 6 - Generate and configure DKIM keys (Not necessary on the **DEV** server)

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

## Step 7 - Setup the database

### Create the database

``` bash
    $ bin/console doctrine:database:create
```

### Build the schema (tables, etc ...)

``` bash
    $ bin/console doctrine:schema:update --force
```

## Step 8 - Compile and Minimize CSS and JS

This step will create `web/js` and `web/css` folders and fill them with compiled and minimized assets. 

``` bash
    $ bin/console assetic:dump
```

## Step 9 - Install bundle's assets

This step will install base assets (fonts, base images, ...) in `web/bundles` folder.

``` bash
    $ bin/console assets:install
```

## Step 10 - Activate cron commands (Not necessary on the **DEV** server)

``` bash
    $ crontab -e
```
And add the following lines

```
*/2 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod swiftmailer:spool:send &> /dev/null
0 4 * * 5 php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:mailing:weeknews --force &> /dev/null
0 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:spotlight --force &> /dev/null
*/2 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:notification:populate --force &> /dev/null
*/30 * * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:notification:email --force &> /dev/null
0 3 * * * php /var/www/www.lairdubois.fr/bin/console --env=prod ladb:cron:sitemaps --force &> /dev/null
```


