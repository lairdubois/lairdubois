#!/bin/bash

# NGINX
rm -rf /etc/nginx/sites-enabled/www.lairdubois.fr.conf
ln -s /etc/nginx/sites-available/www.lairdubois.fr.conf /etc/nginx/sites-enabled/www.lairdubois.fr.conf
service nginx restart
service php7.3-fpm restart

# SERVICES
service cron start
service ladb_consumer_view start
service ladb_consumer_webpush start
service ladb_websocket start