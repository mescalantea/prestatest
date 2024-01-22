#!/bin/bash
# Run composer install.
php /usr/local/bin/composer install --working-dir=/var/www/html/modules/sequra/

# Run npm install and build.
cd /var/www/html/modules/sequra/_dev && npm i && npm run build
