#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Setting up development tools and configurations${NC}"

# Enable Twig debugging
echo -e "${YELLOW}Enabling Twig debugging...${NC}"
docker-compose exec -T drupal bash -c "
    cd /opt/drupal && \
    drush state:set twig_debug 1 && \
    drush state:set twig_auto_reload 1 && \
    drush state:set twig_cache 0
"

# Disable aggregation
echo -e "${YELLOW}Disabling CSS/JS aggregation...${NC}"
docker-compose exec -T drupal bash -c "
    cd /opt/drupal && \
    drush config:set system.performance css.preprocess 0 -y && \
    drush config:set system.performance js.preprocess 0 -y
"

# Disable cache
echo -e "${YELLOW}Disabling render cache...${NC}"
docker-compose exec -T drupal bash -c "
    cd /opt/drupal && \
    drush config:set system.performance cache.page.max_age 0 -y
"

# Enable error display
echo -e "${YELLOW}Enabling error display...${NC}"
docker-compose exec -T drupal bash -c "
    cd /opt/drupal && \
    drush config:set system.logging error_level verbose -y
"

# Enable Devel settings
echo -e "${YELLOW}Configuring Devel module...${NC}"
docker-compose exec -T drupal bash -c "
    cd /opt/drupal && \
    drush config:set devel.settings error_handlers 1 -y 2>/dev/null || true && \
    drush config:set devel.settings dumper kint -y 2>/dev/null || true
"

# Install and configure Stage File Proxy (if external site configured)
if [ ! -z "$STAGE_FILE_PROXY_ORIGIN" ]; then
    echo -e "${YELLOW}Configuring Stage File Proxy...${NC}"
    docker-compose exec -T drupal bash -c "
        cd /opt/drupal && \
        drush pm:enable -y stage_file_proxy && \
        drush config:set stage_file_proxy.settings origin ${STAGE_FILE_PROXY_ORIGIN} -y
    "
fi

# Create rebuild cache access permission
echo -e "${YELLOW}Setting up rebuild cache access...${NC}"
docker-compose exec -T drupal bash -c "
    cd /opt/drupal && \
    drush pm:enable -y rebuild_cache_access 2>/dev/null || true
"

# Clear all caches
echo -e "${YELLOW}Clearing all caches...${NC}"
docker-compose exec -T drupal bash -c "cd /opt/drupal && drush cache:rebuild"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Development setup complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "Development features enabled:"
echo -e "  ✓ Twig debugging"
echo -e "  ✓ Error display (verbose)"
echo -e "  ✓ CSS/JS aggregation disabled"
echo -e "  ✓ Render cache disabled"
echo -e "  ✓ Devel module configured"
echo -e "  ✓ Rebuild cache access enabled"
echo ""
echo -e "${YELLOW}Tips:${NC}"
echo -e "  - Use ${GREEN}drush cr${NC} to clear cache"
echo -e "  - Access ${GREEN}/rebuild.php${NC} to rebuild cache via browser"
echo -e "  - Use Devel's ${GREEN}kint()${NC} function for debugging"
echo -e "  - Check ${GREEN}/admin/reports/dblog${NC} for error logs"