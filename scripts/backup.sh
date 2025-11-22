#!/bin/bash

###############################################################################
# IdeaNest Backup Script
# Creates backup of database and files
###############################################################################

set -e

# Configuration
BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_PATH="$BACKUP_DIR/backup_$TIMESTAMP"
RETENTION_DAYS=30

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Starting backup...${NC}"

# Create backup directory
mkdir -p "$BACKUP_PATH"

# Load database credentials
if [ -f ".env" ]; then
    DB_NAME=$(grep DB_NAME .env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    
    # Backup database
    echo "Backing up database: $DB_NAME"
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH/database.sql" 2>/dev/null
    
    # Compress database backup
    gzip "$BACKUP_PATH/database.sql"
    echo "✓ Database backed up"
fi

# Backup uploads
if [ -d "user/uploads" ]; then
    echo "Backing up user uploads..."
    tar -czf "$BACKUP_PATH/uploads.tar.gz" user/uploads/ user/forms/uploads/ user/profile_pictures/ 2>/dev/null
    echo "✓ Uploads backed up"
fi

# Backup configuration
echo "Backing up configuration..."
cp .env "$BACKUP_PATH/.env.backup" 2>/dev/null || true
tar -czf "$BACKUP_PATH/config.tar.gz" config/ 2>/dev/null
echo "✓ Configuration backed up"

# Backup logs
if [ -d "logs" ]; then
    echo "Backing up logs..."
    tar -czf "$BACKUP_PATH/logs.tar.gz" logs/ 2>/dev/null
    echo "✓ Logs backed up"
fi

# Calculate backup size
BACKUP_SIZE=$(du -sh "$BACKUP_PATH" | cut -f1)

# Clean old backups
echo "Cleaning old backups (older than $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -name "backup_*" -type d -mtime +$RETENTION_DAYS -exec rm -rf {} \; 2>/dev/null || true

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}Backup completed successfully!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo "Backup location: $BACKUP_PATH"
echo "Backup size: $BACKUP_SIZE"
echo "Timestamp: $TIMESTAMP"
echo ""
echo "To restore this backup, use:"
echo "./scripts/rollback.sh $TIMESTAMP"
