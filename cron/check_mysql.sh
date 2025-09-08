#!/bin/bash
# Check if MySQL is running and start if needed
if ! pgrep -x "mysqld" > /dev/null; then
    echo "MySQL not running, starting XAMPP MySQL..."
    /opt/lampp/lampp startmysql
    sleep 5
fi