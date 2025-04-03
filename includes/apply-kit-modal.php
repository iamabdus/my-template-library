<?php
if (!defined('WPINC')) {
    die;
}

// Enqueue required scripts
wp_enqueue_script('jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js', array(), '3.10.1', true);
?>

<!-- Import Kit Modal -->
<div id="apply-kit-modal" class="modal elementor-kit-import-wrapper">
    <div class="modal-header">
        <h2 class="modal-title">Template Kit Import</h2>
        <button class="close-modal">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
    <div class="import-content">
        <input type="hidden" id="selected-template-id" value="">

        <div class="template-info">
            <div class="form-field">
                <label for="kit-url-display">Template Kit URL:</label>
                <input 
                    type="text" 
                    id="kit-url-display" 
                    class="kit-url-input" 
                    value=""
                    disabled>
            </div>
        </div>
        
        <div class="demo-import-section">
            <h2>Import Selected Template Kit</h2>
            <p>You are about to import the selected template kit to your website.</p>
            <div class="import-buttons">
                <button id="required-plugins-checking-btn" class="button button-primary">Install & Activate Plugins</button>
            </div>
        </div>
    </div>
</div>

<?php require_once MTL_PLUGIN_DIR . 'includes/plugin-requirements-modal.php'; ?>

<style>
/* Apply Kit Modal Styles */
#apply-kit-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 100001;
    background: white;
    padding: 0;
    border-radius: 0px;
    width: 100%;
    height: 100%;
    overflow-y: auto;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

#apply-import-modal{
    z-index: 100004;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #f7f7f7;
    border-bottom: 1px solid #ddd;
}

.modal-title {
    margin: 0 !important;
    font-size: 18px;
    color: #333;
}

.close-modal {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
}

.close-modal .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #666;
}

.close-modal:hover .dashicons {
    color: #dc3232;
}

.apply-kit-content {
    max-width: 500px;
    margin: 0;
}

.elementor-kit-import-wrapper {
    margin: 0 auto;
    background: white;
}

.import-content {
    width: 600px;
    margin: 0 auto;
    text-align: center;
    padding: 20px;
}

.template-info {
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
}

.form-field {
    text-align: left;
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.kit-url-input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f5f5f5;
    color: #666;
}

.import-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
}


.section-divider {
    height: 1px;
    background: #ddd;
    margin: 20px 0;
}

/* Common Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
}

.modal-content {
    margin-bottom: 20px;
}

.modal-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.button {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    line-height: 2;
    min-height: 30px;
}

.button-primary {
    background-color: #2271b1;
    color: white;
    border: 1px solid #2271b1;
}

.button-secondary {
    background-color: transparent;
    border: 1px solid #2271b1;
    color: #2271b1;
}

/* Plugin Table Styles */
.plugins-table, .existing-plugins-table {
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
}

.table-header {
    display: grid;
    grid-template-columns: 40px 1fr 150px 150px;
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-bottom: 1px solid #ddd;
    font-weight: 600;
}

.existing-plugins-table .table-header {
    grid-template-columns: 40px 1fr 150px;
}

.plugin-item {
    display: grid;
    grid-template-columns: 40px 1fr 150px 150px;
    padding: 15px;
    border-bottom: 1px solid #ddd;
    align-items: center;
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Plugin Requirements Modal Styles */
#plugin-requirements-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 100001;
    background: white;
    padding: 0;
    border-radius: 0px;
    width: 100%;
    height: 100%;
    overflow-y: auto;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.modal-content {
    padding: 20px;
    overflow-y: auto;
    max-height: 100%; /* Account for header and footer */
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Elements
    const applyKitModal = document.getElementById('apply-kit-modal');
    const pluginRequirementsModal = document.getElementById('plugin-requirements-modal');
    const importDemoBtn = document.getElementById('required-plugins-checking-btn');
    const installActivateBtn = document.getElementById('install-activate-plugins-btn');
    const skipPluginsBtn = document.getElementById('skip-plugins-btn');
    const selectedTemplateId = document.getElementById('selected-template-id');
    const kitUrlDisplay = document.getElementById('kit-url-display');
    const pluginsList = document.getElementById('plugins-list');
    const existingPluginsList = document.getElementById('existing-plugins-list');
    const errorContainer = document.getElementById('installation-error-container');
    const closeModalBtn = pluginRequirementsModal.querySelector('.close-modal');
    const applyKitCloseBtn = applyKitModal.querySelector('.close-modal');

    // Flag to track if installation is in progress
    let isInstallationInProgress = false;

    // Create status elements
    const importingNotice = document.createElement('div');
    importingNotice.className = 'importing-notice';
    importingNotice.innerHTML = '<p>Importing template kit... Please do not close this window.</p>';
    
    const importStatus = document.createElement('div');
    importStatus.className = 'import-status';

    // Create debug log container
    const debugLog = document.createElement('div');
    debugLog.className = 'debug-log';
    debugLog.style.display = 'none';
    
    // Add elements to the template-info div
    const templateInfo = document.querySelector('.template-info');
    templateInfo.appendChild(importingNotice);
    templateInfo.appendChild(importStatus);
    templateInfo.appendChild(debugLog);

    // Create and append overlay
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    document.body.appendChild(overlay);

    // Store plugins data globally for later use
    window.pluginsData = [];

    // Plugin status constants
    const PLUGIN_STATUS = {
        NOT_INSTALLED: 'Not Installed',
        INSTALLING: 'Installing...',
        ACTIVATING: 'Activating...',
        ACTIVE: 'Active',
        ERROR: 'Error: Installation failed'
    };

    // Debug logging function
    function logDebug(message, type = 'info') {
        const timestamp = new Date().toISOString();
        const logEntry = document.createElement('div');
        logEntry.className = `log-entry log-${type}`;
        logEntry.innerHTML = `[${timestamp}] ${message}`;
        debugLog.appendChild(logEntry);
        debugLog.style.display = 'block';
        console.log(`[${type.toUpperCase()}] ${message}`);
    }

    // Modal Control Functions
    function showModal(modal) {
        modal.style.display = 'block';
        overlay.style.display = 'block';
    }

    function hideModal(modal) {
        modal.style.display = 'none';
        overlay.style.display = 'none';
    }

    function resetForm() {
        kitUrlDisplay.value = '';
        selectedTemplateId.value = '';
        importDemoBtn.disabled = false;
        importDemoBtn.innerHTML = 'Import Template Kit';
        pluginsList.innerHTML = '';
        existingPluginsList.innerHTML = '';
        hideError();
        debugLog.innerHTML = '';
        debugLog.style.display = 'none';
        
        if (window.templateKitBlob) {
            delete window.templateKitBlob;
        }
        
        document.dispatchEvent(new CustomEvent('templateKitReset'));
    }

    // Error Handling Functions
    function showError(message, details = null) {
        if (errorContainer) {
            errorContainer.style.display = 'block';
            errorContainer.querySelector('p').textContent = message;
            
            if (details) {
                logDebug(`Error: ${message}\nDetails: ${details}`, 'error');
            } else {
                logDebug(`Error: ${message}`, 'error');
            }
        } else {
            console.error(message);
        }
    }

    function hideError() {
        if (errorContainer) {
            errorContainer.style.display = 'none';
            errorContainer.querySelector('p').textContent = '';
        }
    }

    function checkAllPluginsInstalled() {
        const pluginItems = Array.from(pluginsList.querySelectorAll('.plugin-item'));
        const existingPluginItems = Array.from(existingPluginsList.querySelectorAll('.plugin-item'));
        
        // Count plugins with different statuses
        const activePlugins = pluginItems.filter(item => {
            const status = item.querySelector('.status-column').dataset.status;
            return status === PLUGIN_STATUS.ACTIVE;
        });

        const hasUninstalledPlugins = pluginItems.some(item => {
            const status = item.querySelector('.status-column').dataset.status;
            return status === PLUGIN_STATUS.NOT_INSTALLED || status === PLUGIN_STATUS.ERROR;
        });

        // All plugins are active when the number of active plugins equals the total number of plugins
        const allPluginsActive = pluginItems.length === activePlugins.length;

        // Manage Install & Activate button state
        // Only disable the button when all plugins are active or there are no uninstalled plugins
        // Do NOT disable during installation
        installActivateBtn.disabled = allPluginsActive || !hasUninstalledPlugins;
        installActivateBtn.style.opacity = hasUninstalledPlugins && !allPluginsActive ? '1' : '0.5';
        installActivateBtn.style.cursor = hasUninstalledPlugins && !allPluginsActive ? 'pointer' : 'not-allowed';

        // Update button texts for clarity - only change text, not disabled state
        if (!isInstallationInProgress) {
            installActivateBtn.textContent = hasUninstalledPlugins ? 'Install & Activate' : 'All Plugins Installed';
        }
        
        return allPluginsActive;
    }

    // Plugin Functions
    function parsePluginsFromManifest(manifestData) {
        if (!manifestData.plugins || !Array.isArray(manifestData.plugins) || manifestData.plugins.length === 0) {
            // Return a default array with Elementor if plugins array is missing or empty
            return [{
                name: "Elementor",
                slug: "elementor",
                path: "elementor/elementor",
                version: "latest",
                pluginUri: "https://downloads.wordpress.org/plugin/elementor.latest-stable.zip",
                premium: false,
                download_url: "https://downloads.wordpress.org/plugin/elementor.latest-stable.zip"
            }];
        }

        return manifestData.plugins.map(plugin => ({
            name: plugin.name,
            slug: plugin.plugin.split('/')[0],
            path: plugin.plugin,
            version: plugin.version,
            pluginUri: plugin.pluginUri,
            premium: !plugin.pluginUri.includes('wordpress.org'),
            download_url: plugin.pluginUri
        }));
    }

    function getPluginStatus(plugin) {
        if (plugin.is_active) return PLUGIN_STATUS.ACTIVE;
        if (plugin.is_installed) return 'Installed';
        return PLUGIN_STATUS.NOT_INSTALLED;
    }

    function shouldShowInExistingPlugins(plugin) {
        return plugin.is_installed || plugin.is_active;
    }

    function renderPluginItem(plugin, isExisting = false) {
        const versionLink = `<a href="#" class="version-link">Version ${plugin.version} <span class="dashicons dashicons-external"></span></a>`;
        const status = getPluginStatus(plugin);
        
        if (isExisting) {
            return `
                <div class="plugin-item">
                    <div class="checkbox-column">
                        <input type="checkbox" checked disabled>
                    </div>
                    <div class="name-column">${plugin.name}</div>
                    <div class="version-column">${versionLink}</div>
                </div>
            `;
        }

        return `
            <div class="plugin-item" data-plugin-slug="${plugin.slug}">
                <div class="checkbox-column">
                    <input type="checkbox" ${status === PLUGIN_STATUS.ACTIVE ? 'disabled' : ''} checked>
                </div>
                <div class="name-column">${plugin.name}</div>
                <div class="status-column" data-status="${status}">${status}</div>
                <div class="version-column">${versionLink}</div>
            </div>
        `;
    }

    async function checkPluginStatuses(plugins) {
        try {
            const response = await jQuery.ajax({
                url: mtl_plugin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'mtl_check_plugin_status',
                    security: mtl_plugin_vars.nonce,
                    plugins: plugins.map(plugin => ({
                        slug: plugin.slug,
                        path: plugin.path
                    }))
                }
            });

            if (response.success && response.data && response.data.statuses) {
                return response.data.statuses;
            } else {
                throw new Error(response.data?.message || 'Plugin status check failed');
            }
        } catch (error) {
            console.error('Error checking plugin statuses:', error);
            showError('Failed to check plugin statuses: ' + (error.message || 'Unknown error'));
            return {};
        }
    }

    function renderPluginLists(plugins) {
        pluginsList.innerHTML = '';
        existingPluginsList.innerHTML = '';

        plugins.forEach(plugin => {
            if (shouldShowInExistingPlugins(plugin)) {
                existingPluginsList.innerHTML += renderPluginItem(plugin, true);
            } else {
                pluginsList.innerHTML += renderPluginItem(plugin, false);
            }
        });

        // Check button states after rendering plugins
        checkAllPluginsInstalled();
    }

    // ZIP Processing Functions
    function formatFileSize(bytes) {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    async function processZipContents(zip) {
        const filesList = document.getElementById('zip-files-list');
        if (filesList) {
            filesList.innerHTML = '';
        }

        // Try to find and parse manifest.json
        const manifestFile = zip.file('manifest.json');
        let pluginsData = null;

        if (manifestFile) {
            try {
                const content = await manifestFile.async('string');
                const manifestData = JSON.parse(content);
                
                // Check if plugins array exists in the manifest
                if (!manifestData.plugins || !Array.isArray(manifestData.plugins) || manifestData.plugins.length === 0) {
                    // Create default plugins array with Elementor if missing
                    manifestData.plugins = [
                        {
                            name: "Elementor",
                            plugin: "elementor/elementor",
                            pluginUri: "https://downloads.wordpress.org/plugin/elementor.latest-stable.zip",
                            version: "latest"
                        }
                    ];
                    console.log('No plugins found in manifest.json, using default Elementor plugin');
                }
                
                pluginsData = parsePluginsFromManifest(manifestData);

                // Store plugins data globally
                window.pluginsData = pluginsData || [];

                // Check status of all plugins
                const statuses = await checkPluginStatuses(pluginsData);
                
                // Update plugin data with status information
                pluginsData = pluginsData.map(plugin => ({
                    ...plugin,
                    is_installed: statuses[plugin.slug]?.is_installed || false,
                    is_active: statuses[plugin.slug]?.is_active || false
                }));

                // Render plugins in their respective lists
                renderPluginLists(pluginsData);

                // Display ZIP contents if filesList exists
                if (filesList) {
                    zip.forEach((relativePath, zipEntry) => {
                        if (!zipEntry.dir) {
                            const fileItem = document.createElement('div');
                            fileItem.className = 'file-item';
                            
                            const size = formatFileSize(zipEntry.uncompressedSize);
                            
                            fileItem.innerHTML = `
                                <span class="dashicons dashicons-media-default file-icon"></span>
                                <span class="file-name">${relativePath}</span>
                                <span class="file-size">${size}</span>
                            `;
                            filesList.appendChild(fileItem);
                        }
                    });
                }
            } catch (error) {
                console.error('Error parsing manifest.json:', error);
                showError('Failed to parse manifest.json');
            }
        }

        return pluginsData;
    }

    async function installPlugin(plugin) {
        const pluginElement = document.querySelector(`[data-plugin-slug="${plugin.slug}"]`);
        const statusElement = pluginElement.querySelector('.status-column');
        
        try {
            statusElement.textContent = PLUGIN_STATUS.INSTALLING;
            statusElement.dataset.status = PLUGIN_STATUS.INSTALLING;
            statusElement.className = 'status-column installing';

            hideError();
            
            const existingDetails = pluginElement.querySelector('.installation-details');
            if (existingDetails) {
                existingDetails.remove();
            }

            const pluginData = {
                name: plugin.name,
                slug: plugin.slug,
                path: plugin.path,
                type: plugin.premium ? 'premium' : 'free',
                source: plugin.download_url,
                version: plugin.version
            };

            const response = await jQuery.ajax({
                url: mtl_plugin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'mtl_install_and_activate_plugin',
                    security: mtl_plugin_vars.nonce,
                    plugin: pluginData
                }
            });

            if (response.success) {
                statusElement.textContent = PLUGIN_STATUS.ACTIVE;
                statusElement.dataset.status = PLUGIN_STATUS.ACTIVE;
                statusElement.className = 'status-column success';
                pluginElement.querySelector('input[type="checkbox"]').disabled = true;
                
                // Check if all plugins are installed after each successful installation
                checkAllPluginsInstalled();
                return true;
            } else {
                throw new Error(response.data?.message || 'Installation failed');
            }

        } catch (error) {
            const errorMessage = error.message || 'Unknown error occurred during installation';
            
            statusElement.textContent = PLUGIN_STATUS.ERROR;
            statusElement.dataset.status = PLUGIN_STATUS.ERROR;
            statusElement.className = 'status-column error';
            
            const detailsDiv = document.createElement('div');
            detailsDiv.className = 'installation-details';
            detailsDiv.innerHTML = `<strong>Error Details:</strong> ${errorMessage}`;
            pluginElement.classList.add('has-error');
            pluginElement.appendChild(detailsDiv);
            
            showError(`Failed to install ${plugin.name}: ${errorMessage}`);
            
            console.error('Plugin installation failed:', error);
            return false;
        }
    }

    async function installAndActivatePlugins() {
        const selectedPlugins = Array.from(pluginsList.querySelectorAll('.plugin-item'))
            .filter(item => {
                const checkbox = item.querySelector('input[type="checkbox"]');
                const status = item.querySelector('.status-column').dataset.status;
                return checkbox.checked && status !== PLUGIN_STATUS.ACTIVE;
            })
            .map(item => {
                const plugin = window.pluginsData.find(p => p.slug === item.dataset.pluginSlug);
                return plugin || {
                    slug: item.dataset.pluginSlug,
                    name: item.querySelector('.name-column').textContent
                };
            });

        if (selectedPlugins.length === 0) {
            showError('No plugins selected for installation');
            return;
        }

        hideError();

        // Set installation in progress flag
        isInstallationInProgress = true;

        // Only disable the skip button during installation, not the install button
        skipPluginsBtn.disabled = true;

        // Update button text to show installation is in progress
        installActivateBtn.textContent = 'Installing...';

        // Dispatch event to notify that plugin installation has started
        document.dispatchEvent(new CustomEvent('pluginsInstallationStarted'));

        let failedPlugins = [];

        for (const plugin of selectedPlugins) {
            const success = await installPlugin(plugin);
            if (!success) {
                failedPlugins.push(plugin.name);
            }
        }

        // Re-enable skip button
        skipPluginsBtn.disabled = false;

        // Reset installation in progress flag
        isInstallationInProgress = false;

        // Check final installation status
        const allInstalled = checkAllPluginsInstalled();

        if (failedPlugins.length > 0) {
            showError(`Failed to install the following plugins: ${failedPlugins.join(', ')}`);
        } else if (allInstalled) {
            document.dispatchEvent(new CustomEvent('pluginsInstalled'));
        }
    }

    // File Import Function
    async function handleFileImport(file) {
        const formData = new FormData();
        formData.append('kit_file', file);
        formData.append('action', 'import_template_kit');
        formData.append('template_id', selectedTemplateId.value);
        formData.append('nonce', mtl_plugin_vars.nonce);
        
        try {
            const response = await fetch(mtl_plugin_vars.ajax_url, {
                method: 'POST',
                body: formData,
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Template kit imported successfully!');
                window.location.reload();
            } else {
                throw new Error(data.message || 'Import failed');
            }
        } catch (error) {
            console.error('Import error:', error);
            alert('Failed to import template kit. Please try again.');
        }
    }

    // Event Handlers
    importDemoBtn.addEventListener('click', async () => {
        try {
            importDemoBtn.disabled = true;
            importDemoBtn.innerHTML = '<span class="loading-spinner"></span>Processing...';

            const kitUrl = kitUrlDisplay.value;
            if (!kitUrl) {
                throw new Error('No template kit URL provided.');
            }

            const response = await fetch(kitUrl);
            const blob = await response.blob();
            const zip = await JSZip.loadAsync(blob);
            
            window.templateKitBlob = blob;
            hideModal(applyKitModal);
            
            const pluginsData = await processZipContents(zip);
            showModal(pluginRequirementsModal);

            if (pluginsData) {
                document.dispatchEvent(new CustomEvent('startPluginCheck', {
                    detail: { 
                        kitUrl: kitUrl,
                        templateId: selectedTemplateId.value,
                        requiredPlugins: pluginsData
                    }
                }));
            } else {
                showError('No plugin information found in the template kit. Using default Elementor plugin instead.');
                // Create default plugin data for Elementor
                const defaultPluginsData = [{
                    name: "Elementor",
                    slug: "elementor",
                    path: "elementor/elementor",
                    version: "latest",
                    pluginUri: "https://downloads.wordpress.org/plugin/elementor.latest-stable.zip",
                    premium: false,
                    download_url: "https://downloads.wordpress.org/plugin/elementor.latest-stable.zip",
                    is_installed: false,
                    is_active: false
                }];
                
                // Dispatch event with default plugin data
                document.dispatchEvent(new CustomEvent('startPluginCheck', {
                    detail: { 
                        kitUrl: kitUrl,
                        templateId: selectedTemplateId.value,
                        requiredPlugins: defaultPluginsData
                    }
                }));
            }

        } catch (error) {
            console.error('Error processing template kit:', error);
            alert('Failed to process template kit. Please try again.');
        } finally {
            importDemoBtn.disabled = false;
            importDemoBtn.innerHTML = 'Import Template Kit';
        }
    });

    installActivateBtn.addEventListener('click', () => installAndActivatePlugins());
    skipPluginsBtn.addEventListener('click', () => {
        document.dispatchEvent(new CustomEvent('pluginsSkipped'));
        hideModal(pluginRequirementsModal);
    });

    closeModalBtn.addEventListener('click', () => hideModal(pluginRequirementsModal));

    // Event listener for template selection
    document.addEventListener('templateSelected', function(event) {
        selectedTemplateId.value = event.detail.templateId;
        kitUrlDisplay.value = event.detail.kitUrl;
        showModal(applyKitModal);
    });

    // Handle pluginsReady event
    document.addEventListener('pluginsReady', async function() {
        try {
            const file = new File([window.templateKitBlob], 'template-kit.zip', { type: 'application/zip' });
            delete window.templateKitBlob;
            
            await handleFileImport(file);
        } catch (error) {
            console.error('Error importing template:', error);
            alert('Failed to import the template. Please try again.');
        }
    });

    // Handle version link clicks
    document.addEventListener('click', function(event) {
        if (event.target.closest('.version-link')) {
            event.preventDefault();
        }
    });

    // Handle overlay click
    overlay.addEventListener('click', () => {
        hideModal(applyKitModal);
        hideModal(pluginRequirementsModal);
    });

    // Add event listener for the apply-kit-modal close button
    if (applyKitCloseBtn) {
        applyKitCloseBtn.addEventListener('click', () => {
            hideModal(applyKitModal);
            resetForm();
        });
    }
});
</script>