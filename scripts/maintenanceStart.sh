#!/bin/bash

# NGINX
rm -rf /etc/nginx/sites-enabled/www.lairdubois.fr.conf
ln -s /etc/nginx/sites-available/www.lairdubois.fr-maintenance.conf /etc/nginx/sites-enabled/www.lairdubois.fr.conf
service nginx restart
service php7.3-fpm restart

# SERVICES
service cron stop
service ladb_consumer_view stop
service ladb_consumer_webpush stop
service ladb_websocket stop
