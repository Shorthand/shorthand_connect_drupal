# Drupal 11 Development Environment with Shorthand 5.0 Beta

A modernized Docker-based development environment for Drupal 11 with the Shorthand plugin 5.0 Beta, featuring SSL support, persistent storage, development tools, and automatic cache management.

## Features

- **Drupal 11** with PHP 8.3
- **Shorthand Plugin 5.0 Beta** pre-installed
- **SSL/HTTPS** support with local certificates
- **Persistent storage** for all data
- **Development tools**:
  - Xdebug for debugging
  - PHPCS with Drupal coding standards
  - Devel module for development
  - Error display and logging
  - Automatic cache refresh capabilities
- **Additional services**:
  - MariaDB 11 database
  - Nginx with SSL
  - Adminer for database management
  - MailHog for email testing

## Prerequisites

- Docker and Docker Compose installed
- SSL certificates from Dylan project (or your own certificates)
- At least 4GB of free disk space

## Quick Start

### 1. Copy SSL Certificates

The certificates have already been copied from the Dylan project:

```bash
# Already done, but if needed again:
cp -a /Users/jamesmiller/github/dylan/ops/ci/nginx/certificates/. ./_docker/certificates/
```

### 2. Add Local Domain (Optional)

Add the following to your `/etc/hosts` file for a custom domain:

```
127.0.0.1 drupal.local
```

### 3. Start the Environment

From the project root directory:

```bash
cd _docker
docker-compose up -d --build
```

Or use the initialization script for automatic setup:

```bash
cd _docker/scripts
./init.sh
```

### 4. Access Your Site

- **HTTPS**: https://localhost or https://drupal.local
- **Admin Login**: admin / drupal
- **Adminer**: http://localhost:8080
- **MailHog**: http://localhost:8025

## Development Workflow

### Managing the Environment

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose stop

# View logs
docker-compose logs -f drupal

# Execute commands in Drupal container
docker-compose exec drupal bash

# Rebuild containers
docker-compose up -d --build
```

### Drupal Commands

```bash
# Clear cache
docker-compose exec drupal drush cr

# Install a module
docker-compose exec drupal composer require drupal/module_name
docker-compose exec drupal drush pm:enable module_name

# Update Shorthand to latest
docker-compose exec drupal composer update drupal/shorthand

# Generate login link
docker-compose exec drupal drush user:login

# Export configuration
docker-compose exec drupal drush config:export

# Import configuration
docker-compose exec drupal drush config:import
```

### Running PHPCS

Check code standards for the Shorthand module:

```bash
cd _docker/scripts
./phpcs.sh
```

Or check a specific path:

```bash
./phpcs.sh /opt/drupal/web/modules/custom/your_module
```

### Development Tools Setup

Enable all development features:

```bash
cd _docker/scripts
./dev-setup.sh
```

This enables:
- Twig debugging
- Error display
- Disabled CSS/JS aggregation
- Disabled render cache
- Devel module configuration

## Configuration

### Environment Variables

Edit `_docker/.env` to customize:

```env
# Database
DB_NAME=drupaldb
DB_USER=drupaluser
DB_PASSWORD=drupalpass

# Drupal Admin
DRUPAL_ADMIN_USER=admin
DRUPAL_ADMIN_PASSWORD=drupal

# Site Info
SITE_NAME=Shorthand Development
SITE_MAIL=admin@drupal.local

# Development
XDEBUG_MODE=develop,debug,coverage
```

### Xdebug Configuration

Xdebug is pre-configured for PHPStorm/VS Code:

- **Port**: 9003
- **IDE Key**: PHPSTORM
- **Host**: host.docker.internal

Configure your IDE to listen on port 9003 for debugging.

### SSL Certificates

Certificates are mounted from `_docker/certificates/`:
- `dylan.crt` - Certificate file
- `dylan.key` - Private key file

## Persistent Data

All data is persisted in Docker volumes:

- `drupal_modules` - Contributed modules
- `drupal_themes` - Themes
- `drupal_sites` - Sites configuration
- `drupal_files` - Public files
- `drupal_private` - Private files
- `db_data` - Database data

## Troubleshooting

### Certificate Warnings

Browser certificate warnings are expected for local development. You can:
1. Accept the certificate temporarily
2. Add the certificate to your system's trusted certificates

### Permission Issues

If you encounter permission issues:

```bash
docker-compose exec drupal bash -c "
  chown -R www-data:www-data /opt/drupal/web/sites/default/files && \
  chmod -R 775 /opt/drupal/web/sites/default/files
"
```

### Database Connection Issues

If Drupal can't connect to the database:

1. Check database container is running:
   ```bash
   docker-compose ps
   ```

2. Test database connection:
   ```bash
   docker-compose exec db mysql -u drupaluser -pdrupalpass drupaldb
   ```

3. Rebuild the database container:
   ```bash
   docker-compose stop db
   docker-compose rm -f db
   docker-compose up -d db
   ```

### Cache Issues

For development, caches are mostly disabled, but if needed:

```bash
# Clear all caches
docker-compose exec drupal drush cr

# Or access rebuild.php in browser
https://localhost/rebuild.php
```

## API Integration with Dylan

To connect to a local Dylan API:

```bash
# Create network bridge (if not exists)
docker network create drupal_dev_network

# Connect Dylan container
docker network connect drupal_dev_network dev_dylan

# Get Dylan container IP
docker network inspect drupal_dev_network

# Add to Drupal container hosts
docker-compose exec drupal bash -c "echo '172.18.0.2   api.dylan.local' >> /etc/hosts"
```

Update Shorthand module configuration to use `https://api.dylan.local/`

## Useful Commands

```bash
# View PHP configuration
docker-compose exec drupal php -i

# Check PHPCS standards
docker-compose exec drupal phpcs -i

# Run Drupal tests
docker-compose exec drupal ../vendor/bin/phpunit

# Database backup
docker-compose exec db mysqldump -u root -prootpassword drupaldb > backup.sql

# Database restore
docker-compose exec -T db mysql -u root -prootpassword drupaldb < backup.sql

# View error logs
docker-compose exec drupal tail -f /var/log/apache2/error.log
```

## Stopping and Cleaning Up

```bash
# Stop containers
docker-compose stop

# Remove containers (keeps data)
docker-compose rm -f

# Remove everything including volumes (CAUTION: deletes all data)
docker-compose down -v

# Clean up unused Docker resources
docker system prune -a
```

## Support

For issues with:
- **Docker setup**: Check Docker logs with `docker-compose logs`
- **Drupal**: Check `/admin/reports/dblog` in the Drupal interface
- **Shorthand module**: Visit https://www.drupal.org/project/shorthand