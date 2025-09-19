#!/bin/bash
set -e

# Function to wait for database
wait_for_db() {
    echo "Waiting for database to be ready..."
    while ! mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASSWORD}" -e "SELECT 1" &> /dev/null; do
        echo "Database is not ready yet. Sleeping..."
        sleep 2
    done
    echo "Database is ready!"
}

# Trust certificates
if [ -d "/usr/local/share/ca-certificates" ]; then
    update-ca-certificates
fi

# Set proper permissions
chown -R www-data:www-data /opt/drupal/web/sites/default/files || true
chown -R www-data:www-data /opt/drupal/private || true
chmod -R 775 /opt/drupal/web/sites/default/files || true
chmod -R 775 /opt/drupal/private || true

# Create necessary directories if they don't exist
mkdir -p /opt/drupal/web/sites/default/files
mkdir -p /opt/drupal/private
mkdir -p /opt/drupal/config/sync
mkdir -p /opt/drupal/web/modules/custom

# Copy default settings if not exists
if [ ! -f /opt/drupal/web/sites/default/settings.php ]; then
    cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/default/settings.php
    chmod 644 /opt/drupal/web/sites/default/settings.php
fi

# Include local settings
if [ -f /opt/drupal/web/sites/default/settings.local.php ]; then
    if ! grep -q "settings.local.php" /opt/drupal/web/sites/default/settings.php; then
        echo "" >> /opt/drupal/web/sites/default/settings.php
        echo "if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {" >> /opt/drupal/web/sites/default/settings.php
        echo "  include \$app_root . '/' . \$site_path . '/settings.local.php';" >> /opt/drupal/web/sites/default/settings.php
        echo "}" >> /opt/drupal/web/sites/default/settings.php
    fi
fi

# Create development.services.yml if it doesn't exist
if [ ! -f /opt/drupal/web/sites/development.services.yml ]; then
    cat > /opt/drupal/web/sites/development.services.yml << 'EOF'
services:
  cache.backend.null:
    class: Drupal\Core\Cache\NullBackendFactory
parameters:
  twig.config:
    debug: true
    auto_reload: true
    cache: false
  renderer.config:
    debug: true
  http.response.debug_cacheability_headers: true
EOF
fi

# Check if Drupal is installed
# Default AUTO_INSTALL to true if not set
AUTO_INSTALL=${AUTO_INSTALL:-true}
if [ "${AUTO_INSTALL}" = "true" ]; then
    wait_for_db
    
    cd /opt/drupal
    
    # Check if site is already installed
    if ! drush status --field=bootstrap | grep -q "Successful"; then
        echo "Installing Drupal..."
        drush site:install standard \
            --db-url="mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}/${DB_NAME}" \
            --site-name="${SITE_NAME:-Shorthand Development}" \
            --site-mail="${SITE_MAIL:-admin@drupal.local}" \
            --account-name="${DRUPAL_ADMIN_USER:-admin}" \
            --account-pass="${DRUPAL_ADMIN_PASSWORD:-drupal}" \
            --account-mail="${SITE_MAIL:-admin@drupal.local}" \
            -y
        
        echo "Enabling development modules..."
        drush pm:enable -y \
            devel \
            devel_generate \
            kint \
            webprofiler \
            admin_toolbar \
            admin_toolbar_tools \
            config_inspector \
            field_ui \
            views_ui \
            dblog
        
        echo "Installing Shorthand module..."
        if composer show drupal/shorthand &>/dev/null; then
            echo "Shorthand package already installed"
        else
            composer require 'drupal/shorthand:^5.0@beta' --no-interaction
        fi
        drush pm:enable -y shorthand
        
        echo "Configuring cache settings for development..."
        drush config:set system.performance css.preprocess 0 -y
        drush config:set system.performance js.preprocess 0 -y
        
        echo "Rebuilding cache..."
        drush cache:rebuild
        
        echo "Drupal installation complete!"
        echo "Admin login: ${DRUPAL_ADMIN_USER:-admin} / ${DRUPAL_ADMIN_PASSWORD:-drupal}"
    else
        echo "Drupal is already installed."
    fi
fi

# Execute the original command
exec "$@"