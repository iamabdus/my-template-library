<?php
if (!defined('WPINC')) {
    die;
}

// Define plugin directory if not already defined
if (!defined('MTL_PLUGIN_DIR')) {
    define('MTL_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Required WordPress files
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

class Template_Kit_Plugin_Manager {
    private $upgrader;
    private $skin;
    private $errors = [];
    private $log = [];

    public function __construct() {
        $this->skin = new WP_Ajax_Upgrader_Skin();
        $this->upgrader = new Plugin_Upgrader($this->skin);
    }

    /**
     * Install and activate multiple plugins in batch
     */
    public function batch_install_plugins($plugins, $batch_size = 5) {
        $results = [];
        $batches = array_chunk($plugins, $batch_size);

        foreach ($batches as $batch) {
            foreach ($batch as $plugin) {
                $results[] = $this->install_and_activate_plugin($plugin);
            }
        }

        return $results;
    }

    /**
     * Main installation method
     */
    public function install_and_activate_plugin($plugin_data) {
        $this->log_message("Starting installation process for {$plugin_data['name']}");

        try {
            // Validate plugin data
            $this->validate_plugin_data($plugin_data);

            // Check if plugin is already installed but inactive
            if (file_exists(WP_PLUGIN_DIR . '/' . dirname($plugin_data['path']))) {
                return $this->activate_plugin($plugin_data);
            }

            // Check if plugin is available on WordPress.org
            $wp_org_download = $this->get_wordpress_plugin_download_url($plugin_data['slug']);
            
            if ($wp_org_download) {
                // If available on WordPress.org, treat as free plugin
                $plugin_data['source'] = $wp_org_download;
                $plugin_data['type'] = 'free';
                $this->log_message("Plugin found on WordPress.org, using official source");
                return $this->handle_free_plugin($plugin_data);
            } else {
                // If not available on WordPress.org, use provided source
                $plugin_data['type'] = 'premium';
                return $this->handle_premium_plugin($plugin_data);
            }

        } catch (Exception $e) {
            $this->log_message("Error: " . $e->getMessage());
            return $this->error_response($e->getMessage());
        }
    }

    /**
     * Handle free plugin installation
     */
    private function handle_free_plugin($plugin_data) {
        if (!isset($plugin_data['source']) || empty($plugin_data['source'])) {
            throw new Exception('No valid download URL found for the plugin');
        }

        // Install the plugin
        $this->log_message("Installing free plugin from: " . $plugin_data['source']);
        $installed = $this->upgrader->install($plugin_data['source']);
        
        if (is_wp_error($installed)) {
            throw new Exception($installed->get_error_message());
        }

        // Get the destination folder from the upgrader
        $plugin_folder = $this->upgrader->plugin_info();
        if ($plugin_folder) {
            $plugin_data['path'] = $plugin_folder;
        }

        return $this->activate_plugin($plugin_data);
    }

    /**
     * Handle premium plugin installation
     */
    private function handle_premium_plugin($plugin_data) {
        if (!isset($plugin_data['source']) || empty($plugin_data['source'])) {
            throw new Exception('Premium plugin source URL is required');
        }

        $this->log_message("Installing premium plugin from: " . $plugin_data['source']);
        
        $installed = $this->upgrader->install($plugin_data['source']);
        
        if (is_wp_error($installed)) {
            throw new Exception('Installation failed: ' . $installed->get_error_message());
        }

        if (!$installed) {
            throw new Exception('Installation failed: Unknown error occurred');
        }

        // Get the destination folder from the upgrader
        $plugin_folder = $this->upgrader->plugin_info();
        if (!$plugin_folder) {
            throw new Exception('Could not determine plugin folder after installation');
        }

        $plugin_data['path'] = $plugin_folder;

        return $this->activate_plugin($plugin_data);
    }

    /**
     * Activate plugin
     */
    private function activate_plugin($plugin_data) {
        // Get the correct plugin path
        $plugin_path = $plugin_data['path'];
        if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_path)) {
            // Try to find the plugin file
            $plugin_dir = dirname($plugin_path);
            $plugin_files = glob(WP_PLUGIN_DIR . '/' . $plugin_dir . '/*.php');
            
            if (empty($plugin_files)) {
                throw new Exception('Plugin files not found after installation');
            }
            
            // Use the first PHP file found
            $plugin_path = str_replace(WP_PLUGIN_DIR . '/', '', $plugin_files[0]);
        }

        if (is_plugin_active($plugin_path)) {
            return $this->success_response('Plugin is already active', 'active');
        }

        $result = activate_plugin($plugin_path);
        
        if (is_wp_error($result)) {
            throw new Exception('Activation failed: ' . $result->get_error_message());
        }

        $this->log_message("Successfully activated plugin: " . $plugin_data['name']);
        return $this->success_response('Plugin activated successfully', 'active');
    }

    /**
     * Get WordPress.org plugin download URL
     */
    private function get_wordpress_plugin_download_url($slug) {
        $api = plugins_api('plugin_information', [
            'slug' => $slug,
            'fields' => [
                'short_description' => false,
                'sections' => false,
                'requires' => false,
                'rating' => false,
                'ratings' => false,
                'downloaded' => false,
                'last_updated' => false,
                'added' => false,
                'tags' => false,
                'compatibility' => false,
                'homepage' => false,
                'donate_link' => false,
            ],
        ]);

        if (is_wp_error($api)) {
            return false;
        }

        return $api->download_link;
    }

    /**
     * Validate plugin data
     */
    private function validate_plugin_data($plugin_data) {
        $required_fields = ['name', 'slug', 'path'];
        foreach ($required_fields as $field) {
            if (!isset($plugin_data[$field]) || empty($plugin_data[$field])) {
                throw new Exception("Missing required plugin field: {$field}");
            }
        }

        // Source is required if not a WordPress.org plugin
        if (!isset($plugin_data['source']) || empty($plugin_data['source'])) {
            $wp_org_download = $this->get_wordpress_plugin_download_url($plugin_data['slug']);
            if (!$wp_org_download) {
                throw new Exception('Plugin source URL is required for non-WordPress.org plugins');
            }
        }
    }

    /**
     * Success response
     */
    private function success_response($message, $status = 'success') {
        return [
            'success' => true,
            'status' => $status,
            'message' => $message
        ];
    }

    /**
     * Error response
     */
    private function error_response($message) {
        return [
            'success' => false,
            'status' => 'error',
            'message' => $message
        ];
    }

    /**
     * Log message
     */
    private function log_message($message) {
        $this->log[] = date('Y-m-d H:i:s') . ' - ' . $message;
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Template Kit Plugin Manager: ' . $message);
        }
    }

    /**
     * Get installation logs
     */
    public function get_logs() {
        return $this->log;
    }
}

// Ajax handlers
add_action('wp_ajax_mtl_install_and_activate_plugin', 'mtl_install_and_activate_plugin');
function mtl_install_and_activate_plugin() {
    // Verify nonce
    if (!check_ajax_referer('mtl_plugin_installation_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // Check user capabilities
    if (!current_user_can('install_plugins')) {
        wp_send_json_error(['message' => 'You do not have permission to install plugins']);
    }

    // Get plugin data
    $plugin_data = isset($_POST['plugin']) ? $_POST['plugin'] : null;
    if (!$plugin_data) {
        wp_send_json_error(['message' => 'No plugin data provided']);
    }

    // Sanitize plugin data
    $plugin_data = array_map('sanitize_text_field', $plugin_data);

    // Initialize plugin manager
    $plugin_manager = new Template_Kit_Plugin_Manager();

    // Install and activate plugin
    $result = $plugin_manager->install_and_activate_plugin($plugin_data);

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

// Add status check handler
add_action('wp_ajax_mtl_check_plugin_status', 'mtl_check_plugin_status');
function mtl_check_plugin_status() {
    // Verify nonce
    if (!check_ajax_referer('mtl_plugin_installation_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // Check user capabilities
    if (!current_user_can('install_plugins')) {
        wp_send_json_error(['message' => 'You do not have permission to check plugin status']);
    }

    // Get plugins data from request
    $plugins = isset($_POST['plugins']) ? $_POST['plugins'] : [];
    
    if (empty($plugins)) {
        wp_send_json_error(['message' => 'No plugins provided']);
    }

    $statuses = [];
    
    foreach ($plugins as $plugin) {
        $slug = sanitize_text_field($plugin['slug']);
        $path = sanitize_text_field($plugin['path']);
        
        // Check if plugin is installed
        $installed = file_exists(WP_PLUGIN_DIR . '/' . dirname($path));
        
        // Check if plugin is active
        $active = is_plugin_active($path);
        
        $statuses[$slug] = [
            'is_installed' => $installed,
            'is_active' => $active
        ];
    }

    wp_send_json_success([
        'statuses' => $statuses
    ]);
}

// Enqueue necessary scripts
add_action('admin_enqueue_scripts', 'mtl_enqueue_plugin_scripts');
function mtl_enqueue_plugin_scripts() {
    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'mtl_plugin_vars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mtl_plugin_installation_nonce'),
    ]);
}