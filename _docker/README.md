Drupal 7 docker 

```
# Start environment (from root directory).
docker-compose --file _docker/docker-compose.yml up --detach --build
```
Website is available at [0.0.0.0:7171](http://0.0.0.0:7171).

```
# Install composer.
docker-compose --file _docker/docker-compose.yml exec web bash -c "php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\" && php composer-setup.php --install-dir=/usr/local/bin/ --filename=composer && php -r \"unlink('composer-setup.php');\""

# Install drush.
docker-compose --file _docker/docker-compose.yml exec web bash -c "composer init --quiet && composer require drush/drush:~8"

# Install drupal, reset admin password (user admin, password `drupal`) and enable shorthand.
docker-compose --file _docker/docker-compose.yml exec web bash -c "apt-get update && apt-get install -y default-mysql-client vim && cp sites/default/default.settings.php sites/default/settings.php && chmod 777 sites/default/settings.php && ./vendor/bin/drush -l default site-install standard -y --db-url='mysql://root:rootp@mysql/mysqldb' && ./vendor/bin/drush -l default vset site_name -y 'Shorthand 7' && ./vendor/bin/drush -l default pm-enable -y shorthand && ./vendor/bin/drush -l default  -y upwd --password='drupal' 'admin' && mkdir -p sites/default/files && chmod 777 -R sites/default/files && ./vendor/bin/drush -l default cache-clear all && ./vendor/bin/drush -l 0.0.0.0:7171 user-login"

To edit settings run `vi sites/default/settings.php` inside the container.

# To clear cache.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/drush -l default cache:clear all

# To reset password.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/drush -l 0.0.0.0:7171 user-login
```

To stop and remove containers run

```
docker-compose --file _docker/docker-compose.yml stop
docker-compose --file _docker/docker-compose.yml rm -f
```
