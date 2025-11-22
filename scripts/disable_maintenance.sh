#!/bin/bash

###############################################################################
# Disable Maintenance Mode
###############################################################################

echo "Disabling maintenance mode..."

# Remove maintenance file
if [ -f ".maintenance" ]; then
    rm .maintenance
    echo "✓ Maintenance mode disabled"
    echo "Site is now accessible to all users"
else
    echo "Maintenance mode was not enabled"
fi
