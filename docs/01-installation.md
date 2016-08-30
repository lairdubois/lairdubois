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

Now you are ready to setup the website itself !


### Step 1 - Create the website root directory

``` bash
    $ sudo mkdir /var/www/www.lairdubois.fr
    $ cd /var/www/www.lairdubois.fr
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

``` bash
    $ sudo nano /etc/nginx/sites-enabled/www.lairdubois.fr.conf
```

And put the following content inside.

```
geo $banned_ip {
  default           0;
}

server {
    listen                  443 ssl;
    server_name             www.lairdubois.fr;
    root                    /var/www/www.lairdubois.fr/web;
    client_max_body_size    8M;

    ssl_certificate         /etc/letsencrypt/live/lairdubois.fr/fullchain.pem;
    ssl_certificate_key     /etc/letsencrypt/live/lairdubois.fr/privkey.pem;

    # strip app.php/ prefix if it is present
    rewrite ^/app\.php/?(.*)$ /$1 permanent;

    location / {
        index app.php;
        try_files $uri @rewriteapp;
    }

    location ~ ^/media/cache {
        try_files $uri @rewriteapp;
        expires 1y;
        access_log off;
        add_header Cache-Control "public";
    }

    location @rewriteapp {
        if ($banned_ip) {
            rewrite ^(.*)$ /maintenance.html last;
            break;
        }
        rewrite ^(.*)$ /app.php/$1 last;
    }

    location ~ ^/(app|app_dev|config)\.php(/|$) {
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS on;
        fastcgi_param HTTP_SCHEME https;
        fastcgi_param PATH /usr/bin:;
        # Prevents URIs that include the front controller. This will 404:
        # http://domain.tld/app.php/some-path
        # Remove the internal directive to allow URIs like this
        internal;
    }

    # Media: images, icons
    location ~* (?!sticker)\.(?:jpg|jpeg|ico|cur|svg|svgz)$ {
        expires 1y;
        access_log off;
        add_header Cache-Control "public";
    }

    # CSS and Javascript
    location ~* \.(?:css|js)$ {
        expires 1y;
        access_log off;
        add_header Cache-Control "public";
    }

    error_log /var/log/nginx/www.lairdubois.fr_error.log;
    access_log /var/log/nginx/www.lairdubois.fr_access.log;
}

server {
    listen          80;
    listen          [::]:80;
    listen          443 ssl;
    server_name     lairdubois.fr
                    *.lairdubois.fr
                    lairdubois.com
                    *.lairdubois.com;
    return          301 https://www.lairdubois.fr$request_uri;
}
```




