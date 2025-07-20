# DreamHost SFTP Configuration Guide

## Problem
You're trying to connect to DreamHost using FTP, but it's failing. This is because DreamHost uses **SFTP** (SSH File Transfer Protocol) instead of traditional FTP.

## Solution
The Bulk Plugin Deployer now supports both FTP and SFTP. For DreamHost, you need to use SFTP.

## Configuration Steps

### 1. Install SSH2 Extension (if not already installed)

**For Docker containers:**
```dockerfile
# Add to your Dockerfile
RUN apt-get update && apt-get install -y libssh2-1-dev
RUN docker-php-ext-install ssh2
```

**For Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install php-ssh2
sudo systemctl restart apache2  # or nginx
```

**For CentOS/RHEL:**
```bash
sudo yum install php-ssh2
sudo systemctl restart httpd  # or nginx
```

### 2. Configure Site in Plugin

When adding a new site in the Bulk Plugin Deployer:

1. **Site Name**: Your site name (e.g., "My DreamHost Site")
2. **Site URL**: Your website URL
3. **FTP/SFTP Host**: `iad1-shared-b7-03.dreamhost.com` (or your specific server)
4. **FTP/SFTP Port**: `22` (SFTP port)
5. **FTP/SFTP Username**: `dh_dsiw53` (your DreamHost username)
6. **FTP/SFTP Password**: Your DreamHost password
7. **FTP Path**: `/wp-content/plugins/` (or your specific path)

### 3. Test the Connection

1. Click "Test Connection" to verify your settings
2. If successful, you'll see "SFTP connection successful"
3. If it fails, check the error message for troubleshooting

## DreamHost Server Information

### Finding Your Server
Your DreamHost server hostname can be found in your DreamHost panel:
1. Log into your DreamHost panel
2. Go to "Users & Files" → "FTP Users & Files"
3. Look for your server hostname (e.g., `iad1-shared-b7-03.dreamhost.com`)

### Common DreamHost Servers
- `iad1-shared-b7-03.dreamhost.com` (IAD1 - Virginia)
- `iad2-shared-b7-03.dreamhost.com` (IAD2 - Virginia)
- `iad3-shared-b7-03.dreamhost.com` (IAD3 - Virginia)
- `lax1-shared-b7-03.dreamhost.com` (LAX1 - Los Angeles)
- `lax2-shared-b7-03.dreamhost.com` (LAX2 - Los Angeles)

## Testing SFTP Connection

### Using the Test Script
Run the included test script to verify SFTP connectivity:

```bash
# From your Docker container
docker exec -it bb-wp bash
cd /var/www/html/wp-content/plugins/bulk-plugin-deployer
php test-sftp-connection.php
```

### Manual Testing
You can also test manually:

```bash
# Test from Docker container
docker exec -it bb-wp bash -c "sftp -vvv dh_dsiw53@iad1-shared-b7-03.dreamhost.com"
```

## Troubleshooting

### Common Issues

1. **SSH2 Extension Not Available**
   ```
   Error: SSH2 extension is not available
   ```
   **Solution**: Install php-ssh2 extension

2. **Connection Failed**
   ```
   Error: Could not connect to SFTP server
   ```
   **Solutions**:
   - Check if port 22 is open in your firewall
   - Verify the server hostname is correct
   - Try using host networking in Docker

3. **Authentication Failed**
   ```
   Error: SFTP authentication failed
   ```
   **Solutions**:
   - Verify username and password
   - Check if your DreamHost account is active
   - Ensure you're using the correct username format

4. **Cannot Access Directory**
   ```
   Error: Cannot access plugins directory via SFTP
   ```
   **Solutions**:
   - Verify the path is correct
   - Check file permissions on the server
   - Ensure the directory exists

### Docker-Specific Issues

If you're running in Docker and having connectivity issues:

1. **Use Host Networking**:
   ```yaml
   # docker-compose.yml
   version: '3'
   services:
     wordpress:
       image: your-wordpress-image
       network_mode: host
   ```

2. **Test Network Connectivity**:
   ```bash
   # Test DNS resolution
   docker exec bb-wp nslookup iad1-shared-b7-03.dreamhost.com
   
   # Test port connectivity
   docker exec bb-wp telnet iad1-shared-b7-03.dreamhost.com 22
   ```

3. **Check Docker Network**:
   ```bash
   # View container network info
   docker inspect bb-wp | grep -A 20 "NetworkSettings"
   ```

## Security Considerations

1. **Password Storage**: Passwords are encrypted using WordPress authentication salts
2. **Connection Security**: SFTP uses SSH encryption for all data transfer
3. **Access Control**: Only users with `manage_options` capability can use the plugin

## Performance Tips

1. **Connection Pooling**: The plugin creates new connections for each operation
2. **File Transfer**: Large plugins are zipped before transfer for efficiency
3. **Error Handling**: Comprehensive error reporting for troubleshooting

## Extra Pro Debugging Tips

1. **Enable WordPress Debug Logging**:
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Monitor SFTP Traffic**:
   ```bash
   # Monitor SSH traffic from container
   docker exec bb-wp tcpdump -i any port 22
   ```

3. **Test SSH Connection Directly**:
   ```bash
   # Test SSH connection
   docker exec bb-wp ssh -o ConnectTimeout=10 dh_dsiw53@iad1-shared-b7-03.dreamhost.com
   ```

4. **Check PHP Extensions**:
   ```bash
   # List installed PHP extensions
   docker exec bb-wp php -m | grep -i ssh
   ```

## Related Topics to Learn

1. **SSH/SFTP Protocol**: Understanding how SSH file transfer works
2. **Docker Networking**: Managing network connectivity in containers
3. **PHP Extensions**: Installing and configuring PHP extensions
4. **DreamHost Administration**: Managing DreamHost hosting accounts
5. **WordPress Plugin Development**: Understanding WordPress plugin architecture

## Future Enhancements

1. **SSH Key Authentication**: Support for SSH key-based authentication
2. **Connection Pooling**: Reuse SSH connections for better performance
3. **Compression**: Enable compression for faster file transfers
4. **Resume Support**: Resume interrupted file transfers
5. **Batch Operations**: Optimize multiple file transfers 