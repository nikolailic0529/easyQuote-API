ARTISAN_PATH="/var/www/easyQuote-API/artisan"

sed "s@{ARTISAN_PATH}@$ARTISAN_PATH@g" eq-horizon.ini > /etc/supervisor.d/conf