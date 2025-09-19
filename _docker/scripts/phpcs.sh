#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default path to check
MODULE_PATH="/opt/drupal/web/modules/custom/shorthand"

# Check if custom path is provided
if [ ! -z "$1" ]; then
    MODULE_PATH="$1"
fi

echo -e "${GREEN}Running PHP Code Sniffer on Shorthand module${NC}"
echo -e "Path: ${MODULE_PATH}"
echo ""

# Run PHPCS
echo -e "${YELLOW}Checking code standards...${NC}"
docker-compose exec -T drupal bash -c "
    phpcs -p \
        --standard=Drupal,DrupalPractice \
        --extensions=php,module,inc,install,test,profile,theme,info,txt,md,yml \
        --ignore=*/vendor/*,*/node_modules/* \
        ${MODULE_PATH}
"

PHPCS_EXIT_CODE=$?

if [ $PHPCS_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ No coding standards violations found!${NC}"
else
    echo ""
    echo -e "${YELLOW}Found coding standards violations.${NC}"
    echo -e "${YELLOW}Would you like to automatically fix them? (y/n)${NC}"
    read -r response
    
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        echo -e "${GREEN}Running PHP Code Beautifier and Fixer...${NC}"
        docker-compose exec -T drupal bash -c "
            phpcbf -p \
                --standard=Drupal,DrupalPractice \
                --extensions=php,module,inc,install,test,profile,theme,info,txt,md,yml \
                --ignore=*/vendor/*,*/node_modules/* \
                ${MODULE_PATH}
        "
        
        echo ""
        echo -e "${GREEN}Automatic fixes applied. Re-running PHPCS to check remaining issues...${NC}"
        docker-compose exec -T drupal bash -c "
            phpcs -p \
                --standard=Drupal,DrupalPractice \
                --extensions=php,module,inc,install,test,profile,theme,info,txt,md,yml \
                --ignore=*/vendor/*,*/node_modules/* \
                ${MODULE_PATH}
        "
        
        FINAL_EXIT_CODE=$?
        if [ $FINAL_EXIT_CODE -eq 0 ]; then
            echo -e "${GREEN}✓ All fixable issues have been resolved!${NC}"
        else
            echo -e "${YELLOW}Some issues require manual fixing.${NC}"
        fi
    fi
fi