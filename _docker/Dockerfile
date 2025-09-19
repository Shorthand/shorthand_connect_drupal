FROM drupal:11-php8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    patch \
    mysql-client \
    bash \
    vim \
    curl \
    nodejs \
    npm \
    ca-certificates \
    openssl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Configure PHP for development
RUN { \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 300'; \
    echo 'post_max_size = 100M'; \
    echo 'upload_max_filesize = 100M'; \
    echo 'display_errors = On'; \
    echo 'display_startup_errors = On'; \
    echo 'error_reporting = E_ALL'; \
    echo 'log_errors = On'; \
    echo 'error_log = /dev/stderr'; \
    } > /usr/local/etc/php/conf.d/development.ini

# Copy and trust SSL certificates
COPY certificates /usr/local/share/ca-certificates/
RUN update-ca-certificates

# Set working directory (Drupal image already has Drupal installed)
WORKDIR /opt/drupal

# Install Drush
RUN cd /opt/drupal && \
    composer require drush/drush:^13.0

# Install development modules
RUN cd /opt/drupal && \
    composer require --dev \
        drupal/devel:^5.0 \
        drupal/admin_toolbar:^3.4

# Install PHPCS with Drupal standards
RUN cd /opt/drupal && \
    composer require --dev drupal/coder:^8.3 && \
    vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer

# Create necessary directories
RUN mkdir -p /opt/drupal/web/sites/default/files \
    /opt/drupal/web/sites/default/files/private \
    /opt/drupal/private \
    /opt/drupal/config/sync \
    /opt/drupal/web/modules/custom \
    /opt/drupal/web/themes/custom && \
    chown -R www-data:www-data /opt/drupal

# Copy custom entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set up healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s \
    CMD php-fpm -t || exit 1

# Expose PHP-FPM port
EXPOSE 9000

# Use custom entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]