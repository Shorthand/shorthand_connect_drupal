#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting Drupal Development Environment Setup${NC}"

# Load environment variables
if [ -f ../.env ]; then
    export $(cat ../.env | grep -v '^#' | xargs)
fi

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Docker is not running. Please start Docker first.${NC}"
    exit 1
fi

# Build and start containers
echo -e "${YELLOW}Building Docker containers...${NC}"
docker-compose up -d --build

# Wait for containers to be ready
echo -e "${YELLOW}Waiting for containers to be ready...${NC}"
sleep 10

# Wait for database
echo -e "${YELLOW}Waiting for database...${NC}"
until docker-compose exec -T db mysql -u${DB_USER:-drupaluser} -p${DB_PASSWORD:-drupalpass} -e "SELECT 1" &> /dev/null; do
    echo "Database is not ready yet. Waiting..."
    sleep 2
done
echo -e "${GREEN}Database is ready!${NC}"

# Install Drupal
echo -e "${YELLOW}Installing Drupal...${NC}"
docker-compose exec -T drupal bash -c "
    cd /opt/drupal && \
    drush site:install standard \
        --db-url='mysql://${DB_USER:-drupaluser}:${DB_PASSWORD:-drupalpass}@db/${DB_NAME:-drupaldb}' \
        --site-name='${SITE_NAME:-Shorthand Development}' \
        --site-mail='${SITE_MAIL:-admin@drupal.local}' \
        --account-name='${DRUPAL_ADMIN_USER:-admin}' \
        --account-pass='${DRUPAL_ADMIN_PASSWORD:-drupal}' \
        --account-mail='${SITE_MAIL:-admin@drupal.local}' \
        -y
"

# Enable development modules
echo -e "${YELLOW}Enabling development modules...${NC}"
docker-compose exec -T drupal bash -c "
    cd /opt/drupal && \
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
"

# Install Shorthand 5.0 Beta
echo -e "${YELLOW}Installing Shorthand 5.0 Beta...${NC}"
docker-compose exec -T drupal bash -c "
    cd /opt/drupal && \
    composer require 'drupal/shorthand:^5.0@beta' --no-interaction && \
    drush pm:enable -y shorthand
"

# Configure development settings
echo -e "${YELLOW}Configuring development settings...${NC}"
docker-compose exec -T drupal bash -c "
    cd /opt/drupal && \
    drush config:set system.performance css.preprocess 0 -y && \
    drush config:set system.performance js.preprocess 0 -y && \
    drush config:set system.logging error_level verbose -y
"

# Set proper permissions
echo -e "${YELLOW}Setting permissions...${NC}"
docker-compose exec -T drupal bash -c "
    chown -R www-data:www-data /opt/drupal/web/sites/default/files && \
    chmod -R 775 /opt/drupal/web/sites/default/files && \
    chown -R www-data:www-data /opt/drupal/private && \
    chmod -R 775 /opt/drupal/private
"

# Clear cache
echo -e "${YELLOW}Clearing cache...${NC}"
docker-compose exec -T drupal bash -c "cd /opt/drupal && drush cache:rebuild"

# Generate login link
echo -e "${YELLOW}Generating login link...${NC}"
LOGIN_URL=$(docker-compose exec -T drupal bash -c "cd /opt/drupal && drush user:login --uri=https://localhost" | tr -d '\r')

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Drupal Development Environment is Ready!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "Access your site at:"
echo -e "  ${GREEN}https://localhost${NC}"
echo -e "  ${GREEN}https://drupal.local${NC} (add to /etc/hosts: 127.0.0.1 drupal.local)"
echo ""
echo -e "Admin credentials:"
echo -e "  Username: ${GREEN}${DRUPAL_ADMIN_USER:-admin}${NC}"
echo -e "  Password: ${GREEN}${DRUPAL_ADMIN_PASSWORD:-drupal}${NC}"
echo ""
echo -e "One-time login link:"
echo -e "  ${GREEN}${LOGIN_URL}${NC}"
echo ""
echo -e "Additional services:"
echo -e "  Adminer (Database): ${GREEN}http://localhost:8080${NC}"
echo -e "  MailHog (Email):    ${GREEN}http://localhost:8025${NC}"
echo ""
echo -e "${YELLOW}Note: You may see a certificate warning in your browser. This is expected for local development.${NC}"