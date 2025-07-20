<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Bulk Plugin Deployer', 'bulk-plugin-deployer'); ?></h1>
    
    <div class="bpd-container">
        <!-- Sites Management Section -->
        <div class="bpd-section">
            <h2><?php _e('Manage Target Sites', 'bulk-plugin-deployer'); ?></h2>
            
            <!-- Add/Edit Site Form -->
            <div class="bpd-form-container">
                <h3><?php _e('Add New Site', 'bulk-plugin-deployer'); ?></h3>
                <form id="bpd-site-form" class="bpd-form">
                    <div class="bpd-form-row">
                        <div class="bpd-form-group">
                            <label for="site_name"><?php _e('Site Name', 'bulk-plugin-deployer'); ?> *</label>
                            <input type="text" id="site_name" name="name" required>
                        </div>
                        <div class="bpd-form-group">
                            <label for="site_url"><?php _e('Site URL', 'bulk-plugin-deployer'); ?> *</label>
                            <input type="url" id="site_url" name="url" required>
                        </div>
                    </div>
                    
                    <div class="bpd-form-row">
                        <div class="bpd-form-group">
                            <label for="ftp_host"><?php _e('FTP/SFTP Host', 'bulk-plugin-deployer'); ?> *</label>
                            <input type="text" id="ftp_host" name="ftp_host" required>
                        </div>
                        <div class="bpd-form-group">
                            <label for="ftp_port"><?php _e('FTP/SFTP Port', 'bulk-plugin-deployer'); ?></label>
                            <input type="number" id="ftp_port" name="ftp_port" value="21" min="1" max="65535">
                            <small class="description"><?php _e('Use port 21 for FTP, port 22 for SFTP', 'bulk-plugin-deployer'); ?></small>
                        </div>
                    </div>
                    
                    <div class="bpd-form-row">
                        <div class="bpd-form-group">
                            <label for="ftp_username"><?php _e('FTP/SFTP Username', 'bulk-plugin-deployer'); ?> *</label>
                            <input type="text" id="ftp_username" name="ftp_username" required>
                        </div>
                        <div class="bpd-form-group">
                            <label for="ftp_password"><?php _e('FTP/SFTP Password', 'bulk-plugin-deployer'); ?> *</label>
                            <input type="password" id="ftp_password" name="ftp_password" required>
                            <small class="description"><?php _e('Leave blank when editing to keep existing password', 'bulk-plugin-deployer'); ?></small>
                        </div>
                    </div>
                    
                    <div class="bpd-form-row">
                        <div class="bpd-form-group">
                            <label for="ftp_path"><?php _e('FTP Path to Plugins', 'bulk-plugin-deployer'); ?></label>
                            <input type="text" id="ftp_path" name="ftp_path" value="/wp-content/plugins/">
                        </div>
                    </div>
                    
                    <div class="bpd-form-actions">
                        <button type="button" id="bpd-test-connection" class="button button-secondary">
                            <?php _e('Test Connection', 'bulk-plugin-deployer'); ?>
                        </button>
                        <button type="submit" class="button button-primary">
                            <?php _e('Save Site', 'bulk-plugin-deployer'); ?>
                        </button>
                        <button type="button" id="bpd-clear-form" class="button">
                            <?php _e('Clear Form', 'bulk-plugin-deployer'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Sites List -->
            <div class="bpd-sites-list">
                <h3><?php _e('Saved Sites', 'bulk-plugin-deployer'); ?></h3>
                <div id="bpd-sites-container">
                    <?php if (!empty($sites)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Site Name', 'bulk-plugin-deployer'); ?></th>
                                    <th><?php _e('URL', 'bulk-plugin-deployer'); ?></th>
                                    <th><?php _e('FTP Host', 'bulk-plugin-deployer'); ?></th>
                                    <th><?php _e('Actions', 'bulk-plugin-deployer'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sites as $site): ?>
                                    <tr data-site-id="<?php echo esc_attr($site['id']); ?>">
                                        <td><?php echo esc_html($site['name']); ?></td>
                                        <td><a href="<?php echo esc_url($site['url']); ?>" target="_blank"><?php echo esc_html($site['url']); ?></a></td>
                                        <td><?php echo esc_html($site['ftp_host']); ?></td>
                                        <td>
                                            <button class="button button-small bpd-edit-site" data-site='<?php echo json_encode($site); ?>'>
                                                <?php _e('Edit', 'bulk-plugin-deployer'); ?>
                                            </button>
                                            <button class="button button-small button-link-delete bpd-delete-site" data-site-id="<?php echo esc_attr($site['id']); ?>">
                                                <?php _e('Delete', 'bulk-plugin-deployer'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><?php _e('No sites added yet. Add your first site above.', 'bulk-plugin-deployer'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Plugin Deployment Section -->
        <div class="bpd-section">
            <h2><?php _e('Deploy Plugins', 'bulk-plugin-deployer'); ?></h2>
            
            <div class="bpd-deployment-container">
                <!-- Plugin Selection -->
                <div class="bpd-form-group">
                    <label><?php _e('Select Plugins to Deploy', 'bulk-plugin-deployer'); ?></label>
                    <div class="bpd-checkbox-group">
                        <?php if (!empty($plugins)): ?>
                            <?php foreach ($plugins as $plugin_slug => $plugin_data): ?>
                                <label class="bpd-checkbox-item">
                                    <input type="checkbox" name="plugins[]" value="<?php echo esc_attr($plugin_slug); ?>">
                                    <span class="bpd-checkbox-label">
                                        <strong><?php echo esc_html($plugin_data['name']); ?></strong>
                                        <small><?php echo esc_html($plugin_data['description']); ?></small>
                                        <span class="bpd-version">v<?php echo esc_html($plugin_data['version']); ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php _e('No plugins found in wp-content/plugins directory.', 'bulk-plugin-deployer'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Site Selection -->
                <div class="bpd-form-group">
                    <label><?php _e('Select Target Sites', 'bulk-plugin-deployer'); ?></label>
                    <div class="bpd-checkbox-group">
                        <?php if (!empty($sites)): ?>
                            <?php foreach ($sites as $site): ?>
                                <label class="bpd-checkbox-item">
                                    <input type="checkbox" name="sites[]" value="<?php echo esc_attr($site['id']); ?>">
                                    <span class="bpd-checkbox-label">
                                        <strong><?php echo esc_html($site['name']); ?></strong>
                                        <small><?php echo esc_html($site['url']); ?></small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php _e('No sites added yet. Please add sites above first.', 'bulk-plugin-deployer'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Deployment Actions -->
                <div class="bpd-form-actions">
                    <button type="button" id="bpd-deploy-plugins" class="button button-primary button-large" disabled>
                        <?php _e('Deploy Selected Plugins', 'bulk-plugin-deployer'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Deployment Results -->
        <div class="bpd-section" id="bpd-results" style="display: none;">
            <h2><?php _e('Deployment Results', 'bulk-plugin-deployer'); ?></h2>
            <div id="bpd-results-content"></div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="bpd-loading-overlay" style="display: none;">
    <div class="bpd-loading-content">
        <div class="bpd-spinner"></div>
        <p id="bpd-loading-message"><?php _e('Processing...', 'bulk-plugin-deployer'); ?></p>
    </div>
</div> 