Setting up L'Air du Bois
========================

The following installation instruction suppose that your server is is running on a Linux Operating System. And here a [Debian 8 "Jessie"](https://www.debian.org) distribution is used.
Else you need to adapt it to your needs.

### Step 0 - Install required softwares

Install Git

    `apt-get install git`

Install composer

    `$ curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer`

### Step 1 - Create the web root directory

    `$ sudo mkdir /var/www/www.lairdubois.fr`
    `$ cd /var/www/www.lairdubois.fr`

### Step 2 - Setup the GIT repository

    `$ git init`
    `$ git remote add origin https://github.com/lairdubois/lairdubois.git`

### Step 3 - Clone repository

    `$ git pull origin master`

### Step 4 - Run composer to retrieve vendor dependancies

    `$ composer install`



