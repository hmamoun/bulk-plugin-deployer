jQuery(document).ready(function($) {
    
    // Global variables
    let currentSiteId = null;
    
    // Initialize the plugin
    initBulkPluginDeployer();
    
    function initBulkPluginDeployer() {
        bindEvents();
        updateDeployButton();
    }
    
    function bindEvents() {
        // Site form submission
        $('#bpd-site-form').on('submit', handleSiteFormSubmit);
        
        // Test connection button
        $('#bpd-test-connection').on('click', handleTestConnection);
        
        // Clear form button
        $('#bpd-clear-form').on('click', clearSiteForm);
        
        // Generate standard path button
        $('#bpd-generate-path').on('click', handleGeneratePath);
        
        // Edit site button
        $(document).on('click', '.bpd-edit-site', handleEditSite);
        
        // Delete site button
        $(document).on('click', '.bpd-delete-site', handleDeleteSite);
        
        // Deploy plugins button
        $('#bpd-deploy-plugins').on('click', handleDeployPlugins);
        
        // Checkbox change events
        $(document).on('change', 'input[name="plugins[]"], input[name="sites[]"]', updateDeployButton);
    }
    
    function handleSiteFormSubmit(e) {

        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'bpd_save_site');
        formData.append('nonce', bpd_ajax.nonce);
        
        if (currentSiteId) {
            formData.append('id', currentSiteId);
        }
        
        showLoading(bpd_ajax.strings.saving || 'Saving site...');
        
        $.ajax({
            url: bpd_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showNotice('Site saved successfully!', 'success');
                    clearSiteForm();
                    location.reload(); // Refresh to show updated sites list
                } else {
                    showNotice(response.data || 'Failed to save site', 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotice('An error occurred while saving the site', 'error');
            }
        });
    }
    
    function handleTestConnection() {
        const formData = new FormData($('#bpd-site-form')[0]);
        formData.append('action', 'bpd_test_connection');
        formData.append('nonce', bpd_ajax.nonce);
        
        // Add site ID if editing an existing site
        if (currentSiteId) {
            formData.append('id', currentSiteId);
        }
        
        showLoading(bpd_ajax.strings.testing_connection || 'Testing connection...');
        
        $.ajax({
            url: bpd_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showNotice(bpd_ajax.strings.connection_success || 'Connection successful!', 'success');
                } else {
                    showNotice(response.data || bpd_ajax.strings.connection_failed || 'Connection failed', 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotice('An error occurred while testing the connection', 'error');
            }
        });
    }
    
    function handleEditSite() {
        const siteData = $(this).data('site');
        if (!siteData) return;
        
        // Populate form with site data
        $('#site_name').val(siteData.name);
        $('#site_url').val(siteData.url);
        $('#ftp_host').val(siteData.ftp_host);
        $('#ftp_port').val(siteData.ftp_port);
        $('#ftp_username').val(siteData.ftp_username);
        $('#ftp_password').val(''); // Don't populate password for security
        $('#ftp_path').val(siteData.ftp_path);
        
        currentSiteId = siteData.id;
        
        // Update form title and button
        $('.bpd-form-container h3').text('Edit Site');
        $('.bpd-form-actions button[type="submit"]').text('Update Site');
        
        // Remove required attribute from password field when editing
        $('#ftp_password').removeAttr('required');
        
        // Update password field label to indicate it's optional
        $('#ftp_password').closest('.bpd-form-group').find('label').html('FTP Password <small>(leave blank to keep existing)</small>');
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('.bpd-form-container').offset().top - 50
        }, 500);
    }
    
    function handleDeleteSite() {
        const siteId = $(this).data('site-id');
        const siteName = $(this).closest('tr').find('td:first').text();
        
        if (!confirm(bpd_ajax.strings.confirm_delete || 'Are you sure you want to delete this site?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'bpd_delete_site');
        formData.append('nonce', bpd_ajax.nonce);
        formData.append('id', siteId);
        
        showLoading('Deleting site...');
        
        $.ajax({
            url: bpd_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showNotice('Site deleted successfully!', 'success');
                    location.reload(); // Refresh to update sites list
                } else {
                    showNotice(response.data || 'Failed to delete site', 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotice('An error occurred while deleting the site', 'error');
            }
        });
    }
    
    function handleDeployPlugins() {
        const selectedPlugins = $('input[name="plugins[]"]:checked').map(function() {
            return this.value;
        }).get();
        
        const selectedSites = $('input[name="sites[]"]:checked').map(function() {
            return this.value;
        }).get();
        

        
        if (selectedPlugins.length === 0) {
            showNotice('Please select at least one plugin to deploy', 'error');
            return;
        }
        
        if (selectedSites.length === 0) {
            showNotice('Please select at least one site to deploy to', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'bpd_deploy_plugins');
        formData.append('nonce', bpd_ajax.nonce);
        
        // Ensure we have valid data before sending
        if (selectedPlugins.length > 0) {
            formData.append('plugins', JSON.stringify(selectedPlugins));
        } else {
            formData.append('plugins', JSON.stringify([]));
        }
        
        if (selectedSites.length > 0) {
            formData.append('sites', JSON.stringify(selectedSites));
        } else {
            formData.append('sites', JSON.stringify([]));
        }
        
        showLoading(bpd_ajax.strings.deploying || 'Deploying plugins...');
        
        $.ajax({
            url: bpd_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showDeploymentResults(response.data);
                    showNotice(bpd_ajax.strings.success || 'Deployment completed successfully!', 'success');
                } else {
                    showDeploymentResults(response.data);
                    showNotice(bpd_ajax.strings.error || 'An error occurred during deployment', 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotice('An error occurred during deployment', 'error');
            }
        });
    }
    
    function showDeploymentResults(data) {
        const resultsContainer = $('#bpd-results-content');
        let html = '';
        
        // Show summary
        if (data.summary) {
            html += '<div class="bpd-result-item bpd-result-summary">';
            html += `<strong>Deployment Summary:</strong> ${data.summary.success} successful, ${data.summary.failure} failed out of ${data.summary.total} total deployments`;
            html += '</div>';
        }
        
        // Show individual results
        if (data.results && data.results.length > 0) {
            data.results.forEach(function(result) {
                const cssClass = result.success ? 'bpd-result-success' : 'bpd-result-error';
                html += '<div class="bpd-result-item ' + cssClass + '">';
                html += '<strong>' + (result.site_name || 'Site ' + result.site_id) + '</strong>';
                if (result.plugin_name) {
                    html += ' - <strong>' + result.plugin_name + '</strong>';
                }
                html += '<br><small>' + result.message + '</small>';
                html += '</div>';
            });
        }
        
        resultsContainer.html(html);
        $('#bpd-results').show();
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#bpd-results').offset().top - 50
        }, 500);
    }
    
    function clearSiteForm() {
        $('#bpd-site-form')[0].reset();
        currentSiteId = null;
        $('.bpd-form-container h3').text('Add New Site');
        $('.bpd-form-actions button[type="submit"]').text('Save Site');
        
        // Restore required attribute to password field for new sites
        $('#ftp_password').attr('required', 'required');
        
        // Restore original password field label
        $('#ftp_password').closest('.bpd-form-group').find('label').html('FTP Password *');
    }
    
    function handleGeneratePath(e) {
        e.preventDefault();
        
        const username = $('#ftp_username').val();
        const siteUrl = $('#site_url').val();
        
        if (!username) {
            showNotice('Please enter the FTP username first', 'error');
            $('#ftp_username').focus();
            return;
        }
        
        if (!siteUrl) {
            showNotice('Please enter the site URL first', 'error');
            $('#site_url').focus();
            return;
        }
        
        // Extract domain from URL (remove protocol and www)
        let domain = siteUrl.replace(/^https?:\/\//, '').replace(/^www\./, '');
        
        // Remove trailing slash if present
        domain = domain.replace(/\/$/, '');
        
        // Generate the standard path
        const standardPath = `/home/${username}/${domain}/wp-content/plugins/`;
        
        // Set the path in the input field
        $('#ftp_path').val(standardPath);
        
        showNotice('Standard path generated successfully!', 'success');
    }
    
    function updateDeployButton() {
        const hasPlugins = $('input[name="plugins[]"]:checked').length > 0;
        const hasSites = $('input[name="sites[]"]:checked').length > 0;
        
        $('#bpd-deploy-plugins').prop('disabled', !(hasPlugins && hasSites));
    }
    
    function showLoading(message) {
        $('#bpd-loading-message').text(message);
        $('#bpd-loading-overlay').show();
    }
    
    function hideLoading() {
        $('#bpd-loading-overlay').hide();
    }
    
    function showNotice(message, type) {
        // Remove existing notices
        $('.bpd-notice').remove();
        
        // Create new notice
        const notice = $('<div class="bpd-notice bpd-notice-' + type + '">' + message + '</div>');
        
        // Insert at the top of the container
        $('.bpd-container').prepend(notice);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Utility function to validate form
    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.find('[required]');
        
        requiredFields.each(function() {
            if (!$(this).val()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        return isValid;
    }
    
    // Add form validation
    $('#bpd-site-form').on('submit', function(e) {
        if (!validateForm($(this))) {
            e.preventDefault();
            showNotice('Please fill in all required fields', 'error');
        }
    });
    
    // Real-time form validation
    $('#bpd-site-form input[required]').on('blur', function() {
        if (!$(this).val()) {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });
    
    // Select all/none functionality for checkboxes
    function addSelectAllFunctionality() {
        // Add select all buttons if needed
        if ($('.bpd-checkbox-group').length > 0) {
            $('.bpd-checkbox-group').each(function() {
                const container = $(this);
                const checkboxes = container.find('input[type="checkbox"]');
                
                if (checkboxes.length > 3) {
                    const selectAllBtn = $('<button type="button" class="button button-small">Select All</button>');
                    const selectNoneBtn = $('<button type="button" class="button button-small">Select None</button>');
                    
                    container.before(selectAllBtn).before(selectNoneBtn);
                    
                    selectAllBtn.on('click', function() {
                        checkboxes.prop('checked', true);
                        updateDeployButton();
                    });
                    
                    selectNoneBtn.on('click', function() {
                        checkboxes.prop('checked', false);
                        updateDeployButton();
                    });
                }
            });
        }
    }
    
    // Initialize select all functionality
    addSelectAllFunctionality();
}); 