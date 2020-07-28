Drupal 8 docker 

```
# Start environment (from root directory).
docker-compose --file _docker/docker-compose.yml up --detach --build
```
Website is available at [0.0.0.0:8181](http://0.0.0.0:8181).

```
# Install composer.
docker-compose --file _docker/docker-compose.yml exec web bash -c "php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\" && php composer-setup.php --install-dir=/usr/local/bin/ --filename=composer && php -r \"unlink('composer-setup.php');\""

# Install drush.
docker-compose --file _docker/docker-compose.yml exec web composer require drush/drush

# Install drupal, reset admin password (user admin, password `drupal`) and enable shorthand.
docker-compose --file _docker/docker-compose.yml exec web bash -c "apt-get update && apt-get install -y default-mysql-client && cp sites/default/default.settings.php sites/default/settings.php && mkdir -p sites/default/files && chmod 777 -R sites/default/files && ./vendor/bin/drush -l default cache:rebuild && ./vendor/bin/drush -l default site:install standard -y --debug --db-url='mysql://root:rootp@mysql/mysqldb' && ./vendor/bin/drush -l default config:set system.site name -y 'Shorthand 8' && ./vendor/bin/drush -l default pm:enable -y shorthand && ./vendor/bin/drush -l default  -y user:password admin 'drupal' && ./vendor/bin/drush -l default user:login --uri='http://0.0.0.0:8181'"
```
