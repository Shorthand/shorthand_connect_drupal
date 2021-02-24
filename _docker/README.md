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
docker-compose --file _docker/docker-compose.yml exec web composer require --dev drush/drush

# Install drupal, reset admin password (user admin, password `drupal`) and enable shorthand.
docker-compose --file _docker/docker-compose.yml exec web bash -c "apt-get update && apt-get install -y default-mysql-client vim && cp web/sites/default/default.settings.php web/sites/default/settings.php && ./vendor/bin/drush -l default site:install standard -vvv -y --debug --db-url='mysql://root:rootp@mysql/mysqldb' && ./vendor/bin/drush -l default config:set system.site name -y 'Shorthand 8' && ./vendor/bin/drush -l default pm:enable -y shorthand && ./vendor/bin/drush -l default  -y user:password admin 'drupal' && mkdir -p web/sites/default/files && chmod 777 -R web/sites/default/files && ./vendor/bin/drush -l default cache:rebuild && ./vendor/bin/drush -l default user:login --uri='http://0.0.0.0:8181'"

To edit settings run `vi sites/default/settings.php` inside the container.

# To clear cache.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/drush -l default cache:rebuild
```

To run `phpcs` code linting

```
# Install Drupal code.
docker-compose --file _docker/docker-compose.yml exec web composer require drupal/coder

# Setup code linting.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/phpcs --config-set installed_paths /opt/drupal/vendor/drupal/coder/coder_sniffer

# Run code linting.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/phpcs -p --standard=DrupalPractice,Drupal --extensions=php,module,inc,install,test,profile,theme /opt/drupal/web/modules/custom/shorthand

# Run code fixing.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/phpcbf -p --standard=DrupalPractice,Drupal --extensions=php,module,inc,install,test,profile,theme /opt/drupal/web/modules/custom/shorthand
```

To stop and remove containers run

```
docker-compose --file _docker/docker-compose.yml stop
docker-compose --file _docker/docker-compose.yml rm -f
```
