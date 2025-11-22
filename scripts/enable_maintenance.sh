#!/bin/bash

###############################################################################
# Enable Maintenance Mode
###############################################################################

echo "Enabling maintenance mode..."

# Create maintenance file
touch .maintenance

echo "✓ Maintenance mode enabled"
echo "Site will show maintenance page to all users except admin IPs"
echo "To disable: ./scripts/disable_maintenance.sh"
