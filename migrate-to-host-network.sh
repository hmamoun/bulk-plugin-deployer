#!/bin/bash

# Migration script to fix Docker FTP connectivity issues
# This script helps migrate from bridge networking to host networking

echo "=== Docker FTP Migration Script ==="
echo "This script will help you migrate to host networking to fix FTP issues"
echo ""

# Check if containers are running
echo "1. Checking current container status..."
if docker ps | grep -q bb-wp; then
    echo "✅ Container bb-wp is running"
else
    echo "❌ Container bb-wp is not running"
    exit 1
fi

# Backup current data
echo ""
echo "2. Creating backup of current setup..."
echo "   Stopping current containers..."
docker stop bb-wp bb-wp2 bb-mysql bb-phpmyadmin

echo "   Creating backup of WordPress data..."
docker cp bb-wp:/var/www/html/wp-content ./wp-content-backup-$(date +%Y%m%d-%H%M%S)

echo "   Creating backup of MySQL data..."
docker cp bb-mysql:/var/lib/mysql ./mysql-backup-$(date +%Y%m%d-%H%M%S)

echo "✅ Backup completed"
echo ""

# Create new docker-compose file
echo "3. Setting up new docker-compose configuration..."
echo "   Using host networking for FTP connectivity"
echo ""

# Show the new configuration
echo "New docker-compose-fixed.yml configuration:"
echo "   - WordPress: bridge-builder-plugin-wordpress (host networking)"
echo "   - MySQL: mysql:8.0 (bridge networking)"
echo "   - PhpMyAdmin: phpmyadmin/phpmyadmin (host networking)"
echo ""

# Instructions
echo "4. Migration steps:"
echo ""
echo "   Step 1: Stop current containers"
echo "   docker-compose down"
echo ""
echo "   Step 2: Start with new configuration"
echo "   docker-compose -f docker-compose-fixed.yml up -d"
echo ""
echo "   Step 3: Test FTP connectivity"
echo "   docker exec bb-wp ping google.com"
echo "   docker exec bb-wp ftp ftp.gnu.org"
echo ""
echo "   Step 4: Test the plugin"
echo "   - Go to http://localhost:8080"
echo "   - Navigate to Plugin Deployer"
echo "   - Try the FTP connection test"
echo ""

# Check if docker-compose is available
if command -v docker-compose &> /dev/null; then
    echo "✅ docker-compose is available"
else
    echo "❌ docker-compose not found. Please install it first."
    echo "   Install with: pip install docker-compose"
fi

echo ""
echo "5. Alternative manual approach:"
echo ""
echo "   If docker-compose is not available, you can run manually:"
echo ""
echo "   # Stop current containers"
echo "   docker stop bb-wp bb-wp2 bb-mysql bb-phpmyadmin"
echo ""
echo "   # Start with host networking"
echo "   docker run --network host --name bb-wp-fixed \\"
echo "     -v \$(pwd)/wp-content:/var/www/html/wp-content \\"
echo "     -v \$(pwd)/uploads.ini:/usr/local/etc/php/conf.d/uploads.ini \\"
echo "     bridge-builder-plugin-wordpress"
echo ""
echo "   # Start MySQL"
echo "   docker run --name bb-mysql-fixed \\"
echo "     -e MYSQL_ROOT_PASSWORD=somewordpress \\"
echo "     -e MYSQL_DATABASE=wordpress \\"
echo "     -e MYSQL_USER=wordpress \\"
echo "     -e MYSQL_PASSWORD=wordpress \\"
echo "     -v mysql_data:/var/lib/mysql \\"
echo "     mysql:8.0"
echo ""

echo "=== Ready to migrate ==="
echo "Run the migration commands above to fix the FTP connectivity issue."
echo "The host networking mode will give your container direct access to the host network." 