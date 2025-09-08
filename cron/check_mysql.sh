#!/bin/bash
# Auto-start XAMPP services for cron jobs

# Check if XAMPP is running, start if needed
if ! pgrep -x "httpd\|mysqld" > /dev/null; then
    sudo /opt/lampp/lampp start
    sleep 5
fi