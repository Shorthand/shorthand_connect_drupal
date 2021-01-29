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
docker-compose --file _docker/docker-compose.yml exec web bash -c "composer init --name "shorthand/connect" --quiet && composer require drush/drush:~8"

# Install Sendmail if the image doesn't have it.
docker-compose --file _docker/docker-compose.yml exec web bash -c "apt-get update && apt-get install sendmail -y"


# Install drupal, reset admin password (user admin, password `drupal`) and enable shorthand.
docker-compose --file _docker/docker-compose.yml exec web bash -c "apt-get update && apt-get install -y default-mysql-client vim git && cp sites/default/default.settings.php sites/default/settings.php && chmod 777 sites/default/settings.php && ./vendor/bin/drush -l default site-install standard -y --db-url='mysql://root:rootp@mysql/mysqldb' && ./vendor/bin/drush -l default vset site_name -y 'Shorthand 7' && ./vendor/bin/drush -l default pm-enable -y shorthand && ./vendor/bin/drush -l default  -y upwd --password='drupal' 'admin' && mkdir -p sites/default/files && chmod 777 -R sites/default/files && ./vendor/bin/drush -l default cache-clear all && ./vendor/bin/drush -l 0.0.0.0:7171 user-login"

To edit settings run `vi sites/default/settings.php` inside the container.

# To clear cache.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/drush -l default cache-clear all

# To enable devel module.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/drush -l default pm-enable devel

# To reset password.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/drush -l 0.0.0.0:7171 user-login
```

To run `phpcs` code linting

```
# Install Drupal code.
docker-compose --file _docker/docker-compose.yml exec web composer require drupal/coder

# Setup code linting.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/phpcs --config-set installed_paths /var/www/html/vendor/drupal/coder/coder_sniffer

# Run code linting.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/phpcs -p --standard=DrupalPractice,Drupal --extensions=php,module,inc,install,test,profile,theme /var/www/html/sites/all/modules/custom/shorthand

# Run code fixing.
docker-compose --file _docker/docker-compose.yml exec web ./vendor/bin/phpcbf -p --standard=DrupalPractice,Drupal --extensions=php,module,inc,install,test,profile,theme /var/www/html/sites/all/modules/custom/shorthand
```

To stop and remove containers run

```
docker-compose --file _docker/docker-compose.yml stop
docker-compose --file _docker/docker-compose.yml rm -f
```
