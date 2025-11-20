

echo "Setting up IdeaNest cron jobs..."

# Get the current directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Create cron job entries
CRON_FILE="/tmp/ideanest_cron"

cat > "$CRON_FILE" << EOF
# IdeaNest Automated Tasks

# Send session reminders every hour
0 * * * * /usr/bin/php $PROJECT_DIR/mentor/session_reminder_system.php >> $PROJECT_DIR/logs/cron.log 2>&1

# Clean up old sessions daily at 2 AM
0 2 * * * /usr/bin/php $PROJECT_DIR/cron/cleanup_old_sessions.php >> $PROJECT_DIR/logs/cron.log 2>&1

# Generate daily reports at 1 AM
0 1 * * * /usr/bin/php $PROJECT_DIR/cron/generate_daily_reports.php >> $PROJECT_DIR/logs/cron.log 2>&1

# Backup database daily at 3 AM
0 3 * * * /usr/bin/php $PROJECT_DIR/cron/backup_database.php >> $PROJECT_DIR/logs/cron.log 2>&1

EOF

# Install cron jobs
crontab -l > /tmp/current_cron 2>/dev/null || true
cat /tmp/current_cron "$CRON_FILE" | crontab -

echo "Cron jobs installed successfully!"
echo ""
echo "Installed jobs:"
crontab -l | grep "IdeaNest" -A 10

# Create logs directory if it doesn't exist
mkdir -p "$PROJECT_DIR/logs"
chmod 755 "$PROJECT_DIR/logs"

echo ""
echo "Setup complete! Cron jobs are now active."
echo "Logs will be written to: $PROJECT_DIR/logs/cron.log"
