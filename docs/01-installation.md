Setting up L'Air du Bois
========================

The following installation instruction suppose that your server is is running on a Linux Operating System. And here a [Debian 8 "Jessie"](https://www.debian.org) distribution is used.
Else you need to adapt it to your needs.

### Step 0 - Install required softwares

Install **Git**

``` bash
    sudo apt-get install git
```

Install **Composer**

``` bash
    sudo curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

Install **NodeJS** and **Less package**

``` bash
    curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -
    sudo apt-get install -y nodejs
    npm install -g less
```

Install **Java**

``` bash
    echo "deb http://ppa.launchpad.net/webupd8team/java/ubuntu trusty main" | sudo tee /etc/apt/sources.list.d/webupd8team-java.list
    echo "deb-src http://ppa.launchpad.net/webupd8team/java/ubuntu trusty main" | sudo tee -a /etc/apt/sources.list.d/webupd8team-java.list
    sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys EEA14886
    sudo apt-get update
    sudo apt-get install oracle-java8-installer
    sudo apt-get install oracle-java8-set-default
```

Install **Elasticsearch**

``` bash
    wget -qO - https://packages.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
    echo "deb http://packages.elastic.co/elasticsearch/1.7/debian stable main" | sudo tee -a /etc/apt/sources.list.d/elasticsearch-1.7.list
    sudo apt-get update
    sudo apt-get install elasticsearch
```

### Step 1 - Create the web root directory

``` bash
    sudo mkdir /var/www/www.lairdubois.fr
    cd /var/www/www.lairdubois.fr
```

### Step 2 - Setup the GIT repository

``` bash
    git init
    git remote add origin https://github.com/lairdubois/lairdubois.git
```

### Step 3 - Clone repository

``` bash
    git pull origin master
```

### Step 4 - Run composer to retrieve vendor dependancies

``` bash
    composer install
```



