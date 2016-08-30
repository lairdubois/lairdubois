Setting up L'Air du Bois
========================

The following installation instruction suppose that your server is running under a Linux Operating System.
To write this documentation the [Debian 8 "Jessie"](https://www.debian.org) distribution is used.
Else you need to adapt it to your configuration.

### Step 0 - Install required softwares

L'Air du Bois uses some important tools you need to install first.

Install [MySQL](https://www.mysql.com/) - *The database*

``` bash
    $ sudo apt-get install mysql-server mysql-client
```

Install [Ningx](https://nginx.org/) - *The webserver*

``` bash
    $ sudo apt-get install nginx
```

Install [PHP](http://www.php.net/) - *The scripting language*

``` bash
    $ sudo apt-get install php5 php5-cli php5-curl php5-intl php5-gd php5-imagick php-apc php5-mysql php5-fpm
```

Install [Git](https://git-scm.com/) - *The version control system *

``` bash
    $ sudo apt-get install git
```

Install [Composer](https://getcomposer.org/) - *The dependency manager for PHP*

``` bash
    $ sudo curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

Install [NodeJS](https://nodejs.org) and **[Less](http://lesscss.org/) package**

``` bash
    $ curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -
    $ sudo apt-get install -y nodejs
    $ npm install -g less
```

Install *Java*

``` bash
    $ echo "deb http://ppa.launchpad.net/webupd8team/java/ubuntu trusty main" | sudo tee /etc/apt/sources.list.d/webupd8team-java.list
    $ echo "deb-src http://ppa.launchpad.net/webupd8team/java/ubuntu trusty main" | sudo tee -a /etc/apt/sources.list.d/webupd8team-java.list
    $ sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys EEA14886
    $ sudo apt-get update
    $ sudo apt-get install oracle-java8-installer
    $ sudo apt-get install oracle-java8-set-default
```

Install [Elasticsearch](https://www.elastic.co/products/elasticsearch) - *The search engine*

``` bash
    $ wget -qO - https://packages.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
    $ echo "deb http://packages.elastic.co/elasticsearch/1.7/debian stable main" | sudo tee -a /etc/apt/sources.list.d/elasticsearch-1.7.list
    $ sudo apt-get update
    $ sudo apt-get install elasticsearch
```

Install [pngquant](https://pngquant.org/), [optipng](http://optipng.sourceforge.net/) and [jpegoptim](https://github.com/tjko/jpegoptim) - *The image optimizers*

``` bash
    $ sudo apt-get install pngquant optipng jpegoptim
```

Now you are ready to setup the website itself !


### Step 1 - Create the website root directory

If you are on the **PROD** server :

``` bash
    $ mkdir /var/www/www.lairdubois.fr
    $ cd /var/www/www.lairdubois.fr
```

If you are on the **DEV** server :

``` bash
    $ mkdir /var/www/dev.lairdubois.fr
    $ cd /var/www/dev.lairdubois.fr
```


### Step 2 - Setup the GIT repository

``` bash
    $ git init
    $ git remote add origin https://github.com/lairdubois/lairdubois.git
```

### Step 3 - Clone repository

``` bash
    $ git pull origin master
```

### Step 4 - Run composer to retrieve vendor dependencies

L'Air du Bois uses a lot of external libs and bundles. This step permits to automaticaly download them.

``` bash
    $ composer install
```

Now you are ready to configure Nginx to acces to the webroot directory.

### Step 5 - Configure Nginx

If you are on the **PROD** server :

``` bash
    $ sudo cp /var/www/www.lairdubois.fr/docs/nginx/conf/www.lairdubois.fr.conf /etc/nginx/sites-available/www.lairdubois.fr.conf
    $ sudo ln -s /etc/nginx/sites-available/www.lairdubois.fr.conf /etc/nginx/sites-enabled/www.lairdubois.fr.conf
    $ service nginx restart
```

If you are on the **DEV** server :

``` bash
    $ sudo cp /var/www/dev.lairdubois.fr/docs/nginx/conf/dev.lairdubois.fr.conf /etc/nginx/sites-available/dev.lairdubois.fr.conf
    $ sudo ln -s /etc/nginx/sites-available/dev.lairdubois.fr.conf /etc/nginx/sites-enabled/dev.lairdubois.fr.conf
    $ service nginx restart
```




