#!/bin/bash
# Start XAMPP services
echo "Starting XAMPP services..."

# Start XAMPP (requires root)
sudo /opt/lampp/lampp start

# Wait for services to start
sleep 5

# Check status
sudo /opt/lampp/lampp status

echo "XAMPP started! Access your site at: http://localhost/IdeaNest"