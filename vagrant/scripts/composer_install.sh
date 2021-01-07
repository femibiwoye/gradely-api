#!/bin/bash

sudo chmod -R 775 /var/www/test/tapi.gradely.ng
cd /var/www/test/tapi.gradely.ng
composer update
cp /var/www/test/index.php /var/www/test/tapi.gradely.ng/web
cp /var/www/test/var.php /var/www/test/tapi.gradely.ng/config