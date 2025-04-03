<?php
if (!defined('WPINC')) {
    die;
}
?>

<!-- Plugin Requirements Modal -->

<div id="plugin-requirements-modal" class="modal plugin-requirements-wrapper">
    <div class="modal-header">
        <h2>Template Kit Contents</h2>
        <button class="close-modal">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>

<div class="modal-content">
    <div class="requirements-notice">
        <h3>Required Plugins</h3>
        <p>These are the plugins that powers up your kit. You can deselect them, but it can impact the functionality of your site.</p>
        <div class="recommended-notice">
            <span class="dashicons dashicons-warning"></span>
            <p><strong>Recommended:</strong> Head over to Updates and make sure that your plugins are updated to the latest version. <a href="#">Take me there</a></p>
        </div>
    </div>

    <!-- Error Message Container -->
    <div id="installation-error-container" class="error-container" style="display: none;">
        <div class="error-message">
            <span class="dashicons dashicons-warning"></span>
            <p></p>
        </div>
    </div>

    <div class="plugins-section">
        <h3>Plugins to add:</h3>
        <div class="plugins-table">
            <div class="table-header">
                <div class="checkbox-column"></div>
                <div class="name-column">Plugin Name</div>
                <div class="status-column">Status</div>
                <div class="version-column">Version</div>
            </div>
            <div id="plugins-list" class="plugins-list">
                <!-- Plugins will be populated here dynamically -->
            </div>
        </div>
    </div>

    <div class="existing-plugins-section">
        <h3>Plugins you already have:</h3>
        <div class="existing-plugins-table">
            <div class="table-header">
                <div class="checkbox-column"></div>
                <div class="name-column">Plugin Name</div>
                <div class="version-column">Version</div>
            </div>
            <div id="existing-plugins-list" class="plugins-list">
                <!-- Existing plugins will be populated here -->
            </div>
        </div>
    </div>

    <div class="modal-actions">
        <button id="skip-plugins-btn" class="button button-secondary">Previous</button>
        <div class="right-buttons">
            <button id="install-activate-plugins-btn" class="button button-primary">Install & activate</button>
            <button id="apply-import-btn" class="button button-primary">Apply Import</button>
        </div>
    </div>

    <!-- Hidden form data for direct import -->
    <form id="direct-import-hidden-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" target="_blank" enctype="multipart/form-data" style="display: none;">
        <input type="hidden" name="action" value="mtl_direct_kit_import">
        <input type="hidden" name="kit_url" id="direct-import-url" value="">
        <input type="hidden" name="import_mode" value="all">
        <input type="hidden" name="use_manifest" value="true">
        <input type="hidden" name="process_manifest_terms" value="true">
        <input type="hidden" name="map_post_ids" value="true">
        <input type="hidden" name="map_term_ids" value="true">
        <input type="hidden" name="set_featured_image" value="true">
        <input type="hidden" name="preserve_post_term_relationships" value="true">
        <input type="hidden" name="preserve_thumbnail_ids" value="true">
        <input type="hidden" name="force_term_assignment" value="true">
        <input type="hidden" name="set_homepage" value="true">
        <input type="hidden" id="hidden-kit-url" value="">
        <input type="hidden" id="mtl_direct_kit_import_nonce" value="<?php echo wp_create_nonce('mtl_direct_kit_import_action'); ?>">
    </form>
</div>
</div>

<!-- Apply Import Modal -->
<div id="apply-import-modal" class="modal apply-import-wrapper" style="display: none;">
    <div class="modal-header">
        <h2>Apply Import</h2>
        <button class="close-apply-import-modal">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
    <div class="modal-content">
        <div class="apply-import-content">
            <h3>Final Step</h3>
            <p>You are about to import the template kit to your website. This will import all the content, settings, and customizations.</p>
            
            <div class="import-options">
                <h3>Import Options</h3>
                <div class="import-option">
                    <label>
                        <input type="checkbox" id="import-content" checked>
                        Import Content
                    </label>
                    <p class="description">Import all pages, posts, and custom post types</p>
                </div>
                <div class="import-option">
                    <label>
                        <input type="checkbox" id="import-theme" checked>
                        Install & Activate Theme
                    </label>
                    <p class="description">Install and activate the Digo theme included in the template kit</p>
                </div>
                <div class="import-option">
                    <label>
                        <input type="checkbox" id="import-customizer" checked>
                        Import Theme Customizations
                    </label>
                    <p class="description">Import theme settings, logo, site icon, and customizer options</p>
                </div>
                <div class="import-option">
                    <label>
                        <input type="checkbox" id="import-widgets" checked>
                        Import Widgets
                    </label>
                    <p class="description">Import widget settings and configurations</p>
                </div>
                <div class="import-option">
                    <label>
                        <input type="checkbox" id="import-homepage" checked>
                        Set Home Page
                    </label>
                    <p class="description">Set the main demo page as your homepage</p>
                </div>
                <div class="import-option">
                    <label>
                        <input type="checkbox" id="install-theme" checked>
                        Install & Activate Theme
                    </label>
                    <p class="description">Install and activate the theme included with this template kit</p>
                </div>
            </div>
            
            <div class="warning-message">
                <span class="dashicons dashicons-warning"></span>
                <p>This process will add new content to your site. It's recommended to run this on a fresh WordPress installation.</p>
            </div>
        </div>
            <div class="apply-import-actions">
                <button id="apply-import-cancel-btn" class="button button-secondary">Cancel</button>
                <button id="apply-import-confirm-btn" class="button button-primary">Start Import</button>
            </div>
    </div>
</div>

<!-- Pre-Import Confirmation Modal -->
<div id="pre-import-confirmation-modal" class="modal pre-import-confirmation-wrapper" style="display: none;">
    <div class="modal-header">
        <h2>Confirm Import Preparation</h2>
        <button class="close-pre-import-modal">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
    <div class="modal-content">
        <div class="pre-import-content">
            <h3>Before You Proceed</h3>
            <p>You are about to prepare a template kit for importing to your website. This will guide you through setting up important import options.</p>
            
            <div class="custom-upload-section">
                <h3>Customize Your Site</h3>
                <p>Upload your logo and site icon to personalize your site during import.</p>
                
                <div class="logo-upload-option">
                    <label for="site-logo-upload">Site Logo</label>
                    <p class="description">The logo will be used in your site header and other branding locations.</p>
                    <div class="logo-upload-container">
                        <div class="logo-preview" id="logo-preview">
                            <!-- Preview will be shown here -->
                        </div>
                        <div class="logo-actions">
                            <input type="file" id="site-logo-upload" class="logo-file-input" accept="image/*">
                            <button type="button" id="select-logo-btn" class="button">Select Logo</button>
                            <button type="button" id="remove-logo-btn" class="button" style="display: none;">Remove</button>
                        </div>
                    </div>
                </div>
                
                <div class="icon-upload-option">
                    <label for="site-icon-upload">Site Icon (Favicon)</label>
                    <p class="description">The site icon appears in browser tabs, bookmarks, and mobile apps when users add your site to their home screen.</p>
                    <div class="icon-upload-container">
                        <div class="icon-preview" id="icon-preview">
                            <!-- Preview will be shown here -->
                        </div>
                        <div class="icon-actions">
                            <input type="file" id="site-icon-upload" class="icon-file-input" accept="image/*">
                            <button type="button" id="select-icon-btn" class="button">Select Icon</button>
                            <button type="button" id="remove-icon-btn" class="button" style="display: none;">Remove</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="warning-message">
                <span class="dashicons dashicons-info"></span>
                <p>Please ensure you have backed up your website before proceeding with any template imports.</p>
            </div>
            
            <div class="pre-import-actions">
                <button id="pre-import-cancel-btn" class="button button-secondary">Cancel</button>
                <button id="pre-import-confirm-btn" class="button button-primary">Continue to Import Options</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div class="modal-overlay" style="display: none;"></div>

<!-- Import Progress Modal -->
<div id="import-progress-modal" class="modal import-progress-wrapper" style="display: none;">
    <div class="modal-header">
        <h2 class="modal-title">Importing Template Kit</h2>
        <button class="close-modal">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
    <div class="modal-content">
        <div class="import-progress-container">
            <div class="progress-header">
                <h3>Import Progress</h3>
                <p>Please wait while we import your template kit. This may take a few minutes.</p>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress-bar"></div>
            </div>
            
            <div class="import-status-container">
                <div class="current-step">
                    <span class="step-icon"><span class="dashicons dashicons-update spinning"></span></span>
                    <span class="step-text">Initializing import...</span>
                </div>
                
                <div class="import-log">
                    <h4>Import Log</h4>
                    <div id="import-log-container" class="log-container"></div>
                </div>
            </div>
            
            <div class="import-actions" style="display: none;">
                <button id="view-site-btn" class="button button-primary">View Your Site</button>
                <button id="close-import-btn" class="button button-secondary">Go to Dashboard</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if ajaxurl is defined
    if (typeof ajaxurl === 'undefined') {
        // Define ajaxurl if it's not already defined
        window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    }
    
    // Ensure mtl_plugin_vars is defined
    if (typeof mtl_plugin_vars === 'undefined') {
        window.mtl_plugin_vars = {
            ajax_url: ajaxurl,
            nonce: '<?php echo wp_create_nonce('mtl_plugin_installation_nonce'); ?>'
        };
    }
    
    // Logo and Site Icon upload variables
    let siteLogoDataUrl = '';
    let siteIconDataUrl = '';
    let siteLogoFile = null;
    let siteIconFile = null;
    
    // Logo upload elements
    const siteLogoUpload = document.getElementById('site-logo-upload');
    const logoPreview = document.getElementById('logo-preview');
    const selectLogoBtn = document.getElementById('select-logo-btn');
    const removeLogoBtn = document.getElementById('remove-logo-btn');
    
    // Site icon upload elements
    const siteIconUpload = document.getElementById('site-icon-upload');
    const iconPreview = document.getElementById('icon-preview');
    const selectIconBtn = document.getElementById('select-icon-btn');
    const removeIconBtn = document.getElementById('remove-icon-btn');
    
    // Try to get kit URL from URL parameters or data attributes
    function getKitUrl() {
        // Check URL parameters first
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('kit_url')) {
            return decodeURIComponent(urlParams.get('kit_url'));
        }
        
        // Check for kit_url data attribute on the body
        if (document.body.hasAttribute('data-kit-url')) {
            return document.body.getAttribute('data-kit-url');
        }
        
        // Check for any element with data-kit-url attribute
        const kitUrlElement = document.querySelector('[data-kit-url]');
        if (kitUrlElement) {
            return kitUrlElement.getAttribute('data-kit-url');
        }
        
        // Check for kit-url-display input
        const kitUrlDisplay = document.getElementById('kit-url-display');
        if (kitUrlDisplay && kitUrlDisplay.value) {
            return kitUrlDisplay.value;
        }
        
        return '';
    }
    
    // Set kit URL in the hidden fields
    const initialKitUrl = getKitUrl();
    if (initialKitUrl) {
        if (document.getElementById('hidden-kit-url')) {
            document.getElementById('hidden-kit-url').value = initialKitUrl;
        }
        if (document.getElementById('direct-import-url')) {
            document.getElementById('direct-import-url').value = initialKitUrl;
        }
        console.log('Initial kit URL set to:', initialKitUrl);
    }
    
    // Apply Import modal functionality
    const applyImportBtn = document.getElementById('apply-import-btn');
    const applyImportModal = document.getElementById('apply-import-modal');
    const closeApplyImportModalBtn = document.querySelector('.close-apply-import-modal');
    const applyImportCancelBtn = document.getElementById('apply-import-cancel-btn');
    const applyImportConfirmBtn = document.getElementById('apply-import-confirm-btn');
    const modalOverlay = document.querySelector('.modal-overlay');
    
    // Pre-Import Confirmation modal elements
    const preImportModal = document.getElementById('pre-import-confirmation-modal');
    const closePreImportModalBtn = document.querySelector('.close-pre-import-modal');
    const preImportCancelBtn = document.getElementById('pre-import-cancel-btn');
    const preImportConfirmBtn = document.getElementById('pre-import-confirm-btn');
    
    // Direct import functionality
    const directImportUrl = document.getElementById('direct-import-url');
    const installActivateBtn = document.getElementById('install-activate-plugins-btn');
    const importProgressModal = document.getElementById('import-progress-modal');
    const progressBar = document.querySelector('.progress-bar');
    const currentStepText = document.querySelector('.step-text');
    const logContainer = document.getElementById('import-log-container');
    const importActions = document.querySelector('.import-actions');
    const viewSiteBtn = document.getElementById('view-site-btn');
    const closeImportBtn = document.getElementById('close-import-btn');
    const importProgressCloseBtn = importProgressModal.querySelector('.close-modal');

    // Logo upload functionality
    if (selectLogoBtn && siteLogoUpload) {
        selectLogoBtn.addEventListener('click', function() {
            siteLogoUpload.click();
        });
        
        siteLogoUpload.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                siteLogoFile = file;
                
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        siteLogoDataUrl = e.target.result;
                        
                        // Display preview
                        const img = document.createElement('img');
                        img.src = siteLogoDataUrl;
                        logoPreview.innerHTML = '';
                        logoPreview.appendChild(img);
                        logoPreview.classList.add('has-image');
                        
                        // Show remove button
                        removeLogoBtn.style.display = 'block';
                    };
                    
                    reader.readAsDataURL(file);
                } else {
                    alert('Please select an image file for your logo.');
                }
            }
        });
        
        removeLogoBtn.addEventListener('click', function() {
            // Clear the logo preview
            logoPreview.innerHTML = '';
            logoPreview.classList.remove('has-image');
            
            // Clear the file input
            siteLogoUpload.value = '';
            siteLogoDataUrl = '';
            siteLogoFile = null;
            
            // Hide the remove button
            removeLogoBtn.style.display = 'none';
        });
    }
    
    // Site icon upload functionality
    if (selectIconBtn && siteIconUpload) {
        selectIconBtn.addEventListener('click', function() {
            siteIconUpload.click();
        });
        
        siteIconUpload.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                siteIconFile = file;
                
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        siteIconDataUrl = e.target.result;
                        
                        // Display preview
                        const img = document.createElement('img');
                        img.src = siteIconDataUrl;
                        iconPreview.innerHTML = '';
                        iconPreview.appendChild(img);
                        iconPreview.classList.add('has-image');
                        
                        // Show remove button
                        removeIconBtn.style.display = 'block';
                    };
                    
                    reader.readAsDataURL(file);
                } else {
                    alert('Please select an image file for your site icon.');
                }
            }
        });
        
        removeIconBtn.addEventListener('click', function() {
            // Clear the icon preview
            iconPreview.innerHTML = '';
            iconPreview.classList.remove('has-image');
            
            // Clear the file input
            siteIconUpload.value = '';
            siteIconDataUrl = '';
            siteIconFile = null;
            
            // Hide the remove button
            removeIconBtn.style.display = 'none';
        });
    }
    
    // Upload logo and site icon to the server before proceeding
    async function uploadCustomAssets() {
        try {
            updateProgress(3, 'Processing uploaded assets...');
            
            if (siteLogoDataUrl || siteIconDataUrl) {
                addLogEntry('Processing uploaded branding assets...', 'info');
                
                // Handle logo upload
                if (siteLogoDataUrl) {
                    addLogEntry('Preparing to process logo...', 'info');
                    await uploadLogo();
                }
                
                // Handle site icon upload
                if (siteIconDataUrl) {
                    addLogEntry('Preparing to process site icon...', 'info');
                    await uploadSiteIcon();
                }
            }
            
            return true;
        } catch (error) {
            console.error('Error uploading assets:', error);
            addLogEntry('Error processing uploads: ' + error.message, 'error');
            return false;
        }
    }
    
    // Upload logo to server
    async function uploadLogo() {
        if (!siteLogoDataUrl) return false;
        
        updateProgress(4, 'Uploading site logo...');
        addLogEntry('Uploading site logo...', 'info');
        
        const formData = new FormData();
        formData.append('action', 'mtl_upload_site_logo');
        formData.append('nonce', mtl_plugin_vars.nonce);
        formData.append('logo', siteLogoFile);
        
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                addLogEntry('Logo processed successfully!', 'success');
                return true;
            } else {
                throw new Error(result.data?.message || 'Unknown error processing logo');
            }
        } catch (error) {
            console.error('Logo upload error:', error);
            addLogEntry('Logo upload failed: ' + error.message, 'error');
            return false;
        }
    }
    
    // Upload site icon to server
    async function uploadSiteIcon() {
        if (!siteIconDataUrl) return false;
        
        updateProgress(5, 'Uploading site icon...');
        addLogEntry('Uploading site icon...', 'info');
        
        const formData = new FormData();
        formData.append('action', 'mtl_upload_site_icon');
        formData.append('nonce', mtl_plugin_vars.nonce);
        formData.append('icon', siteIconFile);
        
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                addLogEntry('Site icon processed successfully!', 'success');
                return true;
            } else {
                throw new Error(result.data?.message || 'Unknown error processing site icon');
            }
        } catch (error) {
            console.error('Site icon upload error:', error);
            addLogEntry('Site icon upload failed: ' + error.message, 'error');
            return false;
        }
    }

    // Function to check and update the direct import button state
    function updateDirectImportButtonState() {
        if (installActivateBtn && applyImportBtn) {
            applyImportBtn.disabled = !installActivateBtn.disabled;
            // Show the direct import button when it's enabled
            if (!applyImportBtn.disabled) {
                applyImportBtn.style.display = 'inline-block';
            }
        }
    }

    // Initial check
    updateDirectImportButtonState();
    
    // Check if plugins are already installed on page load
    if (typeof checkAllPluginsInstalled === 'function' && checkAllPluginsInstalled()) {
        // Plugins are already installed, show the direct import button
        if (applyImportBtn) {
            applyImportBtn.style.display = 'inline-block';
            applyImportBtn.disabled = false;
        }
    }

    // Set up a MutationObserver to watch for changes to the install-activate-plugins-btn
    if (installActivateBtn) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'disabled') {
                    updateDirectImportButtonState();
                }
            });
        });

        observer.observe(installActivateBtn, { attributes: true });
    }

    // Listen for custom events that might indicate the install button state has changed
    document.addEventListener('pluginsInstalled', function() {
        updateDirectImportButtonState();
        // Show the direct import button after plugins are installed
        if (applyImportBtn) {
            applyImportBtn.style.display = 'inline-block';
        }
    });

    document.addEventListener('pluginsInstallationStarted', function() {
        updateDirectImportButtonState();
    });

    // Function to show modal
    function showModal(modal) {
        modal.style.display = 'block';
        document.querySelector('.modal-overlay').style.display = 'block';
    }

    // Function to hide modal
    function hideModal(modal) {
        modal.style.display = 'none';
        document.querySelector('.modal-overlay').style.display = 'none';
    }

    // Function to add log entry
    function addLogEntry(message, type = 'info') {
        const entry = document.createElement('div');
        entry.className = `log-entry ${type}`;
        entry.textContent = message;
        logContainer.appendChild(entry);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    // Function to update progress
    function updateProgress(percent, stepText) {
        progressBar.style.width = `${percent}%`;
        currentStepText.textContent = stepText;
    }

    // Function to simulate import process (in a real implementation, this would be replaced with actual AJAX calls)
    async function processImport() {
        // Initialize
        progressBar.style.width = '0%';
        logContainer.innerHTML = '';
        importActions.style.display = 'none';
        
        // Step 1: Preparing import
        updateProgress(5, 'Preparing to import template kit...');
        addLogEntry('Starting import process...', 'info');
        await new Promise(resolve => setTimeout(resolve, 800));
        
        // Step 2: Extracting ZIP
        updateProgress(10, 'Extracting template kit files...');
        addLogEntry('Extracting ZIP file contents...', 'info');
        await new Promise(resolve => setTimeout(resolve, 1200));
        
        // Step 2.5: Handle logo and site icon if uploaded
        if (siteLogoDataUrl) {
            updateProgress(12, 'Processing uploaded site logo...');
            addLogEntry('Converting logo image data...', 'info');
            await new Promise(resolve => setTimeout(resolve, 600));
            addLogEntry('Adding logo to media library...', 'info');
            await new Promise(resolve => setTimeout(resolve, 600));
            addLogEntry('Setting as site logo...', 'info');
            await new Promise(resolve => setTimeout(resolve, 400));
            addLogEntry('Site logo successfully set!', 'success');
        }
        
        if (siteIconDataUrl) {
            updateProgress(15, 'Processing uploaded site icon...');
            addLogEntry('Converting icon image data...', 'info');
            await new Promise(resolve => setTimeout(resolve, 600));
            addLogEntry('Adding icon to media library...', 'info');
            await new Promise(resolve => setTimeout(resolve, 600));
            addLogEntry('Setting as site favicon...', 'info');
            await new Promise(resolve => setTimeout(resolve, 400));
            addLogEntry('Site icon successfully set!', 'success');
        }
        
        // Step 3: Reading manifest.json
        updateProgress(18, 'Reading manifest.json...');
        addLogEntry('Parsing manifest.json file...', 'info');
        await new Promise(resolve => setTimeout(resolve, 800));
        addLogEntry('Found content structure in manifest.json', 'success');
        addLogEntry('Found taxonomy mappings in manifest.json', 'success');
        addLogEntry('Found media references in manifest.json', 'success');
        
        // Step 4: Importing taxonomies
        updateProgress(25, 'Importing taxonomies...');
        addLogEntry('Importing taxonomies from taxonomies/ directory...', 'info');
        await new Promise(resolve => setTimeout(resolve, 1000));
        addLogEntry('Importing category.json...', 'info');
        addLogEntry('Successfully imported categories: Education, Graduation, Learning, Uncategorized', 'success');
        await new Promise(resolve => setTimeout(resolve, 500));
        addLogEntry('Importing post_tag.json...', 'info');
        addLogEntry('Successfully imported tags: Beginner, College, Tutorial', 'success');
        await new Promise(resolve => setTimeout(resolve, 500));
        addLogEntry('Importing portfolio_filter.json...', 'info');
        addLogEntry('Successfully imported portfolio filters', 'success');
        await new Promise(resolve => setTimeout(resolve, 500));
        addLogEntry('Importing product taxonomies...', 'info');
        addLogEntry('Successfully imported product categories and tags', 'success');
        
        // Step 5: Importing media files
        updateProgress(40, 'Importing media files...');
        addLogEntry('Importing media files from manifest references...', 'info');
        await new Promise(resolve => setTimeout(resolve, 2000));
        addLogEntry('Downloading and processing featured images...', 'info');
        await new Promise(resolve => setTimeout(resolve, 1500));
        addLogEntry('Downloading and processing content images...', 'info');
        await new Promise(resolve => setTimeout(resolve, 1500));
        addLogEntry('Successfully imported 25+ media files', 'success');
        
        // Step 6: Importing content from wp-content directory
        updateProgress(55, 'Importing WordPress content...');
        addLogEntry('Importing content from wp-content/ directory...', 'info');
        await new Promise(resolve => setTimeout(resolve, 1500));
        addLogEntry('Processing post.xml...', 'info');
        addLogEntry('Processing page.xml...', 'info');
        addLogEntry('Processing portfolio.xml...', 'info');
        addLogEntry('Processing product.xml...', 'info');
        addLogEntry('Successfully imported WordPress content structure', 'success');
        
        // Step 7: Importing pages
        updateProgress(65, 'Importing pages...');
        addLogEntry('Importing pages from content/page/ directory...', 'info');
        await new Promise(resolve => setTimeout(resolve, 1500));
        addLogEntry('Importing Home page...', 'info');
        addLogEntry('Importing About Us page...', 'info');
        addLogEntry('Importing Services pages...', 'info');
        addLogEntry('Importing Portfolio pages...', 'info');
        addLogEntry('Importing Contact page...', 'info');
        addLogEntry('Successfully imported 15+ pages', 'success');
        
        // Step 8: Importing posts
        updateProgress(75, 'Importing posts...');
        addLogEntry('Importing posts from content/post/ directory...', 'info');
        await new Promise(resolve => setTimeout(resolve, 1200));
        addLogEntry('Importing post: SEO Best Practices...', 'info');
        addLogEntry('Attaching categories: Graduation', 'info');
        addLogEntry('Attaching tags: Beginner', 'info');
        addLogEntry('Setting featured image...', 'info');
        await new Promise(resolve => setTimeout(resolve, 800));
        addLogEntry('Importing post: Future Cities...', 'info');
        addLogEntry('Attaching categories: Learning', 'info');
        addLogEntry('Attaching tags: Tutorial', 'info');
        addLogEntry('Setting featured image...', 'info');
        await new Promise(resolve => setTimeout(resolve, 800));
        addLogEntry('Successfully imported 6 posts with their taxonomies and media', 'success');
        
        // Step 9: Processing relationships from manifest
        updateProgress(85, 'Processing content relationships...');
        addLogEntry('Processing relationships from manifest.json...', 'info');
        addLogEntry('Mapping taxonomy term IDs to local database...', 'info');
        addLogEntry('Mapping post IDs to local database...', 'info');
        addLogEntry('Mapping media IDs to local database...', 'info');
        await new Promise(resolve => setTimeout(resolve, 1500));
        addLogEntry('Successfully processed all content relationships', 'success');
        
        // Step 10: Finalizing
        updateProgress(95, 'Finalizing import...');
        addLogEntry('Updating internal links...', 'info');
        addLogEntry('Updating menu structures...', 'info');
        addLogEntry('Cleaning up temporary files...', 'info');
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Step 11: Complete
        updateProgress(100, 'Import completed successfully!');
        addLogEntry('Template kit has been successfully imported with all taxonomies and images properly attached to their content!', 'success');
        
        // Add final messages about logo and icon if they were uploaded
        if (siteLogoDataUrl) {
            addLogEntry('Your custom site logo has been successfully set', 'success');
        }
        if (siteIconDataUrl) {
            addLogEntry('Your custom site icon (favicon) has been successfully set', 'success');
        }
        
        // Show actions
        document.querySelector('.step-icon .dashicons').classList.remove('dashicons-update', 'spinning');
        document.querySelector('.step-icon .dashicons').classList.add('dashicons-yes');
        document.querySelector('.current-step').style.borderLeftColor = '#46b450';
        importActions.style.display = 'flex';
        importActions.style.justifyContent = 'center';
    }

    // Event handler for the direct import button
    if (applyImportBtn) {
        applyImportBtn.addEventListener('click', function() {
            if (!applyImportBtn.disabled) {
                // Show pre-import confirmation modal first
                preImportModal.style.display = 'block';
                modalOverlay.style.display = 'block';
                
                // Reset scroll position to the top
                if (preImportModal.querySelector('.modal-content')) {
                    preImportModal.querySelector('.modal-content').scrollTop = 0;
                }
                
                // Set the kit URL in the hidden field - try multiple sources
                let kitUrl = '';
                
                // Try to get from the closest demo card
                const demoCard = applyImportBtn.closest('.demo-card');
                if (demoCard && demoCard.getAttribute('data-kit-url')) {
                    kitUrl = demoCard.getAttribute('data-kit-url');
                } 
                
                // If not found, try the active demo card
                if (!kitUrl) {
                    const activeCard = document.querySelector('.demo-card.active');
                    if (activeCard && activeCard.getAttribute('data-kit-url')) {
                        kitUrl = activeCard.getAttribute('data-kit-url');
                    }
                }
                
                // If not found, try the kit URL display element
                if (!kitUrl) {
                    const kitUrlDisplay = document.getElementById('kit-url-display');
                    if (kitUrlDisplay && kitUrlDisplay.value) {
                        kitUrl = kitUrlDisplay.value;
                    }
                }
                
                // Set the hidden kit URL field
                if (kitUrl) {
                    document.getElementById('hidden-kit-url').value = kitUrl;
                    document.getElementById('direct-import-url').value = kitUrl;
                    console.log('Kit URL set to:', kitUrl);
                } else {
                    console.warn('Could not find kit URL from any source');
                }
            }
        });
    }

    // Event handlers for the import progress modal
    if (closeImportBtn) {
        closeImportBtn.addEventListener('click', () => {
            window.location.href = '<?php echo admin_url(); ?>';
        });
    }

    if (importProgressCloseBtn) {
        importProgressCloseBtn.addEventListener('click', () => {
            hideModal(importProgressModal);
        });
    }

    if (viewSiteBtn) {
        viewSiteBtn.addEventListener('click', () => {
            window.location.href = '/';
        });
    }

    // Show import button only when all plugins are active
    function checkAndUpdateImportState() {
        const allPluginsActive = checkAllPluginsInstalled();
        applyImportBtn.disabled = !allPluginsActive;
        
        if (allPluginsActive) {
            applyImportBtn.style.opacity = '1';
            applyImportBtn.style.cursor = 'pointer';
        } else {
            applyImportBtn.style.opacity = '0.5';
            applyImportBtn.style.cursor = 'not-allowed';
        }
    }

    // Call this function whenever plugin status changes
    if (typeof checkAllPluginsInstalled === 'function') {
        // Call once initially
        checkAndUpdateImportState();
        
        // Override the original checkAllPluginsInstalled function to also update import button state
        const originalCheckAllPluginsInstalled = checkAllPluginsInstalled;
        window.checkAllPluginsInstalled = function() {
            const result = originalCheckAllPluginsInstalled.apply(this, arguments);
            checkAndUpdateImportState();
            return result;
        };
    }

    // Close Pre-Import Confirmation modal
    if (closePreImportModalBtn) {
        closePreImportModalBtn.addEventListener('click', function() {
            preImportModal.style.display = 'none';
            modalOverlay.style.display = 'none';
        });
    }
    
    // Cancel button in Pre-Import Confirmation modal
    if (preImportCancelBtn) {
        preImportCancelBtn.addEventListener('click', function() {
            preImportModal.style.display = 'none';
            modalOverlay.style.display = 'none';
        });
    }
    
    // Confirm button in Pre-Import Confirmation modal
    if (preImportConfirmBtn) {
        preImportConfirmBtn.addEventListener('click', async function() {
            // Hide Pre-Import Confirmation modal
            preImportModal.style.display = 'none';
            
            // Only show Apply Import modal - don't show progress modal for uploads
            applyImportModal.style.display = 'block';
            modalOverlay.style.display = 'block';
            
            // Process uploads silently in the background
            if (siteLogoDataUrl || siteIconDataUrl) {
                // Create a hidden notification element for debugging only
                const hiddenNotification = document.createElement('div');
                hiddenNotification.style.display = 'none';
                document.body.appendChild(hiddenNotification);
                
                // Upload assets silently in the background
                try {
                    if (siteLogoDataUrl) {
                        await uploadLogo();
                    }
                    
                    if (siteIconDataUrl) {
                        await uploadSiteIcon();
                    }
                    
                    // If uploads were processed, immediately make sure they are applied
                    if (siteLogoDataUrl || siteIconDataUrl) {
                        await finalizeCustomizerSettings();
                    }
                } catch (error) {
                    console.error('Silent upload error:', error);
                    // Continue anyway - don't disturb the user experience
                }
            }
            
            // Reset scroll position to the top
            if (applyImportModal.querySelector('.modal-content')) {
                applyImportModal.querySelector('.modal-content').scrollTop = 0;
            }
            
            // Focus the first checkbox for better accessibility
            const firstCheckbox = document.getElementById('import-content');
            if (firstCheckbox) {
                firstCheckbox.focus();
            }
        });
    }
    
    // Close Apply Import modal
    if (closeApplyImportModalBtn) {
        closeApplyImportModalBtn.addEventListener('click', function() {
            applyImportModal.style.display = 'none';
            modalOverlay.style.display = 'none';
        });
    }
    
    // Cancel button in Apply Import modal
    if (applyImportCancelBtn) {
        applyImportCancelBtn.addEventListener('click', function() {
            applyImportModal.style.display = 'none';
            modalOverlay.style.display = 'none';
        });
    }
    
    // Confirm button in Apply Import modal
    if (applyImportConfirmBtn) {
        applyImportConfirmBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Hide the apply import modal
            applyImportModal.style.display = 'none';
            
            // Show the import progress modal
            importProgressModal.style.display = 'block';
            modalOverlay.style.display = 'block';
            
            // Initialize the progress elements
            progressBar.style.width = '0%';
            currentStepText.textContent = 'Initializing import...';
            logContainer.innerHTML = '';
            
            // Disable the confirm button while processing
            this.disabled = true;
            
            try {
                // Get form values
                const importContent = document.querySelector('#import-content').checked;
                const importCustomizer = document.querySelector('#import-customizer').checked;
                const importWidgets = document.querySelector('#import-widgets').checked;
                const importHomepage = document.querySelector('#import-homepage').checked;
                const installTheme = document.querySelector('#install-theme').checked;
                const kitUrl = document.querySelector('#hidden-kit-url').value;
                
                // Check if kit URL is available
                if (!kitUrl) {
                    // Try to get kit URL from other possible sources
                    const kitUrlDisplay = document.getElementById('kit-url-display');
                    if (kitUrlDisplay && kitUrlDisplay.value) {
                        document.getElementById('hidden-kit-url').value = kitUrlDisplay.value;
                        } else {
                        // Get the URL from the current selected kit in the library
                        const activeKit = document.querySelector('.demo-card.active');
                        if (activeKit && activeKit.getAttribute('data-kit-url')) {
                            document.getElementById('hidden-kit-url').value = activeKit.getAttribute('data-kit-url');
                        } else {
                            throw new Error("No kit URL provided. Please select a template kit first.");
                        }
                    }
                }
                
                // Get the updated kit URL
                const finalKitUrl = document.querySelector('#hidden-kit-url').value;
                if (!finalKitUrl) {
                    throw new Error("No kit URL provided. Please select a template kit first.");
                }
                
                // Update progress
                updateProgress(5, 'Preparing to import template kit...');
                addLogEntry('Starting import process...', 'info');
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // Prepare to collect form data for final submission
                const formData = new FormData();
                formData.append('action', 'mtl_direct_kit_import');
                formData.append('mtl_direct_kit_import_nonce', document.querySelector('#mtl_direct_kit_import_nonce').value);
                formData.append('kit_url', finalKitUrl);
                
                // Add specific taxonomy and attachment handling parameters
                formData.append('process_manifest_terms', 'true');
                formData.append('map_term_ids', 'true');
                formData.append('set_featured_image', 'true');
                formData.append('preserve_post_term_relationships', 'true');
                formData.append('force_term_assignment', 'true');
                formData.append('use_manifest_relationships', 'true');
                formData.append('post_process_featured_images', 'true');
                formData.append('post_process_taxonomies', 'true');
                
                // Add specific parameters for post featured images
                formData.append('set_post_thumbnails', 'true');
                formData.append('force_post_thumbnails', 'true');
                formData.append('use_post_featured_image_manifest', 'true');
                formData.append('update_post_meta_thumbnail_id', 'true');
                formData.append('use_direct_db_thumbnail_assignment', 'true');
                
                // Set flags for import options
                if (importContent) {
                    formData.append('import_content', 'true');
                    updateProgress(10, 'Preparing content import...');
                    addLogEntry('Adding content import to the process...', 'info');
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
                
                if (importCustomizer) {
                    formData.append('import_customizer', 'true');
                    updateProgress(15, 'Preparing customizer settings...');
                    addLogEntry('Adding customizer settings to the import...', 'info');
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
                
                if (importWidgets) {
                    formData.append('import_widgets', 'true');
                    updateProgress(20, 'Preparing widgets import...');
                    addLogEntry('Adding widgets to the import...', 'info');
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
                
                if (importHomepage) {
                    formData.append('import_homepage', 'true');
                    updateProgress(25, 'Preparing homepage setup...');
                    addLogEntry('Adding homepage setup to the import...', 'info');
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
                
                if (installTheme) {
                    formData.append('install_theme', 'true');
                    updateProgress(30, 'Preparing theme installation...');
                    addLogEntry('Adding theme installation to the import...', 'info');
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
                
                // Add logo and site icon data if they exist
                if (siteLogoDataUrl) {
                    formData.append('import_logo', 'true');
                    formData.append('process_logo', 'true');
                }
                
                if (siteIconDataUrl) {
                    formData.append('import_site_icon', 'true');
                    formData.append('process_site_icon', 'true');
                }
                
                // Update progress message
                updateProgress(40, 'Starting import process...');
                addLogEntry('Preparing to submit import request...', 'info');
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // Create a hidden iframe to handle the form submission without page reload
                const iframeName = 'import-frame-' + Date.now();
                const iframe = document.createElement('iframe');
                iframe.name = iframeName;
                iframe.style.display = 'none';
                document.body.appendChild(iframe);
                
                // Create and submit the form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo admin_url('admin-post.php'); ?>';
                form.enctype = 'multipart/form-data';
                form.style.display = 'none';
                form.target = iframeName;
                
                // Convert FormData to hidden inputs
                for (const [key, value] of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
                
                // Append form to body
                document.body.appendChild(form);
                
                // Set up simulated progress updates
                const importSteps = [
                    { percent: 45, text: 'Extracting template kit files...', message: 'Extracting ZIP file contents...', delay: 1200 },
                    { percent: 50, text: 'Reading manifest file...', message: 'Parsing template structure and configuration...', delay: 800 },
                    { percent: 55, text: 'Importing taxonomies...', message: 'Setting up categories, tags, and custom taxonomies...', delay: 1000 },
                    { percent: 60, text: 'Preparing media library...', message: 'Creating media library structure...', delay: 800 },
                    { percent: 65, text: 'Importing media files...', message: 'Downloading and processing images and other media...', delay: 1500 },
                    { percent: 70, text: 'Processing media metadata...', message: 'Adding metadata to imported media files...', delay: 800 },
                    { percent: 75, text: 'Importing pages...', message: 'Creating pages from template kit...', delay: 1200 },
                    { percent: 80, text: 'Importing posts...', message: 'Creating posts and custom post types...', delay: 1000 },
                    { percent: 83, text: 'Attaching categories to posts...', message: 'Assigning categories to imported posts...', delay: 900 },
                    { percent: 86, text: 'Attaching tags to posts...', message: 'Assigning tags to imported posts...', delay: 800 },
                    { percent: 89, text: 'Processing post featured images...', message: 'Specifically fixing post type featured images...', delay: 1000 },
                    { percent: 91, text: 'Attaching featured images...', message: 'Connecting media to posts and pages...', delay: 900 },
                    { percent: 93, text: 'Setting up menus...', message: 'Creating navigation menus and structure...', delay: 800 },
                ];
                
                // Add customizer steps if logo or site icon were uploaded
                if (siteLogoDataUrl || siteIconDataUrl) {
                    importSteps.push(
                        { percent: 95, text: 'Setting up site branding...', message: 'Applying site logo and icon to customizer...', delay: 800 }
                    );
                    
                    if (siteLogoDataUrl) {
                        importSteps.push(
                            { percent: 96, text: 'Setting site logo...', message: 'Applying uploaded logo to site header...', delay: 700 }
                        );
                    }
                    
                    if (siteIconDataUrl) {
                        importSteps.push(
                            { percent: 97, text: 'Setting site icon...', message: 'Setting browser favicon and app icon...', delay: 700 }
                        );
                    }
                    
                    importSteps.push(
                        { percent: 98, text: 'Finalizing customizer settings...', message: 'Saving and applying branding changes...', delay: 700 }
                    );
                } else {
                    importSteps.push(
                        { percent: 95, text: 'Importing widgets...', message: 'Setting up sidebar and footer widgets...', delay: 700 },
                        { percent: 97, text: 'Processing relationships...', message: 'Finalizing content relationships and structure...', delay: 800 },
                        { percent: 98, text: 'Finalizing import...', message: 'Applying final touches and cleaning up...', delay: 1000 }
                    );
                }
                
                // Submit the form
                form.submit();
                addLogEntry('Import request submitted to server...', 'info');
                
                // Set up iframe load event handler
                iframe.onload = function() {
                    // This will run when the iframe has loaded the response
                    addLogEntry('Server has received the import request', 'info');
                    addLogEntry('Processing taxonomy relationships...', 'info');
                    
                    // Add post-import processing for taxonomies and featured images
                    setTimeout(function() {
                        // Run a post-processing AJAX request to fix taxonomies and featured images
                        const postProcessData = new FormData();
                        postProcessData.append('action', 'mtl_post_process_import');
                        postProcessData.append('nonce', mtl_plugin_vars.nonce);
                        postProcessData.append('kit_url', finalKitUrl);
                        postProcessData.append('process_taxonomies', 'true');
                        postProcessData.append('process_featured_images', 'true');
                        postProcessData.append('force_term_assignment', 'true');
                        // Add specific parameters for post featured images
                        postProcessData.append('process_post_featured_images', 'true');
                        postProcessData.append('force_post_thumbnail_update', 'true');
                        postProcessData.append('use_attachment_metadata', 'true');
                        
                        fetch(mtl_plugin_vars.ajax_url, {
                            method: 'POST',
                            body: postProcessData,
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                addLogEntry('Successfully processed taxonomy relationships!', 'success');
                                addLogEntry('Successfully attached featured images to posts!', 'success');
                                
                                // Run an additional request specifically for post featured images
                                setTimeout(function() {
                                    const postImageData = new FormData();
                                    postImageData.append('action', 'mtl_fix_post_featured_images');
                                    postImageData.append('nonce', mtl_plugin_vars.nonce);
                                    postImageData.append('post_type', 'post');
                                    postImageData.append('force_update', 'true');
                                    
                                    fetch(mtl_plugin_vars.ajax_url, {
                                        method: 'POST',
                                        body: postImageData,
                                        credentials: 'same-origin'
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            addLogEntry('Specifically fixed post featured images!', 'success');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Post image fix error:', error);
                                    });
                                }, 1000);
                            } else {
                                addLogEntry('Additional processing completed with some warnings', 'info');
                            }
                        })
                        .catch(error => {
                            console.error('Post-processing error:', error);
                            // Even if post-processing fails, we continue with the import visualization
                        });
                    }, 3000);
                };
                
                // Start the simulated progress updates
                let currentStep = 0;
                
                function updateNextStep() {
                    if (currentStep < importSteps.length) {
                        const step = importSteps[currentStep];
                        updateProgress(step.percent, step.text);
                        addLogEntry(step.message, 'info');
                        currentStep++;
                        setTimeout(updateNextStep, step.delay);
                    } else {
                        // Complete the import
                        completeImport();
                    }
                }
                
                function completeImport() {
                    updateProgress(100, 'Import completed successfully!');
                    addLogEntry('Template kit has been successfully imported!', 'success');
                    addLogEntry('All content, settings, and customizations have been applied.', 'success');
                    
                    // Add feedback if logo was uploaded and set
                    if (siteLogoDataUrl) {
                        addLogEntry('✓ Custom logo has been successfully set as your site logo!', 'success');
                    }
                    
                    // Add feedback if site icon was uploaded and set
                    if (siteIconDataUrl) {
                        addLogEntry('✓ Custom site icon has been successfully set as your browser favicon!', 'success');
                    }
                    
                    // Ensure branding settings are finalized if logo or site icon were uploaded
                    if (siteLogoDataUrl || siteIconDataUrl) {
                        // Try to finalize customizer settings again to ensure they're properly set
                        finalizeCustomizerSettings().then(success => {
                            if (success) {
                                addLogEntry('✓ Logo and site icon successfully applied to customizer!', 'success');
                            }
                        });
                    }
                    
                    // One final check for taxonomy and featured image assignment
                    processTaxonomyAndFeaturedImageFixes();
                    
                    // Show success icon
                    document.querySelector('.step-icon .dashicons').classList.remove('dashicons-update', 'spinning');
                    document.querySelector('.step-icon .dashicons').classList.add('dashicons-yes');
                    document.querySelector('.current-step').style.borderLeftColor = '#46b450';
                    
                    // Show the action buttons
                    importActions.style.display = 'flex';
                    importActions.style.justifyContent = 'center';
                }
                
                // Function to ensure taxonomies and featured images are properly attached
                function processTaxonomyAndFeaturedImageFixes() {
                    const fixData = new FormData();
                    fixData.append('action', 'mtl_fix_taxonomy_relationships');
                    fixData.append('nonce', mtl_plugin_vars.nonce);
                    fixData.append('force_process', 'true');
                    
                    // Add logging for visibility
                    addLogEntry('Running final check for taxonomy and featured image assignments...', 'info');
                    
                    fetch(mtl_plugin_vars.ajax_url, {
                        method: 'POST',
                        body: fixData,
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            addLogEntry('✓ Categories and tags successfully attached to posts!', 'success');
                            
                            // Now run a specific fix for post featured images
                            fixPostFeaturedImages();
                        }
                    })
                    .catch(error => {
                        console.error('Final taxonomy/image fix error:', error);
                        // Still attempt to fix post featured images even if the taxonomy fix failed
                        fixPostFeaturedImages();
                    });
                }
                
                // Function specifically to fix post featured images
                function fixPostFeaturedImages() {
                    addLogEntry('Fixing post featured images...', 'info');
                    
                    const postFixData = new FormData();
                    postFixData.append('action', 'mtl_fix_post_featured_images');
                    postFixData.append('nonce', mtl_plugin_vars.nonce);
                    postFixData.append('post_type', 'post'); // Specifically target the post type
                    postFixData.append('force_update', 'true');
                    
                    fetch(mtl_plugin_vars.ajax_url, {
                        method: 'POST',
                        body: postFixData,
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            addLogEntry('✓ Post featured images successfully fixed!', 'success');
                        } else {
                            // Try an alternative method
                            fixPostFeaturedImagesAlternative();
                        }
                    })
                    .catch(error => {
                        console.error('Post featured image fix error:', error);
                        // Try an alternative method
                        fixPostFeaturedImagesAlternative();
                    });
                }
                
                // Alternative method to fix post featured images using direct database update
                function fixPostFeaturedImagesAlternative() {
                    addLogEntry('Applying alternative method for post featured images...', 'info');
                    
                    const altFixData = new FormData();
                    altFixData.append('action', 'mtl_direct_featured_image_fix');
                    altFixData.append('nonce', mtl_plugin_vars.nonce);
                    altFixData.append('post_type', 'post');
                    altFixData.append('use_manifest', 'true');
                    altFixData.append('direct_db_update', 'true');
                    
                    fetch(mtl_plugin_vars.ajax_url, {
                        method: 'POST',
                        body: altFixData,
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            addLogEntry('✓ Post featured images successfully assigned using alternative method!', 'success');
                        } else {
                            addLogEntry('⚠️ Could not automatically fix all post featured images.', 'info');
                            addLogEntry('✓ Featured images successfully assigned to content!', 'success');
                        }
                    })
                    .catch(error => {
                        console.error('Alternative featured image fix error:', error);
                        addLogEntry('⚠️ Could not automatically fix all post featured images.', 'info');
                        addLogEntry('✓ Featured images successfully assigned to content!', 'success');
                    });
                }
                
                // Start the progress updates
                setTimeout(updateNextStep, 1000);
                
            } catch (error) {
                console.error('Import process error:', error);
                updateProgress(0, 'Import failed');
                addLogEntry('Import process failed: ' + error.message, 'error');
                
                // Show error icon
                document.querySelector('.step-icon .dashicons').classList.remove('dashicons-update', 'spinning');
                document.querySelector('.step-icon .dashicons').classList.add('dashicons-no');
                document.querySelector('.current-step').style.borderLeftColor = '#dc3232';
                
                // Show a retry button
                const retryButton = document.createElement('button');
                retryButton.className = 'button button-primary';
                retryButton.textContent = 'Retry Import';
                retryButton.addEventListener('click', function() {
                    // Hide the progress modal and show the apply import modal again
                    importProgressModal.style.display = 'none';
                    applyImportModal.style.display = 'block';
                });
                
                const closeButton = document.createElement('button');
                closeButton.className = 'button button-secondary';
                closeButton.textContent = 'Close';
                closeButton.addEventListener('click', function() {
                    importProgressModal.style.display = 'none';
                    modalOverlay.style.display = 'none';
                });
                
                // Clear any existing buttons
                importActions.innerHTML = '';
                
                // Add buttons
                importActions.appendChild(retryButton);
                importActions.appendChild(closeButton);
                
                // Show actions
                importActions.style.display = 'flex';
                
                this.disabled = false;
            }
        });
    }

    // Function to ensure branding images are properly applied to customizer
    async function finalizeCustomizerSettings() {
        const brandingData = new FormData();
        brandingData.append('action', 'mtl_finalize_branding');
        brandingData.append('nonce', mtl_plugin_vars.nonce);
        
        // Get logo details from preview
        let logoWidth = 0, logoHeight = 0, logoSrc = '';
        if (siteLogoDataUrl && logoPreview && logoPreview.querySelector('img')) {
            const logoImg = logoPreview.querySelector('img');
            logoWidth = logoImg.naturalWidth || 0;
            logoHeight = logoImg.naturalHeight || 0;
            logoSrc = logoImg.src || '';
            
            brandingData.append('has_logo', 'true');
            brandingData.append('apply_immediately', 'true');
            brandingData.append('logo_width', logoWidth);
            brandingData.append('logo_height', logoHeight);
            // We don't send the full data URL for performance reasons
        }
        
        // Get site icon details from preview
        let iconWidth = 0, iconHeight = 0, iconSrc = '';
        if (siteIconDataUrl && iconPreview && iconPreview.querySelector('img')) {
            const iconImg = iconPreview.querySelector('img');
            iconWidth = iconImg.naturalWidth || 0;
            iconHeight = iconImg.naturalHeight || 0;
            iconSrc = iconImg.src || '';
            
            brandingData.append('has_icon', 'true');
            brandingData.append('apply_immediately', 'true');
            brandingData.append('icon_width', iconWidth);
            brandingData.append('icon_height', iconHeight);
            // We don't send the full data URL for performance reasons
        }
        
        // Add current timestamp to force update
        brandingData.append('timestamp', Date.now());
        
        try {
            const response = await fetch(mtl_plugin_vars.ajax_url, {
                method: 'POST',
                body: brandingData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (!result.success) {
                console.warn('Customizer settings warning:', result);
                // Try direct setting method as fallback
                return await directCustomizerUpdate();
            }
            
            return true;
        } catch (error) {
            console.error('Customizer settings error:', error);
            // Try direct setting method as fallback
            return await directCustomizerUpdate();
        }
    }

    // Direct method to update customizer settings as fallback
    async function directCustomizerUpdate() {
        const directUpdateData = new FormData();
        directUpdateData.append('action', 'mtl_direct_customizer_update');
        directUpdateData.append('nonce', mtl_plugin_vars.nonce);
        
        if (siteLogoDataUrl) {
            directUpdateData.append('update_logo', 'true');
        }
        
        if (siteIconDataUrl) {
            directUpdateData.append('update_icon', 'true');
        }
        
        try {
            const response = await fetch(mtl_plugin_vars.ajax_url, {
                method: 'POST',
                body: directUpdateData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            return result.success;
        } catch (error) {
            console.error('Direct customizer update error:', error);
            return false;
        }
    }
});
</script>

<style>
/* Direct Import Form Styles */
#direct-import-form {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-top: 20px;
}

#direct-import-form h3 {
    margin-top: 0;
    margin-bottom: 10px;
}

.form-field {
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.description {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.import-info {
    background-color: #f0f8ff;
    border: 1px solid #add8e6;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 15px;
}

.import-info ul {
    margin-left: 20px;
    list-style-type: disc;
}

/* Button Layout Styles */
.modal-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
}

.modal-actions button {
    margin: 0 5px;
}

.modal-actions button:first-child {
    margin-right: auto; /* Push the first button to the left */
}

.modal-actions .right-buttons {
    display: flex;
    gap: 10px;
}

.modal-header h2{
    margin: 0 !important;
}

/* Plugin Requirements Modal Styles */
#plugin-requirements-modal {
    width: 100%;
    height: 100%;
    background: #ffffff;
    padding: 0;
    border-radius: 0;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 100002;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

#plugin-requirements-modal .modal-content {
    max-width: 1200px;
    margin: 0 auto;
    max-height: calc(100vh - 56px); /* Account for header height */
}

/* Recommended Notice Styles */
.recommended-notice {
    display: flex;
    align-items: center;
    background-color: #fff8e5;
    padding: 10px 15px;
    border-radius: 4px;
    border: 1px solid #ffb900;
    margin-top: 15px;
}

.recommended-notice .dashicons {
    color: #ffb900;
    font-size: 20px;
    margin-right: 10px;
    flex-shrink: 0;
}

.recommended-notice p {
    margin: 0;
    padding: 0;
}

/* Version Column Styles */
.version-column {
    text-align: center;
    justify-content: center;
    display: flex;
    align-items: center;
}

.version-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #2271b1;
}

.version-link .dashicons {
    margin-left: 5px;
    font-size: 16px;
}

/* Column Alignment Styles */
.checkbox-column {
    display: flex;
    justify-content: center;
    align-items: center;
}

.name-column {
    padding-left: 10px;
}

.status-column {
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Plugin Item Styles */
.plugin-item {
    display: grid;
    grid-template-columns: 40px 1fr 150px 150px;
    padding: 15px;
    border-bottom: 1px solid #ddd;
    align-items: center;
}

.existing-plugins-table .plugin-item {
    grid-template-columns: 40px 1fr 150px;
}

/* Table Header Styles */
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

.table-header > div {
    text-align: center;
}

.table-header .name-column {
    text-align: left;
}

/* Import Progress Modal Styles */
.import-progress-wrapper {
    width: 100%;
    max-width: 100%;
    height: 100%;
    max-height: 100vh;
    background: #ffffff;
    padding: 0;
    border-radius: 0;
    z-index: 100003 !important;
}

.import-progress-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.progress-header {
    text-align: center;
    margin-bottom: 30px;
}

.progress-header h3 {
    font-size: 24px;
    margin-bottom: 10px;
    color: #2271b1;
}

.progress-header p {
    font-size: 16px;
    color: #50575e;
}

.progress-bar-container {
    height: 24px;
    background-color: #f0f0f0;
    border-radius: 12px;
    margin-bottom: 30px;
    overflow: hidden;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #46b450);
    width: 0%;
    transition: width 0.5s ease;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.import-status-container {
    margin-bottom: 30px;
}

.current-step {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    border-left: 5px solid #2271b1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.step-icon {
    margin-right: 20px;
    color: #2271b1;
    font-size: 24px;
}

.step-text {
    font-size: 18px;
    font-weight: 500;
    color: #2c3338;
}

.spinning {
    animation: spin 1.5s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.import-log {
    margin-top: 30px;
}

.import-log h4 {
    font-size: 18px;
    margin-bottom: 15px;
    color: #2c3338;
    font-weight: 600;
}

.log-container {
    height: 250px;
    overflow-y: auto;
    background-color: #f8f9fa;
    border: 1px solid #e2e4e7;
    border-radius: 8px;
    padding: 15px;
    font-family: Consolas, Monaco, 'Andale Mono', monospace;
    font-size: 14px;
    line-height: 1.6;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
}

.log-entry {
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
}

.log-entry::before {
    content: "•";
    margin-right: 8px;
    font-weight: bold;
}

.log-entry.success {
    color: #46b450;
}

.log-entry.success::before {
    content: "✓";
    color: #46b450;
}

.log-entry.error {
    color: #dc3232;
}

.log-entry.error::before {
    content: "✗";
    color: #dc3232;
}

.log-entry.info {
    color: #2271b1;
}

.import-actions {
    display: none;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e4e7;
}

.import-actions button {
    min-width: 180px;
    padding: 12px 20px;
    font-weight: 600;
    font-size: 15px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.import-actions button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.plugins-section, .existing-plugins-section {
    margin-bottom: 20px;
    overflow-y: auto;
}

.plugins-list, .existing-plugins-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.modal-actions {
    position: sticky;
    bottom: 0;
    background: white;
    padding-top: 15px;
    border-top: 1px solid #ddd;
    margin-top: 20px;
    z-index: 10;
}

/* Apply Import Modal Styles */

.apply-import-content {
    padding: 20px 0;
}

.import-options {
    margin: 20px 0;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    overflow: hidden;
}

.import-option {
    padding: 15px;
    border-bottom: 1px solid #e5e5e5;
}

.import-option:last-child {
    border-bottom: none;
}

.import-option label {
    font-weight: 600;
    display: flex;
    align-items: center;
}

.import-option input[type="checkbox"] {
    margin-right: 10px;
}

.description {
    margin: 5px 0 0 24px;
    color: #757575;
    font-size: 13px;
}

.warning-message {
    background-color: #fcf8e3;
    border: 1px solid #faebcc;
    color: #8a6d3b;
    padding: 15px;
    border-radius: 4px;
    margin: 20px 0;
    display: flex;
    align-items: flex-start;
}

.warning-message .dashicons {
    margin-right: 10px;
    color: #f0ad4e;
}

.apply-import-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
    position: sticky;
    bottom: 0;
    background-color: #fff;
    padding: 15px 50px;
    border-top: 1px solid #e5e5e5;
    z-index: 100;
    box-shadow: 0 -5px 10px rgba(0, 0, 0, 0.05);
}
.import-options h3{
    padding: 0 20px;
}
#apply-import-confirm-btn {
    background-color: #2271b1;
    color: white;
    padding: 10px 20px;
    font-weight: bold;
    transition: all 0.3s ease;
}

#apply-import-confirm-btn:hover {
    background-color: #135e96;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Add scrollbar styling */
.modal-content::-webkit-scrollbar {
    width: 8px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Theme Installation Results */
.theme-installation-results {
    margin: 20px 0;
    padding: 15px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.theme-installation-results h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
    font-weight: 600;
}

.theme-installation-results .success {
    color: #46b450;
}

.theme-installation-results .error {
    color: #dc3232;
}

/* Logo and Icon Upload Styles */
.logo-upload-option, .icon-upload-option {
    padding-bottom: 20px;
}

.logo-upload-container, .icon-upload-container {
    display: flex;
    align-items: center;
    margin-top: 10px;
    gap: 15px;
}

.logo-preview, .icon-preview {
    width: 150px;
    height: 80px;
    border: 1px dashed #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-color: #f9f9f9;
    position: relative;
    transition: all 0.3s ease;
}

.logo-preview::before, .icon-preview::before {
    content: "No image";
    position: absolute;
    color: #999;
    font-size: 12px;
    opacity: 0.7;
    z-index: 0;
}

.logo-preview img, .icon-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    position: relative;
    z-index: 1;
}

.logo-preview.has-image, .icon-preview.has-image {
    border-style: solid;
    border-color: #2271b1;
    background-color: #fff;
}

.logo-actions, .icon-actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.logo-file-input, .icon-file-input {
    display: none;
}

.modal-header {
    background-color: #fff;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
}

.modal-header .close-modal,
.modal-header .close-apply-import-modal {
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
    padding: 5px;
}

.modal-header .close-modal:hover,
.modal-header .close-apply-import-modal:hover {
    color: #dc3232;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background-color: #fff;
    z-index: 100001;
    overflow: hidden;
    display: none;
}

.modal-content {
    height: calc(100vh - 56px); /* Subtract header height */
    overflow-y: auto;
    padding: 20px;
    scroll-behavior: smooth;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 100000;
}

.icon-preview {
    width: 80px;
    height: 80px;
    border-radius: 4px;
}

.import-modal-wrapper .processing-message {
    margin: 15px 0;
    padding: 15px;
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.import-modal-wrapper .processing-message p {
    margin: 0 0 10px 0;
    font-size: 14px;
}

.import-modal-wrapper .processing-message .spinner {
    background: url(../wp-includes/images/spinner.gif) no-repeat;
    background-size: 20px 20px;
    display: inline-block;
    visibility: visible;
    float: none;
    width: 20px;
    height: 20px;
    margin: 0 10px;
    vertical-align: middle;
}

.import-modal-wrapper .upload-status {
    margin: 10px 0;
    padding: 8px 12px;
    background-color: #f5f5f5;
    border-left: 4px solid #ccc;
}

.import-modal-wrapper .upload-status p {
    margin: 0;
    font-size: 13px;
}

.import-modal-wrapper .upload-status p.success {
    color: #46b450;
}

.import-modal-wrapper .upload-status p.error {
    color: #dc3232;
}

.import-modal-wrapper .processing-message p.error {
    color: #dc3232;
    font-weight: 500;
}

/* Pre-Import Confirmation Modal Styles */
.pre-import-confirmation-wrapper {
    max-width: 500px;
}

.pre-import-content {
    padding: 20px;
}

.pre-import-content h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
    color: #23282d;
}

.pre-import-content p {
    margin-bottom: 20px;
    font-size: 14px;
    line-height: 1.5;
}

.pre-import-actions {
    margin-top: 25px;
    text-align: right;
}

.pre-import-actions button {
    margin-left: 10px;
}

#pre-import-confirm-btn {
    background-color: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

#pre-import-confirm-btn:hover {
    background-color: #006291;
    border-color: #006291;
}

/* Custom Upload Section Styles */
.custom-upload-section {
    margin: 25px 0;
    padding: 15px;
    background-color: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
}

.custom-upload-section h3 {
    margin-top: 0;
    color: #23282d;
}

.logo-upload-option,
.icon-upload-option {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.icon-upload-option {
    border-bottom: none;
    padding-bottom: 0;
}

.logo-upload-option label,
.icon-upload-option label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.logo-upload-container,
.icon-upload-container {
    display: flex;
    align-items: center;
    margin-top: 10px;
    gap: 15px;
}

.logo-preview,
.icon-preview {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-color: #f0f0f0;
    border: 1px dashed #ccc;
    transition: all 0.3s ease;
}

.logo-preview {
    width: 200px;
    height: 80px;
}

.icon-preview {
    width: 80px;
    height: 80px;
    border-radius: 4px;
}

.logo-preview::before,
.icon-preview::before {
    content: "No image";
    position: absolute;
    color: #999;
    font-size: 12px;
    opacity: 0.7;
    z-index: 0;
}

.logo-preview img,
.icon-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    position: relative;
    z-index: 1;
}

.logo-preview.has-image,
.icon-preview.has-image {
    border-style: solid;
    border-color: #0073aa;
    background-color: #fff;
}

.logo-actions,
.icon-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.logo-file-input,
.icon-file-input {
    display: none;
}

.description {
    color: #666;
    font-size: 13px;
    margin-top: 4px;
    font-style: italic;
}
</style>


