#!/bin/bash
sudo -H -u www-data bash -c '.cd /var/www/html/modules/sequra/ && composer install'
sudo -H -u www-data bash -c '.cd /var/www/html/modules/sequra/_dev && npm run build'
/var/www/html/bin/console prestashop:module install sequra
/var/www/html/bin/console prestashop:module enable sequra
