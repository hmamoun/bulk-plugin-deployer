# Bulk Plugin Deployer

A WordPress plugin that allows you to deploy WordPress plugins to multiple sites via FTP. Perfect for developers and agencies managing multiple WordPress installations.

## Features

- **Bulk Plugin Deployment**: Deploy multiple plugins to multiple sites simultaneously
- **Site Management**: Add, edit, and delete target sites with FTP credentials
- **FTP Connection Testing**: Test FTP connections before deployment
- **Secure Password Storage**: FTP passwords are encrypted using WordPress salts
- **Modern Admin Interface**: Clean, responsive admin interface with real-time feedback
- **Deployment Logging**: Detailed results for each deployment attempt
- **Plugin Discovery**: Automatically discovers available plugins in wp-content/plugins

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- PHP FTP extension (php-ftp)
- FTP access to target sites
- ZipArchive PHP extension (for creating plugin archives)

## Installation

1. Upload the `bulk-plugin-deployer` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Plugin Deployer' in the admin menu

## Usage

### Adding Target Sites

1. Go to **Plugin Deployer** in your WordPress admin
2. In the "Manage Target Sites" section, fill out the site form:
   - **Site Name**: A friendly name for the site
   - **Site URL**: The website URL
   - **FTP Host**: FTP server hostname or IP
   - **FTP Port**: FTP port (default: 21)
   - **FTP Username**: FTP username
   - **FTP Password**: FTP password
   - **FTP Path**: Path to plugins directory (default: /wp-content/plugins/)
3. Click "Test Connection" to verify FTP credentials
4. Click "Save Site" to add the site to your list

### Deploying Plugins

1. In the "Deploy Plugins" section, select the plugins you want to deploy
2. Select the target sites where you want to deploy the plugins
3. Click "Deploy Selected Plugins"
4. Monitor the deployment progress and review results

## Security Features

- **Password Encryption**: FTP passwords are encrypted using WordPress authentication salts
- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **Capability Checks**: Only users with `manage_options` capability can use the plugin
- **Input Sanitization**: All user inputs are properly sanitized

## File Structure

```
bulk-plugin-deployer/
├── bulk-plugin-deployer.php          # Main plugin file
├── includes/
│   ├── class-bulk-plugin-deployer.php # Main plugin class
│   ├── class-bpd-admin.php           # Admin interface
│   ├── class-bpd-deployer.php        # Deployment logic
│   ├── class-bpd-site-manager.php    # Site management
│   └── class-bpd-installer.php       # Installation/uninstallation
├── templates/
│   └── admin-page.php                # Admin page template
├── assets/
│   ├── css/
│   │   └── admin.css                 # Admin styles
│   └── js/
│       └── admin.js                  # Admin JavaScript
└── README.md                         # This file
```

## Database Tables

The plugin creates one database table:

- `wp_bpd_sites`: Stores site information and FTP credentials

## Configuration

The plugin uses WordPress options for configuration:

- `bpd_settings`: Plugin settings (execution time, memory limit, logging)
- `bpd_db_version`: Database version tracking

## Troubleshooting

### Common Issues

1. **FTP Connection Failed**
   - Verify FTP host, port, username, and password
   - Ensure FTP server allows passive mode connections
   - Check if firewall is blocking FTP connections

2. **Plugin Deployment Failed**
   - Verify the plugins directory path is correct
   - Ensure FTP user has write permissions to plugins directory
   - Check server has enough disk space

3. **Memory/Timeout Issues**
   - Increase PHP memory limit in wp-config.php
   - Increase max_execution_time in php.ini
   - Consider deploying fewer plugins at once

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### FTP Extension Issues

If you see "FTP extension is not available" errors:

1. **Shared Hosting**: Contact your hosting provider to enable the PHP FTP extension
2. **VPS/Dedicated Server**: Install the extension:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-ftp
   
   # CentOS/RHEL
   sudo yum install php-ftp
   
   # Restart web server after installation
   sudo systemctl restart apache2  # or nginx
   ```
3. **Docker**: Add the extension to your Dockerfile:
   ```dockerfile
   RUN docker-php-ext-install ftp
   ```

## Development

### Hooks and Filters

The plugin provides several hooks for customization:

```php
// Modify deployment results
add_filter('bpd_deployment_result', 'my_custom_result_handler', 10, 2);

// Add custom validation
add_filter('bpd_validate_site_data', 'my_custom_validation', 10, 1);

// Log deployment events
add_action('bpd_plugin_deployed', 'my_custom_logger', 10, 3);
```

### Extending the Plugin

To add custom functionality:

1. Create a custom plugin that depends on Bulk Plugin Deployer
2. Use WordPress hooks to modify behavior
3. Extend existing classes if needed

## Changelog

### Version 1.0.0
- Initial release
- Basic site management
- Plugin deployment via FTP
- Admin interface
- Security features

## Support

For support and feature requests, please create an issue on the plugin's GitHub repository.

## License

This plugin is licensed under the GPL v2 or later.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Extra Pro Debugging Tip

When troubleshooting deployment issues, enable WordPress debug logging and check the debug.log file for detailed error messages. You can also add custom logging to the deployment process by hooking into the `bpd_plugin_deployed` action to track successful deployments and identify patterns in failures.

For advanced debugging, consider implementing a custom deployment logger that captures FTP commands and responses, which can help identify server-specific issues or configuration problems. 