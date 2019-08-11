#!/usr/bin/env bash

ssh-keygen -A

# handle error behavior ba
if [[ "$ERRORS" != "1" ]] ; then
  sed -i -e "s/error_reporting =.*=/error_reporting = E_ALL/g" /usr/etc/php.ini
  sed -i -e "s/display_errors =.*/display_errors = stdout/g" /usr/etc/php.ini
fi

ln -s /usr/etc/php.ini /etc/php.ini

php artisan migrate --force

# super simple process handling, esp restarting on a crash
/usr/bin/supervisord -n -c /etc/supervisord.conf
