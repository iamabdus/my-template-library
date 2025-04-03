<?php
/**
 * Elementor Kit Handler
 *
 * Handles the template kit import functionality for Elementor.
 * This file is required by the main import modal to process kit files.
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}

/**
 * Class for handling Elementor kit imports
 */
class MTL_Elementor_Kit_Handler {
    /**
     * Initialize the handler with hooks
     */
    public static function init() {
        // Hook into WordPress and Elementor actions
        add_action('wp_ajax_mtl_prepare_template_import', array(__CLASS__, 'prepare_template_import'));
        add_action('wp_ajax_mtl_process_template_import', array(__CLASS__, 'process_template_import'));
        add_action('wp_ajax_mtl_validate_template_kit', array(__CLASS__, 'validate_template_kit'));
        add_action('wp_ajax_mtl_import_elementor_kit', array(__CLASS__, 'ajax_import_elementor_kit'));

        // Add filter to modify Elementor's template import process if needed
        add_filter('elementor/template-library/import_template', array(__CLASS__, 'maybe_handle_template_import'), 10, 1);

        // Hook into Elementor kit import process
        add_filter('elementor/import/stage_1/result', array(__CLASS__, 'handle_kit_import'), 10, 3);
    }

    /**
     * Prepare template import by validating the file (basic checks)
     */
    public static function prepare_template_import() {
        // Verify nonce
        if (!isset($_POST['_nonce']) || !wp_verify_nonce($_POST['_nonce'], 'mtl_template_import_nonce')) {
            wp_send_json_error(array('message' => 'Error: Invalid security token.'));
        }

        if (!isset($_POST['template_id']) || empty($_POST['template_id'])) {
            wp_send_json_error(array('message' => 'Error: No template ID provided.'));
        }

        $template_id = sanitize_text_field($_POST['template_id']);

        // Return success to allow the import process to continue
        wp_send_json_success(array(
            'template_id' => $template_id,
            'message' => 'Template is ready for import.'
        ));
    }

    /**
     * Process the template import
     */
    public static function process_template_import() {
        // Verify nonce
        if (!isset($_POST['_nonce']) || !wp_verify_nonce($_POST['_nonce'], 'mtl_template_import_nonce')) {
            wp_send_json_error(array('message' => 'Error: Invalid security token.'));
        }

        if (!isset($_POST['template_id']) || empty($_POST['template_id'])) {
            wp_send_json_error(array('message' => 'Error: No template ID provided.'));
        }

        $template_id = sanitize_text_field($_POST['template_id']);
        $file = null;
        $temp_file = null;

        try {
            // Check if we have uploaded files
            if (!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])) {
                // If no file is uploaded, try to get kit URL from POST data
                if (!isset($_POST['kit_url']) || empty($_POST['kit_url'])) {
                    throw new Exception('Error: No template kit file or URL provided.');
                }

                $kit_url = esc_url_raw($_POST['kit_url']);

                // Try to download the file
                $temp_file = download_url($kit_url);

                if (is_wp_error($temp_file)) {
                    throw new Exception('Error: Failed to download template kit. ' . $temp_file->get_error_message());
                }

                // Create file array similar to $_FILES
                $file = array(
                    'name' => basename($kit_url),
                    'tmp_name' => $temp_file,
                    'error' => 0,
                    'size' => filesize($temp_file)
                );
            } else {
                $file = $_FILES['file'];
            }

            if (!$file) {
                throw new Exception("Error: Unable to retrieve file for import.");
            }

            // Process the file through Elementor's importer
            if (!class_exists('\Elementor\Plugin')) {
                throw new Exception('Error: Elementor is not active or installed.');
            }

            // Check for Elementor 3.0+ with Kit import functionality
            if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.0.0', '>=')) {
                // Import using the kit import functionality
                self::import_elementor_kit($file, $template_id);
            } else {
                // Fallback to regular template import for older Elementor versions
                $importer = \Elementor\Plugin::$instance->templates_manager->get_import_handler();

                if (!$importer) {
                    throw new Exception('Error: Elementor import handler not available.');
                }

                // Import the template
                $result = $importer->import_template($file);

                if (is_wp_error($result)) {
                    throw new Exception('Error: Elementor import failed. ' . $result->get_error_message());
                }

                wp_send_json_success(array(
                    'template_id' => $template_id,
                    'import_result' => $result,
                    'message' => 'Template imported successfully (older Elementor version).'
                ));
            }

        } catch (Exception $e) {
            // Delete temp file if we downloaded it
            if (isset($temp_file) && file_exists($temp_file)) {
                @unlink($temp_file);
            }

            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Import Elementor Kit (for Elementor 3.0+)
     */
    public static function import_elementor_kit($file, $template_id) {
        // Import using Elementor Kit import functionality
        if (!class_exists('\Elementor\App\Modules\ImportExport\Module')) {
            throw new Exception('Error: Elementor Kit import functionality not available. Ensure Elementor Pro is installed and activated.');
        }

        // Make sure we have the required hooks
        if (!has_action('elementor/import/run_import_process') && method_exists('\Elementor\App\Modules\ImportExport\Processes\Import', 'run_import_process')) {
            // Create a Hook for the import process
            add_action('elementor/import/run_import_process', array('\Elementor\App\Modules\ImportExport\Processes\Import', 'run_import_process'), 10, 3);
        }

        // Get the upload directory
        $upload_dir = wp_upload_dir();
        $kit_filename = 'elementor-kit-' . $template_id . '.zip';
        $kit_filepath = $upload_dir['path'] . '/' . $kit_filename;

        try {
            // Copy uploaded file to the uploads directory
            if (!file_exists(dirname($kit_filepath))) {
                wp_mkdir_p(dirname($kit_filepath));
            }

            // Move temp file to uploads directory
            $moved = copy($file['tmp_name'], $kit_filepath);
            if (!$moved) {
                throw new Exception("Error: Failed to move uploaded file to " . $kit_filepath);
            }

            // Set up import session
            set_transient('elementor_import_kit_' . $template_id, [
                'file_path' => $kit_filepath,
                'kit_id' => $template_id,
                'session' => [
                    'kit_title' => 'Imported Kit',
                    'include' => [
                        'templates' => true,
                        'content' => true,
                        'site_settings' => true,
                        'plugins' => true,
                    ],
                    'selected_plugins' => [],
                    'manifest' => [],
                    'extracted_directory_path' => '',
                    'current_stage' => 1,
                    'import_settings' => [
                        'include_content' => true,
                        'include_customizer' => true,
                        'include_plugins' => true,
                    ],
                ],
            ], HOUR_IN_SECONDS);

            // Attempt to use Elementor's import modules directly
            try {
                $import_module = new \Elementor\App\Modules\ImportExport\Module();
                $import_module->run_import($kit_filepath, [
                    'include' => [
                        'templates' => true,
                        'content' => true,
                        'site_settings' => true,
                    ],
                ]);

                wp_send_json_success([
                    'template_id' => $template_id,
                    'message' => 'Kit imported successfully!'
                ]);
            } catch (Exception $e) {
                // Fallback to using WordPress hooks
                do_action('elementor/import/run_import_process', $kit_filepath, $template_id, [
                    'include' => [
                        'templates' => true,
                        'content' => true,
                        'site_settings' => true,
                    ],
                ]);

                wp_send_json_success([
                    'template_id' => $template_id,
                    'message' => 'Import process initiated. The page will reload shortly to apply changes.'
                ]);
            }
        } catch (Exception $e) {
            // Handle any exceptions that might occur
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle Elementor kit import process
     */
    public static function handle_kit_import($result, $import_settings, $imported_data) {
        // This hook allows us to monitor and modify Elementor's kit import process
        // Log import data for debugging if needed
        return $result;
    }

    /**
     * Validate a template kit file
     */
    public static function validate_template_kit() {
        // Verify nonce
        if (!isset($_POST['_nonce']) || !wp_verify_nonce($_POST['_nonce'], 'mtl_template_import_nonce')) {
            wp_send_json_error(array('message' => 'Error: Invalid security token.'));
        }

        if (!isset($_POST['kit_url']) || empty($_POST['kit_url'])) {
            wp_send_json_error(array('message' => 'Error: No template kit URL provided.'));
        }

        $kit_url = esc_url_raw($_POST['kit_url']);

        // Simply validate the URL format
        if (filter_var($kit_url, FILTER_VALIDATE_URL) === false) {
            wp_send_json_error(array('message' => 'Error: Invalid URL format.'));
        }

        // Return success to allow the import process to continue
        wp_send_json_success(array(
            'kit_url' => $kit_url,
            'message' => 'Template kit URL is valid.'
        ));
    }

    /**
     * Maybe modify Elementor's template import process
     */
    public static function maybe_handle_template_import($template) {
        // Here you can modify the imported template data if needed
        return $template;
    }

    /**
     * Helper to extract a zip file
     */
    public static function extract_zip($zip_file, $extract_to) {
        global $wp_filesystem;

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        WP_Filesystem();

        $unzipped = unzip_file($zip_file, $extract_to);

        if (is_wp_error($unzipped)) {
            return $unzipped;
        }

        return true;
    }

    /**
     * Helper function to recursively delete a directory
     */
    public static function recursive_rmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        self::recursive_rmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * AJAX handler for importing Elementor kits
     */
    public static function ajax_import_elementor_kit() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mtl_template_import_nonce')) {
            wp_send_json_error('Security check failed.');
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('You do not have permission to import templates.');
            return;
        }

        // Check if file was uploaded
        if (empty($_FILES['kit_file'])) {
            wp_send_json_error('No file was uploaded.');
            return;
        }

        $file = $_FILES['kit_file'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_message = self::get_upload_error_message($file['error']);
            wp_send_json_error('Upload error: ' . $error_message);
            return;
        }

        // Validate file type
        $file_type = wp_check_filetype(basename($file['name']), array('zip' => 'application/zip'));
        if (empty($file_type['ext'])) {
            wp_send_json_error('Invalid file type. Only ZIP files are allowed.');
            return;
        }

        // Create a temporary directory
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/mtl_temp';
        $session_id = 'import_' . time() . '_' . mt_rand(1000, 9999);
        $session_dir = $temp_dir . '/' . $session_id;
        
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        wp_mkdir_p($session_dir);

        // Move the uploaded file to the temporary directory
        $zip_file = $session_dir . '/elementor-kit.zip';
        if (!move_uploaded_file($file['tmp_name'], $zip_file)) {
            wp_send_json_error('Failed to move uploaded file.');
            return;
        }

        try {
            // Extract the ZIP file
            $extracted = self::extract_zip($zip_file, $session_dir);
            if (!$extracted) {
                throw new Exception('Failed to extract ZIP file.');
            }

            // Check if this is an Elementor kit
            if (!self::is_elementor_kit($session_dir)) {
                throw new Exception('The uploaded file is not a valid Elementor kit.');
            }

            // Import the kit
            $imported_items = self::import_kit_contents($session_dir);

            // Clean up
            self::recursive_rmdir($session_dir);

            wp_send_json_success(array(
                'message' => 'Elementor kit imported successfully!',
                'imported_items' => $imported_items
            ));

        } catch (Exception $e) {
            // Clean up
            if (file_exists($session_dir)) {
                self::recursive_rmdir($session_dir);
            }

            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Check if the extracted directory contains an Elementor kit
     */
    private static function is_elementor_kit($dir) {
        // Check for manifest.json which is typically in Elementor kits
        if (file_exists($dir . '/manifest.json')) {
            return true;
        }

        // Check for site-settings.json which is typically in Elementor kits
        if (file_exists($dir . '/site-settings.json')) {
            return true;
        }

        // Check for content directory structure
        if (is_dir($dir . '/content') && 
            (is_dir($dir . '/content/page') || 
             is_dir($dir . '/content/post') || 
             is_dir($dir . '/content/templates'))) {
            return true;
        }

        return false;
    }

    /**
     * Import all contents from the Elementor kit
     */
    private static function import_kit_contents($dir) {
        $imported_items = array();

        // Import site settings if available
        if (file_exists($dir . '/site-settings.json')) {
            $site_settings = json_decode(file_get_contents($dir . '/site-settings.json'), true);
            if ($site_settings) {
                self::import_site_settings($site_settings);
                $imported_items[] = 'Site Settings';
            }
        }

        // Import templates
        $templates_imported = self::import_templates_from_dir($dir);
        if (!empty($templates_imported)) {
            $imported_items = array_merge($imported_items, $templates_imported);
        }

        // Import pages
        $pages_imported = self::import_pages_from_dir($dir);
        if (!empty($pages_imported)) {
            $imported_items = array_merge($imported_items, $pages_imported);
        }

        // Import posts
        $posts_imported = self::import_posts_from_dir($dir);
        if (!empty($posts_imported)) {
            $imported_items = array_merge($imported_items, $posts_imported);
        }

        // Import custom post types
        $cpt_imported = self::import_custom_post_types_from_dir($dir);
        if (!empty($cpt_imported)) {
            $imported_items = array_merge($imported_items, $cpt_imported);
        }

        return $imported_items;
    }

    /**
     * Import site settings from the kit
     */
    private static function import_site_settings($settings) {
        if (empty($settings)) {
            return false;
        }

        // Check if Elementor is active
        if (!did_action('elementor/loaded')) {
            return false;
        }

        // Get the active kit ID
        $kit_id = \Elementor\Plugin::$instance->kits_manager->get_active_id();
        if (!$kit_id) {
            // Create a new kit if none exists
            $kit = \Elementor\Plugin::$instance->kits_manager->create();
            $kit_id = $kit->get_id();
        }

        // Update the kit with the new settings
        $kit_document = \Elementor\Plugin::$instance->documents->get($kit_id);
        if (!$kit_document) {
            return false;
        }

        $kit_document->update_settings($settings);

        return true;
    }

    /**
     * Import templates from the kit directory
     */
    private static function import_templates_from_dir($dir) {
        $imported = array();
        $templates_dir = $dir . '/content/templates';
        
        if (!is_dir($templates_dir)) {
            return $imported;
        }

        $template_files = glob($templates_dir . '/*.json');
        foreach ($template_files as $file) {
            $template_data = json_decode(file_get_contents($file), true);
            if (!$template_data) {
                continue;
            }

            $template_id = self::import_template($template_data);
            if ($template_id) {
                $title = isset($template_data['title']) ? $template_data['title'] : basename($file, '.json');
                $imported[] = "Template: {$title}";
            }
        }

        return $imported;
    }

    /**
     * Import pages from the kit directory
     */
    private static function import_pages_from_dir($dir) {
        $imported = array();
        $pages_dir = $dir . '/content/page';
        
        if (!is_dir($pages_dir)) {
            return $imported;
        }

        $page_files = glob($pages_dir . '/*.json');
        foreach ($page_files as $file) {
            $page_data = json_decode(file_get_contents($file), true);
            if (!$page_data) {
                continue;
            }

            $page_id = mtl_create_page_from_template($page_data);
            if ($page_id && !is_wp_error($page_id)) {
                $title = isset($page_data['title']) ? $page_data['title'] : basename($file, '.json');
                $imported[] = "Page: {$title}";
            }
        }

        return $imported;
    }

    /**
     * Import posts from the kit directory
     */
    private static function import_posts_from_dir($dir) {
        $imported = array();
        $posts_dir = $dir . '/content/post';
        
        if (!is_dir($posts_dir)) {
            return $imported;
        }

        $post_files = glob($posts_dir . '/*.json');
        foreach ($post_files as $file) {
            $post_data = json_decode(file_get_contents($file), true);
            if (!$post_data) {
                continue;
            }

            $post_id = self::create_post_from_template($post_data, 'post');
            if ($post_id && !is_wp_error($post_id)) {
                $title = isset($post_data['title']) ? $post_data['title'] : basename($file, '.json');
                $imported[] = "Post: {$title}";
            }
        }

        return $imported;
    }

    /**
     * Import custom post types from the kit directory
     */
    private static function import_custom_post_types_from_dir($dir) {
        $imported = array();
        $content_dir = $dir . '/content';
        
        if (!is_dir($content_dir)) {
            return $imported;
        }

        // Get all directories in the content directory except 'page', 'post', and 'templates'
        $cpt_dirs = array_filter(glob($content_dir . '/*', GLOB_ONLYDIR), function($dir) {
            $basename = basename($dir);
            return !in_array($basename, array('page', 'post', 'templates'));
        });

        foreach ($cpt_dirs as $cpt_dir) {
            $post_type = basename($cpt_dir);
            
            // Check if the post type exists
            if (!post_type_exists($post_type)) {
                continue;
            }

            $cpt_files = glob($cpt_dir . '/*.json');
            foreach ($cpt_files as $file) {
                $cpt_data = json_decode(file_get_contents($file), true);
                if (!$cpt_data) {
                    continue;
                }

                $cpt_id = self::create_post_from_template($cpt_data, $post_type);
                if ($cpt_id && !is_wp_error($cpt_id)) {
                    $title = isset($cpt_data['title']) ? $cpt_data['title'] : basename($file, '.json');
                    $imported[] = ucfirst($post_type) . ": {$title}";
                }
            }
        }

        return $imported;
    }

    /**
     * Create a post from template data
     */
    private static function create_post_from_template($template_data, $post_type = 'post') {
        // Check if the template data is valid
        if (empty($template_data) || !is_array($template_data)) {
            return new WP_Error('invalid_data', 'Invalid template data.');
        }
        
        // Get the title from the template data or use a default title
        $title = isset($template_data['title']) ? sanitize_text_field($template_data['title']) : 'Imported ' . ucfirst($post_type) . ' ' . date('Y-m-d H:i:s');
        
        // Create the post
        $post_args = array(
            'post_title'    => $title,
            'post_status'   => 'publish',
            'post_type'     => $post_type,
            'post_content'  => '',
        );
        
        // Add excerpt if available
        if (isset($template_data['excerpt'])) {
            $post_args['post_excerpt'] = sanitize_text_field($template_data['excerpt']);
        }
        
        // Insert the post
        $post_id = wp_insert_post($post_args);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Save the template data as post meta
        update_post_meta($post_id, '_elementor_data', wp_slash(json_encode($template_data['content'])));
        update_post_meta($post_id, '_elementor_edit_mode', 'builder');
        update_post_meta($post_id, '_elementor_template_type', $post_type);
        update_post_meta($post_id, '_elementor_version', '3.6.0'); // Use appropriate version
        
        // If there are page settings, save them
        if (isset($template_data['page_settings'])) {
            update_post_meta($post_id, '_elementor_page_settings', $template_data['page_settings']);
        }
        
        // Set featured image if available
        if (isset($template_data['featured_image'])) {
            // TODO: Handle featured image import
        }
        
        // Set categories if available
        if (isset($template_data['categories']) && is_array($template_data['categories'])) {
            $taxonomy = $post_type === 'post' ? 'category' : $post_type . '_category';
            if (taxonomy_exists($taxonomy)) {
                $term_ids = array();
                foreach ($template_data['categories'] as $category) {
                    $term = term_exists($category, $taxonomy);
                    if (!$term) {
                        $term = wp_insert_term($category, $taxonomy);
                    }
                    if (!is_wp_error($term)) {
                        $term_ids[] = $term['term_id'];
                    }
                }
                if (!empty($term_ids)) {
                    wp_set_object_terms($post_id, $term_ids, $taxonomy);
                }
            }
        }
        
        // Set tags if available
        if (isset($template_data['tags']) && is_array($template_data['tags'])) {
            $taxonomy = $post_type === 'post' ? 'post_tag' : $post_type . '_tag';
            if (taxonomy_exists($taxonomy)) {
                $term_ids = array();
                foreach ($template_data['tags'] as $tag) {
                    $term = term_exists($tag, $taxonomy);
                    if (!$term) {
                        $term = wp_insert_term($tag, $taxonomy);
                    }
                    if (!is_wp_error($term)) {
                        $term_ids[] = $term['term_id'];
                    }
                }
                if (!empty($term_ids)) {
                    wp_set_object_terms($post_id, $term_ids, $taxonomy, true);
                }
            }
        }
        
        return $post_id;
    }

    /**
     * Import a template
     */
    private static function import_template($template_data) {
        // Check if Elementor is active
        if (!did_action('elementor/loaded')) {
            return false;
        }

        // Create the template
        $template_id = \Elementor\Plugin::$instance->templates_manager->save_template([
            'source' => 'local',
            'type' => isset($template_data['type']) ? $template_data['type'] : 'page',
            'title' => isset($template_data['title']) ? $template_data['title'] : 'Imported Template',
            'content' => isset($template_data['content']) ? $template_data['content'] : [],
            'page_settings' => isset($template_data['page_settings']) ? $template_data['page_settings'] : [],
        ]);

        return $template_id;
    }

    /**
     * Get upload error message
     */
    private static function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown upload error.';
        }
    }
}

// Initialize the handler
MTL_Elementor_Kit_Handler::init();

/**
 * Get template kit details from a file
 */
function mtl_get_kit_details($file_path) {
    // Default values
    $kit_data = array(
        'title' => 'Unknown Template',
        'description' => '',
        'author' => '',
        'version' => '1.0.0',
        'elementor_version' => '',
        'templates' => array(),
        'has_content' => false,
        'has_settings' => false
    );

    // Extract the ZIP to a temp folder to read the manifest
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/mtl-temp-' . uniqid();

    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }

    $result = MTL_Elementor_Kit_Handler::extract_zip($file_path, $temp_dir);

    if (is_wp_error($result)) {
        return $kit_data;
    }

    // Look for manifest.json
    if (file_exists($temp_dir . '/manifest.json')) {
        $manifest = json_decode(file_get_contents($temp_dir . '/manifest.json'), true);

        if (is_array($manifest)) {
            if (isset($manifest['title'])) {
                $kit_data['title'] = sanitize_text_field($manifest['title']);
            }

            if (isset($manifest['description'])) {
                $kit_data['description'] = wp_kses_post($manifest['description']);
            }

            if (isset($manifest['author'])) {
                $kit_data['author'] = sanitize_text_field($manifest['author']);
            }

            if (isset($manifest['version'])) {
                $kit_data['version'] = sanitize_text_field($manifest['version']);
            }

            if (isset($manifest['elementor_version'])) {
                $kit_data['elementor_version'] = sanitize_text_field($manifest['elementor_version']);
            }

            if (isset($manifest['templates']) && is_array($manifest['templates'])) {
                $kit_data['templates'] = $manifest['templates'];
            }

            if (isset($manifest['content']) && $manifest['content']) {
                $kit_data['has_content'] = true;
            }

            if (isset($manifest['settings']) && $manifest['settings']) {
                $kit_data['has_settings'] = true;
            }
        }
    }

    // Clean up temp directory
    if (file_exists($temp_dir)) {
        MTL_Elementor_Kit_Handler::recursive_rmdir($temp_dir);
    }

    return $kit_data;
}