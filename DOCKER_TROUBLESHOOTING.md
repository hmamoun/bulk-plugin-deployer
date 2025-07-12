# Docker FTP Connectivity Troubleshooting Guide

## Problem
Your Docker container cannot connect to external FTP servers, even though FTP works from your host machine.

## Common Causes

### 1. Docker Network Isolation
Docker containers are isolated from the host network by default.

**Solution:**
```bash
# Run container with host networking
docker run --network host your-wordpress-image

# Or in docker-compose.yml
version: '3'
services:
  wordpress:
    image: your-wordpress-image
    network_mode: host
```

### 2. Firewall Rules
Docker daemon or host firewall may block outbound connections.

**Solution:**
```bash
# Check if container can reach external hosts
docker exec your-container ping google.com

# Test FTP from container
docker exec your-container ftp ftp.gnu.org
```

### 3. DNS Resolution Issues
Container may not be able to resolve hostnames.

**Solution:**
```bash
# Add custom DNS to docker-compose.yml
version: '3'
services:
  wordpress:
    image: your-wordpress-image
    dns:
      - 8.8.8.8
      - 1.1.1.1
```

### 4. Port Blocking
FTP uses multiple ports (20, 21, and dynamic ports for data transfer).

**Solution:**
```yaml
# docker-compose.yml
version: '3'
services:
  wordpress:
    image: your-wordpress-image
    ports:
      - "21:21"
      - "20:20"
      - "1024-65535:1024-65535"  # FTP data ports
```

## Testing Steps

### 1. Run Network Test Script
```bash
# Copy the test script to your container
docker cp docker-network-test.php your-container:/var/www/html/wp-content/plugins/bulk-plugin-deployer/

# Run the test
docker exec your-container php /var/www/html/wp-content/plugins/bulk-plugin-deployer/docker-network-test.php
```

### 2. Test Basic Connectivity
```bash
# Test DNS resolution
docker exec your-container nslookup your-ftp-server.com

# Test port connectivity
docker exec your-container telnet your-ftp-server.com 21

# Test FTP directly
docker exec your-container ftp your-ftp-server.com
```

### 3. Check Container Network
```bash
# View container network info
docker inspect your-container | grep -A 20 "NetworkSettings"

# Check if container can reach host
docker exec your-container ping host.docker.internal
```

## Alternative Solutions

### 1. Use SFTP Instead of FTP
SFTP uses a single SSH connection and is often more reliable in Docker.

**Update your site configuration:**
- Change port from 21 to 22
- Use SFTP credentials instead of FTP
- The plugin will automatically detect and use SFTP if available

### 2. Use Host Network Mode
```yaml
# docker-compose.yml
version: '3'
services:
  wordpress:
    image: your-wordpress-image
    network_mode: host
    volumes:
      - ./wp-content:/var/www/html/wp-content
```

### 3. Use Docker Host IP
If your FTP server is on the same network as the Docker host:

```bash
# Get Docker host IP
docker run --rm alpine ip route show default | awk '/default/ {print $3}'

# Use this IP in your FTP configuration
```

### 4. Use SSH Tunneling
```bash
# Create SSH tunnel from host to container
ssh -L 2121:localhost:21 user@your-ftp-server.com

# Configure plugin to use localhost:2121
```

## Docker Compose Example

```yaml
version: '3.8'
services:
  wordpress:
    image: wordpress:latest
    network_mode: host  # Use host networking
    environment:
      WORDPRESS_DB_HOST: localhost
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
    volumes:
      - ./wp-content:/var/www/html/wp-content
      - ./uploads.ini:/usr/local/etc/php/conf.d/uploads.ini
    ports:
      - "80:80"
    depends_on:
      - mysql
    restart: unless-stopped

  mysql:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: somewordpress
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
    volumes:
      - db_data:/var/lib/mysql
    restart: unless-stopped

volumes:
  db_data:
```

## PHP Configuration

Add this to your `uploads.ini` file:

```ini
; Increase memory and execution time for large uploads
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
post_max_size = 100M
upload_max_filesize = 100M

; Enable FTP extension
extension=ftp.so
```

## Testing Commands

```bash
# Test from host machine
ftp your-ftp-server.com

# Test from container
docker exec your-container ftp your-ftp-server.com

# Test PHP FTP functions
docker exec your-container php -r "
if (function_exists('ftp_connect')) {
    echo 'FTP extension available\n';
    \$conn = ftp_connect('ftp.gnu.org', 21, 5);
    if (\$conn) {
        echo 'FTP connection successful\n';
        ftp_close(\$conn);
    } else {
        echo 'FTP connection failed\n';
    }
} else {
    echo 'FTP extension not available\n';
}
"
```

## Common Error Messages

- **"Could not connect to FTP server"**: Network connectivity issue
- **"FTP extension is not available"**: PHP FTP extension not installed
- **"Connection timed out"**: Firewall or DNS issue
- **"Permission denied"**: Authentication or file permission issue

## Extra Pro Debugging Tip

For advanced Docker networking debugging, use `tcpdump` to monitor network traffic:

```bash
# Monitor FTP traffic from container
docker exec your-container tcpdump -i any port 21

# Monitor all outbound connections
docker exec your-container tcpdump -i any -n
```

This will help identify if the issue is network-related or application-related. 