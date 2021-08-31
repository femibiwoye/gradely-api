#!/bin/bash

cd /var/www/php/test/tapi
rm -rf vendor/
rm composer.lock
composer update




#sudo chmod -R 777 /var/www/test/tapi.gradely.ng
#cd /var/www/test/tapi.gradely.ng
#composer update
#cp /var/www/test/index.php /var/www/test/tapi.gradely.ng/web
#cp /var/www/test/var.php /var/www/test/tapi.gradely.ng/config