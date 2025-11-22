#!/bin/bash

###############################################################################
# IdeaNest Rollback Script
# Rollback to a previous backup
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check if backup timestamp provided
if [ -z "$1" ]; then
    echo -e "${RED}Error:${NC} Please provide backup timestamp"
    echo "Usage: ./rollback.sh YYYYMMDD_HHMMSS"
    echo ""
    echo "Available backups:"
    ls -1 backups/ | grep "backup_"
    exit 1
fi

BACKUP_TIMESTAMP=$1
BACKUP_PATH="backups/backup_$BACKUP_TIMESTAMP"

# Check if backup exists
if [ ! -d "$BACKUP_PATH" ]; then
    echo -e "${RED}Error:${NC} Backup not found: $BACKUP_PATH"
    exit 1
fi

echo -e "${YELLOW}WARNING:${NC} This will rollback to backup: $BACKUP_TIMESTAMP"
echo "This will restore:"
echo "  - Database"
echo "  - User uploads"
echo "  - Configuration files"
echo ""
read -p "Are you sure? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Rollback cancelled"
    exit 0
fi

echo -e "${GREEN}Starting rollback...${NC}"

# Restore database
if [ -f "$BACKUP_PATH/database.sql" ]; then
    echo "Restoring database..."
    DB_NAME=$(grep DB_NAME .env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_PATH/database.sql"
    echo "Database restored ✓"
fi

# Restore uploads
if [ -f "$BACKUP_PATH/uploads.tar.gz" ]; then
    echo "Restoring uploads..."
    tar -xzf "$BACKUP_PATH/uploads.tar.gz"
    echo "Uploads restored ✓"
fi

# Restore .env
if [ -f "$BACKUP_PATH/.env.backup" ]; then
    echo "Restoring .env..."
    cp "$BACKUP_PATH/.env.backup" .env
    echo ".env restored ✓"
fi

echo -e "${GREEN}Rollback completed successfully!${NC}"
echo "Rolled back to: $BACKUP_TIMESTAMP"
