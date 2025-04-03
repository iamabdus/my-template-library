<?php
/**
 * Plugin Name: My Template Library
 * Plugin URI: 
 * Description: A custom template library plugin for WordPress
 * Version: 1.0.0
 * Author: Ratul
 * Author URI: 
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: my-template-library
 */

if (!defined('WPINC')) {
    die;
}

define('MTL_VERSION', '1.0.0');
define('MTL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MTL_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Debug logging function
 */
function mtl_debug_log($message) {
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
    
    // Also log to a plugin-specific log file
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/mtl_logs';
    
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
        
        // Create an index.php file to prevent directory listing
        file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
        
        // Create .htaccess to restrict direct access
        file_put_contents($log_dir . '/.htaccess', 'Deny from all');
    }
    
    $log_file = $log_dir . '/debug-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

/**
 * Increase PHP memory limit for large imports
 */
function mtl_increase_memory_limit() {
    $current_limit = ini_get('memory_limit');
    $current_limit_int = intval($current_limit);
    
    // Convert to bytes for comparison
    if (strpos($current_limit, 'G') !== false) {
        $current_limit_int *= 1024 * 1024 * 1024;
    } elseif (strpos($current_limit, 'M') !== false) {
        $current_limit_int *= 1024 * 1024;
    } elseif (strpos($current_limit, 'K') !== false) {
        $current_limit_int *= 1024;
    }
    
    // If current limit is less than 256M, try to increase it
    if ($current_limit_int < 256 * 1024 * 1024) {
        // Try to set memory limit to 256M
        $result = @ini_set('memory_limit', '256M');
        
        if ($result === false) {
            mtl_debug_log('Failed to increase memory limit from ' . $current_limit . ' to 256M');
        } else {
            mtl_debug_log('Increased memory limit from ' . $current_limit . ' to ' . ini_get('memory_limit'));
        }
    } else {
        mtl_debug_log('Current memory limit is already sufficient: ' . $current_limit);
    }
}

/**
 * Increase max execution time for large imports
 */
function mtl_increase_max_execution_time() {
    $current_time = ini_get('max_execution_time');
    
    // If current time is less than 300 seconds (5 minutes), try to increase it
    if ($current_time < 300 && $current_time != 0) { // 0 means unlimited
        // Try to set max execution time to 300 seconds (5 minutes)
        $result = @set_time_limit(300);
        
        if ($result === false) {
            mtl_debug_log('Failed to increase max execution time from ' . $current_time . ' to 300 seconds');
        } else {
            mtl_debug_log('Increased max execution time from ' . $current_time . ' to ' . ini_get('max_execution_time') . ' seconds');
        }
    } else {
        mtl_debug_log('Current max execution time is already sufficient: ' . ($current_time == 0 ? 'unlimited' : $current_time . ' seconds'));
    }
}

// Include required files
require_once MTL_PLUGIN_DIR . 'includes/class-template-library.php';
require_once MTL_PLUGIN_DIR . 'includes/plugin-activation-handler.php';
// require_once MTL_PLUGIN_DIR . 'includes/elementor-kit-handle.php';

// Initialize the plugin
function mtl_init() {
    return My_Template_Library::get_instance();
}

mtl_init();

// Register AJAX handlers
add_action('wp_ajax_mtl_upload_site_logo', 'mtl_ajax_upload_site_logo');
add_action('wp_ajax_mtl_upload_site_icon', 'mtl_ajax_upload_site_icon');
add_action('wp_ajax_mtl_finalize_branding', 'mtl_ajax_finalize_branding');
add_action('wp_ajax_mtl_direct_customizer_update', 'mtl_ajax_direct_customizer_update');

/**
 * AJAX handler for finalizing branding settings (logo and site icon)
 */
function mtl_ajax_finalize_branding() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mtl_plugin_installation_nonce')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
    }
    
    // Check if user has permission
    if (!current_user_can('edit_theme_options')) {
        wp_send_json_error(['message' => 'You do not have permission to modify theme settings.']);
    }
    
    $has_logo = isset($_POST['has_logo']) && $_POST['has_logo'] === 'true';
    $has_icon = isset($_POST['has_icon']) && $_POST['has_icon'] === 'true';
    $apply_immediately = isset($_POST['apply_immediately']) && $_POST['apply_immediately'] === 'true';
    $logo_width = isset($_POST['logo_width']) ? intval($_POST['logo_width']) : 0;
    $logo_height = isset($_POST['logo_height']) ? intval($_POST['logo_height']) : 0;
    $icon_width = isset($_POST['icon_width']) ? intval($_POST['icon_width']) : 0;
    $icon_height = isset($_POST['icon_height']) ? intval($_POST['icon_height']) : 0;
    
    $success = true;
    $messages = [];
    
    // Handle logo finalization
    if ($has_logo) {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            // Re-apply the logo to ensure it's properly set
            set_theme_mod('custom_logo', $logo_id);
            $messages[] = 'Logo setting finalized.';
            
            // If we have dimensions, update the attachment metadata too
            if ($logo_width > 0 && $logo_height > 0) {
                $metadata = wp_get_attachment_metadata($logo_id);
                if (is_array($metadata)) {
                    $metadata['width'] = $logo_width;
                    $metadata['height'] = $logo_height;
                    wp_update_attachment_metadata($logo_id, $metadata);
                    $messages[] = "Updated logo metadata with dimensions: {$logo_width}x{$logo_height}";
                }
            }
            
            // If immediate application is requested, use more direct methods
            if ($apply_immediately) {
                // Make sure to set the logo for the current active theme
                $active_theme = get_option('stylesheet');
                
                // Get current theme mods
                $theme_mods = get_option('theme_mods_' . $active_theme, []);
                
                // If the theme mods are already an array, update them
                if (is_array($theme_mods)) {
                    $theme_mods['custom_logo'] = $logo_id;
                    update_option('theme_mods_' . $active_theme, $theme_mods);
                } 
                // Otherwise, create a new array with the logo
                else {
                    update_option('theme_mods_' . $active_theme, ['custom_logo' => $logo_id]);
                }
                
                $messages[] = 'Logo immediately applied to active theme.';
                
                // Try direct database update if we have wpdb
                global $wpdb;
                if (isset($wpdb)) {
                    $result = $wpdb->update(
                        $wpdb->options,
                        ['option_value' => maybe_serialize($theme_mods)],
                        ['option_name' => 'theme_mods_' . $active_theme]
                    );
                    
                    if ($result !== false) {
                        $messages[] = 'Logo applied via direct database update.';
                    }
                }
                
                // Try to force WordPress to flush its caches
                wp_cache_flush();
                $messages[] = 'Cache flushed for logo.';
                
                // Use WP_Customize_Manager
                if (class_exists('WP_Customize_Manager')) {
                    try {
                        $wp_customize = new WP_Customize_Manager();
                        $wp_customize->set_post_value('custom_logo', $logo_id);
                        $wp_customize->save_changeset_post(['status' => 'publish']);
                        $messages[] = 'Logo applied via WP_Customize_Manager.';
                    } catch (Exception $e) {
                        $messages[] = 'Error with WP_Customize_Manager: ' . $e->getMessage();
                    }
                }
            }
        } else {
            // Try to get from option as fallback
            $logo_id = get_option('_mtl_user_uploaded_logo_id');
            if ($logo_id) {
                set_theme_mod('custom_logo', $logo_id);
                $messages[] = 'Logo setting restored from backup.';
                
                // If immediate application is requested
                if ($apply_immediately) {
                    // Get current theme
                    $active_theme = get_option('stylesheet');
                    
                    // Get current theme mods
                    $theme_mods = get_option('theme_mods_' . $active_theme, []);
                    
                    // If the theme mods are already an array, update them
                    if (is_array($theme_mods)) {
                        $theme_mods['custom_logo'] = $logo_id;
                        update_option('theme_mods_' . $active_theme, $theme_mods);
                    } 
                    // Otherwise, create a new array with the logo
                    else {
                        update_option('theme_mods_' . $active_theme, ['custom_logo' => $logo_id]);
                    }
                    
                    $messages[] = 'Logo from backup immediately applied to theme mods.';
                    
                    // Try to force WordPress to flush its caches
                    wp_cache_flush();
                    $messages[] = 'Cache flushed for logo from backup.';
                }
            } else {
                $success = false;
                $messages[] = 'Could not finalize logo: No logo ID found.';
            }
        }
    }
    
    // Handle site icon finalization
    if ($has_icon) {
        $icon_id = get_option('site_icon');
        if ($icon_id) {
            // Re-apply the site icon to ensure it's properly set
            update_option('site_icon', $icon_id);
            $messages[] = 'Site icon setting finalized.';
            
            // If we have dimensions, update the attachment metadata too
            if ($icon_width > 0 && $icon_height > 0) {
                $metadata = wp_get_attachment_metadata($icon_id);
                if (is_array($metadata)) {
                    $metadata['width'] = $icon_width;
                    $metadata['height'] = $icon_height;
                    wp_update_attachment_metadata($icon_id, $metadata);
                    $messages[] = "Updated icon metadata with dimensions: {$icon_width}x{$icon_height}";
                }
            }
            
            // If immediate application is requested
            if ($apply_immediately) {
                // Make sure all icon sizes are generated
                if (function_exists('delete_option')) {
                    delete_option('site_icon_meta'); // Force WP to regenerate site icon images
                }
                
                // Try direct database update
                global $wpdb;
                if (isset($wpdb)) {
                    $result = $wpdb->update(
                        $wpdb->options,
                        ['option_value' => $icon_id],
                        ['option_name' => 'site_icon']
                    );
                    
                    if ($result !== false) {
                        $messages[] = 'Site icon applied via direct database update.';
                    }
                }
                
                // Try to force WordPress to flush its caches
                wp_cache_flush();
                $messages[] = 'Cache flushed for site icon.';
                
                // Use WP_Customize_Manager
                if (class_exists('WP_Customize_Manager')) {
                    try {
                        $wp_customize = new WP_Customize_Manager();
                        $wp_customize->set_post_value('site_icon', $icon_id);
                        $wp_customize->save_changeset_post(['status' => 'publish']);
                        $messages[] = 'Site icon applied via WP_Customize_Manager.';
                    } catch (Exception $e) {
                        $messages[] = 'Error with WP_Customize_Manager: ' . $e->getMessage();
                    }
                }
            }
        } else {
            // Try to get from option as fallback
            $icon_id = get_option('_mtl_user_uploaded_icon_id');
            if ($icon_id) {
                update_option('site_icon', $icon_id);
                $messages[] = 'Site icon setting restored from backup.';
                
                // If immediate application is requested
                if ($apply_immediately) {
                    // Try to force WordPress to flush its caches
                    wp_cache_flush();
                    $messages[] = 'Cache flushed for site icon from backup.';
                }
            } else {
                $success = false;
                $messages[] = 'Could not finalize site icon: No icon ID found.';
            }
        }
    }
    
    // Final immediate refresh for all customizer settings
    if ($apply_immediately && ($has_logo || $has_icon)) {
        // Call our thorough function that ensures all settings are properly applied
        mtl_ensure_customizer_settings();
        $messages[] = 'Applied thorough customizer settings update.';
    }
    
    // Log the process
    mtl_debug_log('Finalize branding settings: ' . implode(' | ', $messages));
    
    if ($success) {
        wp_send_json_success([
            'message' => 'Branding settings finalized.',
            'details' => $messages
        ]);
    } else {
        wp_send_json_error([
            'message' => 'There were issues finalizing some branding settings.',
            'details' => $messages
        ]);
    }
}

/**
 * Create a page from template data
 */
function mtl_create_page_from_template($template_data) {
    // Increase memory limit and execution time
    mtl_increase_memory_limit();
    mtl_increase_max_execution_time();
    
    // Check if the template data is valid
    if (empty($template_data) || !is_array($template_data)) {
        return new WP_Error('invalid_data', 'Invalid template data.');
    }
    
    // Get the title from the template data or use a default title
    $title = isset($template_data['title']) ? sanitize_text_field($template_data['title']) : 'Imported Template ' . date('Y-m-d H:i:s');
    
    // Create the page
    $page_args = array(
        'post_title'    => $title,
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_content'  => '',
    );
    
    // Insert the page
    $page_id = wp_insert_post($page_args);
    
    if (is_wp_error($page_id)) {
        return $page_id;
    }
    
    // Save the template data as post meta
    update_post_meta($page_id, '_elementor_data', wp_slash(json_encode($template_data['content'])));
    update_post_meta($page_id, '_elementor_edit_mode', 'builder');
    update_post_meta($page_id, '_elementor_template_type', 'page');
    update_post_meta($page_id, '_elementor_version', '3.6.0'); // Use appropriate version
    
    // If there are page settings, save them
    if (isset($template_data['page_settings'])) {
        update_post_meta($page_id, '_elementor_page_settings', $template_data['page_settings']);
    }
    
    mtl_debug_log('Created page from template: ' . $title . ' (ID: ' . $page_id . ')');
    
    return $page_id;
}

/**
 * Create custom content from template data
 * 
 * @param array $template_data The template data
 * @param string $content_type The content type (post type)
 * @return int|WP_Error The content ID or WP_Error on failure
 */
function mtl_create_custom_content($template_data, $content_type) {
    // Check if the template data is valid
    if (empty($template_data) || !is_array($template_data)) {
        return new WP_Error('invalid_data', 'Invalid template data.');
    }
    
    // Validate the content type
    if (!post_type_exists($content_type)) {
        // If the post type doesn't exist, try to register it temporarily
        if (!register_post_type($content_type, array(
            'public' => true,
            'label' => ucfirst($content_type),
            'show_in_rest' => true,
        ))) {
            return new WP_Error('invalid_post_type', 'Invalid or unregistered post type: ' . $content_type);
        }
    }
    
    // Get the title from the template data or use a default title
    $title = isset($template_data['title']) ? sanitize_text_field($template_data['title']) : 'Imported ' . ucfirst($content_type) . ' ' . date('Y-m-d H:i:s');
    
    // Create the content
    $content_args = array(
        'post_title'    => $title,
        'post_status'   => 'publish',
        'post_type'     => $content_type,
        'post_content'  => '',
    );
    
    // Add excerpt if available
    if (isset($template_data['excerpt'])) {
        $content_args['post_excerpt'] = sanitize_text_field($template_data['excerpt']);
    }
    
    // Add content if available
    if (isset($template_data['content'])) {
        $content_args['post_content'] = $template_data['content'];
    }
    
    // Add date if available
    if (isset($template_data['date'])) {
        $content_args['post_date'] = $template_data['date'];
        $content_args['post_date_gmt'] = $template_data['date'];
    }
    
    // Add author if available
    if (isset($template_data['author'])) {
        $content_args['post_author'] = $template_data['author'];
    }
    
    // Insert the content
    $content_id = wp_insert_post($content_args);
    
    if (is_wp_error($content_id)) {
        return $content_id;
    }
    
    // Process meta fields if available
    if (isset($template_data['meta']) && is_array($template_data['meta'])) {
        foreach ($template_data['meta'] as $meta_key => $meta_value) {
            update_post_meta($content_id, $meta_key, $meta_value);
        }
    }
    
    // Process taxonomies if available
    if (isset($template_data['taxonomies']) && is_array($template_data['taxonomies'])) {
        foreach ($template_data['taxonomies'] as $taxonomy => $terms) {
            if (!empty($terms)) {
                $term_ids = array();
                foreach ($terms as $term) {
                    // Check if term is a string or an array
                    if (is_string($term)) {
                        $term_name = $term;
                        $term_slug = sanitize_title($term);
                    } else if (is_array($term) && isset($term['name'])) {
                        $term_name = $term['name'];
                        $term_slug = isset($term['slug']) ? $term['slug'] : sanitize_title($term_name);
                    } else {
                        continue;
                    }
                    
                    // Find the term by slug
                    $existing_term = get_term_by('slug', $term_slug, $taxonomy);
                    
                    if ($existing_term) {
                        $term_ids[] = $existing_term->term_id;
                    } else {
                        // Try to find by name
                        $existing_term = get_term_by('name', $term_name, $taxonomy);
                        if ($existing_term) {
                            $term_ids[] = $existing_term->term_id;
                        } else {
                            // Create the term if it doesn't exist
                            $new_term = wp_insert_term($term_name, $taxonomy, array('slug' => $term_slug));
                            if (!is_wp_error($new_term)) {
                                $term_ids[] = $new_term['term_id'];
                            }
                        }
                    }
                }
                
                // Set the terms for the post
                if (!empty($term_ids)) {
                    wp_set_object_terms($content_id, $term_ids, $taxonomy);
                    mtl_debug_log("Set {$taxonomy} terms for {$content_type} ID {$content_id}: " . implode(', ', $term_ids));
                }
            }
        }
    }
    
    // Set featured image if available
    if (isset($template_data['featured_image']) || isset($template_data['thumbnail'])) {
        $image_url = isset($template_data['featured_image']) ? $template_data['featured_image'] : $template_data['thumbnail'];
        
        if (!empty($image_url)) {
            // Extract the filename from the URL
            $image_filename = basename($image_url);
            
            // Find the attachment by filename
            $attachment_args = array(
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'posts_per_page' => 1,
                's' => $image_filename
            );
            
            $attachment_query = new WP_Query($attachment_args);
            
            if ($attachment_query->have_posts()) {
                $attachment = $attachment_query->posts[0];
                $attachment_id = $attachment->ID;
                
                // Set as featured image
                set_post_thumbnail($content_id, $attachment_id);
                mtl_debug_log("Set featured image (ID: {$attachment_id}) for {$content_type} ID {$content_id}");
            }
        }
    }
    
    return $content_id;
}

// Flush rewrite rules on plugin activation
function mtl_activate() {
    // Make sure the direct-import.php file is included
    require_once MTL_PLUGIN_DIR . 'includes/direct-import.php';
    
    // Ensure our rewrite rules are added before flushing
    if (function_exists('mtl_register_direct_access_url')) {
        mtl_register_direct_access_url();
    } else {
        // If the function still doesn't exist, add a basic rewrite rule
        add_rewrite_rule(
            'mtl-direct-import/?$',
            'index.php?mtl_direct_import=1',
            'top'
        );
        
        add_rewrite_tag('%mtl_direct_import%', '([0-9]+)');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'mtl_activate');

// Flush rewrite rules on plugin deactivation
function mtl_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'mtl_deactivate');

// Register theme switch hook to apply saved theme mods when the target theme is activated
// add_action('after_switch_theme', 'mtl_apply_saved_theme_mods');

/**
 * Apply saved theme mods when a theme is activated
 */
// function mtl_apply_saved_theme_mods() {
//     $saved_theme_mods = get_option('mtl_saved_theme_mods', array());
//     $current_theme = get_template();
//     
//     if (isset($saved_theme_mods[$current_theme]) && is_array($saved_theme_mods[$current_theme])) {
//         // Apply the saved theme mods
//         foreach ($saved_theme_mods[$current_theme] as $key => $value) {
//             set_theme_mod($key, $value);
//         }
//         
//         // Apply custom CSS if it exists
//         if (isset($saved_theme_mods[$current_theme . '_css']) && !empty($saved_theme_mods[$current_theme . '_css'])) {
//             wp_update_custom_css_post($saved_theme_mods[$current_theme . '_css']);
//         }
//         
//         mtl_debug_log('Applied saved theme mods for ' . $current_theme . ' theme');
//         
//         // Remove the saved theme mods for this theme since they've been applied
//         unset($saved_theme_mods[$current_theme]);
//         unset($saved_theme_mods[$current_theme . '_css']);
//         update_option('mtl_saved_theme_mods', $saved_theme_mods);
//     }
// }

/**
 * The code that runs during plugin activation.
 */
// ... existing code ...

/**
 * Handle direct kit import
 */
function mtl_direct_kit_import_handler() {
    // Check nonce
    if (!isset($_POST['mtl_direct_kit_import_nonce']) || !wp_verify_nonce($_POST['mtl_direct_kit_import_nonce'], 'mtl_direct_kit_import_action')) {
        wp_die('Security check failed. Please try again.');
    }
    
    // Check if user has permission
    if (!current_user_can('edit_posts')) {
        wp_die('You do not have permission to import templates.');
    }
    
    // Get the kit URL
    $kit_url = isset($_POST['kit_url']) ? sanitize_text_field($_POST['kit_url']) : '';
    if (empty($kit_url)) {
        wp_die('No kit URL provided.');
    }
    
    // Process site logo ID if available (from AJAX upload)
    if (isset($_POST['site_logo_id']) && !empty($_POST['site_logo_id'])) {
        $logo_id = intval($_POST['site_logo_id']);
        
        if ($logo_id > 0) {
            // Set as custom logo
            set_theme_mod('custom_logo', $logo_id);
            mtl_debug_log('Set uploaded logo as custom_logo from ID: ' . $logo_id);
            
            // Set flag to indicate user uploaded logo
            update_option('_mtl_user_uploaded_logo', true);
        }
    }
    // Fallback to process logo data from direct form submission (though not used in the new flow)
    else if (isset($_POST['site_logo']) && !empty($_POST['site_logo'])) {
        $logo_data = $_POST['site_logo'];
        
        // Process data URL format (data:image/jpeg;base64,base64string)
        if (preg_match('/^data:image\/(\w+);base64,/', $logo_data, $matches)) {
            $image_type = $matches[1];
            $base64_data = substr($logo_data, strpos($logo_data, ',') + 1);
            $decoded_data = base64_decode($base64_data);
            
            if ($decoded_data !== false) {
                $upload_dir = wp_upload_dir();
                $filename = 'site-logo-' . time() . '.' . $image_type;
                $file_path = $upload_dir['path'] . '/' . $filename;
                
                // Save decoded data to file
                file_put_contents($file_path, $decoded_data);
                
                // Prepare the file array for sideload
                $file = [
                    'name' => $filename,
                    'tmp_name' => $file_path,
                    'error' => 0,
                    'size' => filesize($file_path)
                ];
                
                // Import the image and set as logo
                $upload = wp_handle_sideload($file, ['test_form' => false]);
                
                if (!isset($upload['error'])) {
                    // Create attachment
                    $attachment = [
                        'guid' => $upload['url'],
                        'post_mime_type' => $upload['type'],
                        'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    ];
                    
                    $attach_id = wp_insert_attachment($attachment, $upload['file']);
                    
                    if (!is_wp_error($attach_id)) {
                        // Generate attachment metadata
                        $attachment_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                        wp_update_attachment_metadata($attach_id, $attachment_data);
                        
                        // Set as custom logo
                        set_theme_mod('custom_logo', $attach_id);
                        mtl_debug_log('Set uploaded logo as custom_logo: ' . $attach_id);
                        
                        // Set flag to indicate user uploaded logo
                        update_option('_mtl_user_uploaded_logo', true);
                    }
                }
            }
        }
    }
    
    // Process site icon ID if available (from AJAX upload)
    if (isset($_POST['site_icon_id']) && !empty($_POST['site_icon_id'])) {
        $icon_id = intval($_POST['site_icon_id']);
        
        if ($icon_id > 0) {
            // Set as site icon
            update_option('site_icon', $icon_id);
            mtl_debug_log('Set uploaded icon as site_icon from ID: ' . $icon_id);
            
            // Set flag to indicate user uploaded icon
            update_option('_mtl_user_uploaded_icon', true);
        }
    }
    // Fallback to process icon data from direct form submission (though not used in the new flow)
    else if (isset($_POST['site_icon']) && !empty($_POST['site_icon'])) {
        $icon_data = $_POST['site_icon'];
        
        // Process data URL format (data:image/png;base64,base64string)
        if (preg_match('/^data:image\/(\w+);base64,/', $icon_data, $matches)) {
            $image_type = $matches[1];
            $base64_data = substr($icon_data, strpos($icon_data, ',') + 1);
            $decoded_data = base64_decode($base64_data);
            
            if ($decoded_data !== false) {
                $upload_dir = wp_upload_dir();
                $filename = 'site-icon-' . time() . '.' . $image_type;
                $file_path = $upload_dir['path'] . '/' . $filename;
                
                // Save decoded data to file
                file_put_contents($file_path, $decoded_data);
                
                // Prepare the file array for sideload
                $file = [
                    'name' => $filename,
                    'tmp_name' => $file_path,
                    'error' => 0,
                    'size' => filesize($file_path)
                ];
                
                // Import the image and set as site icon
                $upload = wp_handle_sideload($file, ['test_form' => false]);
                
                if (!isset($upload['error'])) {
                    // Create attachment
                    $attachment = [
                        'guid' => $upload['url'],
                        'post_mime_type' => $upload['type'],
                        'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    ];
                    
                    $attach_id = wp_insert_attachment($attachment, $upload['file']);
                    
                    if (!is_wp_error($attach_id)) {
                        // Generate attachment metadata
                        $attachment_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                        wp_update_attachment_metadata($attach_id, $attachment_data);
                        
                        // Set as site icon
                        update_option('site_icon', $attach_id);
                        mtl_debug_log('Set uploaded icon as site_icon: ' . $attach_id);
                        
                        // Set flag to indicate user uploaded icon
                        update_option('_mtl_user_uploaded_icon', true);
                    }
                }
            }
        }
    }
    
    // Force a site title and tagline if they're empty
    $site_title = get_option('blogname');
    $site_tagline = get_option('blogdescription');
    
    if (empty($site_title)) {
        update_option('blogname', 'Digo - Digital Marketing Agency WordPress Theme');
        mtl_debug_log('Set default site title since it was empty');
    }
    
    if (empty($site_tagline)) {
        update_option('blogdescription', 'Digital Marketing WordPress Theme');
        mtl_debug_log('Set default site tagline since it was empty');
    }
    
    // Get the import mode
    $import_mode = isset($_POST['import_mode']) ? sanitize_text_field($_POST['import_mode']) : 'all';
    
    // Get the page ID if in single mode
    $page_id = '';
    if ($import_mode === 'single') {
        $page_id = isset($_POST['page_id']) ? sanitize_text_field($_POST['page_id']) : '';
        if (empty($page_id)) {
            wp_die('No page ID provided for single page import.');
        }
        
        // Remove .json extension if present
        $page_id = str_replace('.json', '', $page_id);
    }
    
    // Increase memory limit and execution time
    mtl_increase_memory_limit();
    mtl_increase_max_execution_time();
    
    // Start output buffering to capture any errors
    ob_start();
    
    try {
        // Create a temporary directory
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/mtl_temp';
        $session_id = 'import_' . time() . '_' . mt_rand(1000, 9999);
        $session_dir = $temp_dir . '/' . $session_id;
        
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        wp_mkdir_p($session_dir);
        
        // Download the ZIP file
        $zip_file = $session_dir . '/template-kit.zip';
        $response = wp_remote_get($kit_url, array(
            'timeout' => 300,
            'stream' => true,
            'filename' => $zip_file
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to download ZIP file: ' . $response->get_error_message());
        }
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            throw new Exception('Failed to download ZIP file: HTTP ' . wp_remote_retrieve_response_code($response));
        }
        
        // Extract the ZIP file
        $zip = new ZipArchive();
        if ($zip->open($zip_file) !== true) {
            throw new Exception('Failed to open ZIP file.');
        }
        
        $zip->extractTo($session_dir);
        $zip->close();
        
        // Check if we have a theme to install
        $theme_dir = $session_dir . '/inc/theme';
        $theme_installed = false;
        $theme_message = '';
        
        if (file_exists($theme_dir) && is_dir($theme_dir)) {
            $theme_files = glob($theme_dir . '/*.zip');
            if (!empty($theme_files)) {
                $theme_zip = $theme_files[0]; // Take the first theme ZIP file
                mtl_debug_log('Found theme ZIP file: ' . $theme_zip);
                
                // Check if theme installation is requested
                $install_theme = isset($_POST['install_theme']) ? filter_var($_POST['install_theme'], FILTER_VALIDATE_BOOLEAN) : true;
                
                if ($install_theme) {
                    mtl_debug_log('Theme installation requested, proceeding with installation...');
                    
                    // Install and activate the theme
                    $theme_result = mtl_install_theme_from_zip($theme_zip);
                    
                    if ($theme_result['success']) {
                        echo '<div class="theme-installation-results">';
                        echo '<h3>Theme Installation</h3>';
                        echo '<p class="result-item success">✅ ' . $theme_result['message'] . '</p>';
                        echo '</div>';
                        
                        $theme_installed = true;
                        $theme_message = $theme_result['message'];
                    } else {
                        echo '<div class="theme-installation-results">';
                        echo '<h3>Theme Installation</h3>';
                        echo '<p class="result-item error">❌ ' . $theme_result['message'] . '</p>';
                        echo '</div>';
                        
                        $theme_message = $theme_result['message'];
                    }
                } else {
                    mtl_debug_log('Theme installation skipped by user request.');
                }
            }
        }
        
        // Read manifest.json if it exists to get page and post names
        $manifest_file = $session_dir . '/manifest.json';
        $manifest_data = array();
        $page_names = array();
        $post_names = array();
        
        if (file_exists($manifest_file)) {
            $manifest_json = file_get_contents($manifest_file);
            $manifest_data = json_decode($manifest_json, true);
            
            // Extract page names from manifest
            if (isset($manifest_data['content']['page']) && is_array($manifest_data['content']['page'])) {
                foreach ($manifest_data['content']['page'] as $page_id => $page_info) {
                    if (isset($page_info['title'])) {
                        $page_names[$page_id] = $page_info['title'];
                    }
                }
            }
            
            // Extract post names from manifest
            if (isset($manifest_data['content']['post']) && is_array($manifest_data['content']['post'])) {
                foreach ($manifest_data['content']['post'] as $post_id => $post_info) {
                    if (isset($post_info['title'])) {
                        $post_names[$post_id] = $post_info['title'];
                    }
                }
            }
        }
        
        // Import taxonomies if they exist
        $taxonomies_dir = $session_dir . '/taxonomies';
        $imported_taxonomies = array();
        $taxonomy_term_mapping = array(); // Store term mappings for later use
        
        // Import theme mods from .dat file if it exists
        $inc_dir = $session_dir . '/inc';
        $theme_mods_imported = false;
        
        if (file_exists($inc_dir) && is_dir($inc_dir)) {
            $dat_files = glob($inc_dir . '/*.dat');
            
            foreach ($dat_files as $dat_file) {
                try {
                    // Use the new customizer import function
                    $result = mtl_import_customizer_data($dat_file);
                    
                    if (is_wp_error($result)) {
                        mtl_debug_log('Error importing customizer data: ' . $result->get_error_message());
                        echo '<div class="theme-mods-results">';
                        echo '<h3>Theme Customizations</h3>';
                        echo '<p class="result-item error">❌ Error importing customizer data: ' . $result->get_error_message() . '</p>';
                        echo '</div>';
                    } else {
                        $theme_mods_imported = ($result['status'] === 'success');
                        
                        echo '<div class="theme-mods-results">';
                        echo '<h3>Theme Customizations</h3>';
                        
                        if ($result['status'] === 'success') {
                            echo '<p class="result-item success">✅ ' . $result['message'] . '</p>';
                        } else if ($result['status'] === 'pending') {
                            echo '<p class="result-item warning">⚠️ ' . $result['message'] . '</p>';
                        } else {
                            echo '<p class="result-item error">❌ ' . $result['message'] . '</p>';
                        }
                        
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    mtl_debug_log('Error importing customizer data: ' . $e->getMessage());
                    echo '<div class="theme-mods-results">';
                    echo '<h3>Theme Customizations</h3>';
                    echo '<p class="result-item error">❌ Error importing customizer data: ' . $e->getMessage() . '</p>';
                    echo '</div>';
                }
            }
        }
        
        // Import site settings if available
        $site_settings_file = $session_dir . '/site-settings.json';
        $site_settings_imported = false;
        
        if (file_exists($site_settings_file)) {
            try {
                $site_settings = json_decode(file_get_contents($site_settings_file), true);
                if ($site_settings) {
                    // Check if Elementor is active
                    if (did_action('elementor/loaded')) {
                        // Get the active kit ID
                        $kit_id = \Elementor\Plugin::$instance->kits_manager->get_active_id();
                        if (!$kit_id) {
                            // Create a new kit if none exists
                            $kit = \Elementor\Plugin::$instance->kits_manager->create();
                            $kit_id = $kit->get_id();
                        }
                        
                        // Update the kit with the new settings
                        $kit_document = \Elementor\Plugin::$instance->documents->get($kit_id);
                        if ($kit_document) {
                            $kit_document->update_settings($site_settings['settings']);
                            $site_settings_imported = true;
                        }
                    } else {
                        // Store settings in options if Elementor is not active
                        update_option('mtl_imported_site_settings', $site_settings);
                        $site_settings_imported = true;
                    }
                }
            } catch (Exception $e) {
                mtl_debug_log('Error importing site settings: ' . $e->getMessage());
            }
        }
        
        if (file_exists($taxonomies_dir) && is_dir($taxonomies_dir)) {
            $taxonomy_files = glob($taxonomies_dir . '/*.json');
            
            foreach ($taxonomy_files as $taxonomy_file) {
                $taxonomy_name = basename($taxonomy_file, '.json');
                
                // Skip if taxonomy doesn't exist in WordPress
                if (!taxonomy_exists($taxonomy_name)) {
                    continue;
                }
                
                $taxonomy_data = json_decode(file_get_contents($taxonomy_file), true);
                
                if (is_array($taxonomy_data)) {
                    foreach ($taxonomy_data as $term) {
                        // Check if term already exists
                        $existing_term = term_exists($term['slug'], $taxonomy_name);
                        
                        if (!$existing_term) {
                            // Create the term
                            $result = wp_insert_term(
                                $term['name'],
                                $taxonomy_name,
                                array(
                                    'description' => isset($term['description']) ? $term['description'] : '',
                                    'slug' => $term['slug'],
                                    'parent' => isset($term['parent']) ? $term['parent'] : 0
                                )
                            );
                            
                            if (!is_wp_error($result)) {
                                $imported_taxonomies[] = $taxonomy_name . ': ' . $term['name'];
                                
                                // Store the term mapping (original term_id => new term_id)
                                if (isset($term['term_id'])) {
                                    $taxonomy_term_mapping[$taxonomy_name][$term['term_id']] = $result['term_id'];
                                }
                            }
                        } else {
                            // Store the existing term mapping
                            if (isset($term['term_id'])) {
                                $term_id = is_array($existing_term) ? $existing_term['term_id'] : $existing_term;
                                $taxonomy_term_mapping[$taxonomy_name][$term['term_id']] = $term_id;
                            }
                        }
                    }
                }
            }
        }
        
        // Read post type to taxonomy mappings from manifest if available
        $post_type_taxonomies = array();
        if (!empty($manifest_data) && isset($manifest_data['taxonomies']) && is_array($manifest_data['taxonomies'])) {
            $post_type_taxonomies = $manifest_data['taxonomies'];
        }
        
        // Import content from wp-content folder if it exists
        $wp_content_dir = $session_dir . '/wp-content';
        $imported_wp_content = array();
        
        if (file_exists($wp_content_dir) && is_dir($wp_content_dir)) {
            // Get all subdirectories in wp-content (each represents a post type)
            $post_type_dirs = array_filter(glob($wp_content_dir . '/*'), 'is_dir');
            
            echo '<div class="wp-content-results">';
            echo '<h3>Importing WordPress Content</h3>';
            
            foreach ($post_type_dirs as $post_type_dir) {
                $post_type = basename($post_type_dir);
                
                // Look for XML files in the post type directory
                $xml_files = glob($post_type_dir . '/*.xml');
                
                if (!empty($xml_files)) {
                    echo '<div class="post-type-group">';
                    echo '<h4>Importing ' . ucfirst($post_type) . '</h4>';
                    echo '<ul>';
                    
                    foreach ($xml_files as $xml_file) {
                        try {
                            mtl_debug_log('Importing ' . $post_type . ' from ' . $xml_file);
                            
                            // Check if SimpleXML extension is available
                            if (!class_exists('SimpleXMLElement')) {
                                throw new Exception('SimpleXMLElement class not found. PHP SimpleXML extension may not be enabled.');
                            }
                            
                            // Import the XML file
                            $import_result = mtl_import_wp_content_xml($xml_file, $post_type);
                            
                            if (!empty($import_result)) {
                                $imported_wp_content[$post_type] = $import_result;
                                mtl_debug_log('Successfully imported ' . count($import_result) . ' ' . $post_type . ' items');
                                
                                // Display imported items
                                foreach ($import_result as $item) {
                                    $icon = ($item['type'] === 'attachment') ? '📎' : '✅';
                                    echo '<li class="result-item success">' . $icon . ' ' . $item['title'] . ' (ID: ' . $item['id'] . ')</li>';
                                }
                            } else {
                                mtl_debug_log('No ' . $post_type . ' items were imported');
                                echo '<li class="result-item error">❌ No ' . $post_type . ' items were imported</li>';
                            }
                        } catch (Exception $e) {
                            mtl_debug_log('Error importing ' . $post_type . ' XML: ' . $e->getMessage());
                            echo '<li class="result-item error">❌ Error importing ' . $post_type . ': ' . $e->getMessage() . '</li>';
                            // Continue with the next file
                        }
                    }
                    
                    echo '</ul>';
                    echo '</div>';
                }
            }
            
            echo '</div>';
        }
        
        // Find all content directories and JSON files
        $content_dir = $session_dir . '/content';
        if (!file_exists($content_dir) || !is_dir($content_dir)) {
            throw new Exception('No content directory found in the ZIP file.');
        }
        
        // Get all subdirectories in the content folder
        $content_types = array_filter(glob($content_dir . '/*'), 'is_dir');
        if (empty($content_types)) {
            throw new Exception('No content type directories found in the content directory.');
        }
        
        // Store all JSON files by content type
        $content_files = array();
        foreach ($content_types as $type_dir) {
            $type = basename($type_dir);
            $json_files = glob($type_dir . '/*.json');
            if (!empty($json_files)) {
                $content_files[$type] = $json_files;
            }
        }
        
        if (empty($content_files)) {
            throw new Exception('No JSON files found in any content type directory.');
        }
        
        // For backward compatibility, ensure we have page and post variables
        $json_files = isset($content_files['page']) ? $content_files['page'] : array();
        $post_json_files = isset($content_files['post']) ? $content_files['post'] : array();
        
        // Filter files if in single mode
        if ($import_mode === 'single') {
            if (isset($content_files['page'])) {
                $filtered_files = array();
                foreach ($content_files['page'] as $file) {
                    $filename = basename($file);
                    $file_id = str_replace('.json', '', $filename);
                    if ($file_id === $page_id || $filename === $page_id) {
                        $filtered_files[] = $file;
                        break;
                    }
                }
                if (!empty($filtered_files)) {
                    $content_files['page'] = $filtered_files;
                    $json_files = $filtered_files;
                } else {
                    $content_files['page'] = array();
                    $json_files = array();
                }
            }
        }
        
        // Process each JSON file
        $results = array();
        $success_count = 0;
        $error_count = 0;
        
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Template Import Results</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    line-height: 1.5;
                    padding: 20px;
                    max-width: 1200px;
                    margin: 0 auto;
                    color: #333;
                }
                h1 {
                    color: #23282d;
                    font-size: 23px;
                    font-weight: 400;
                    margin: 0 0 20px;
                    padding: 9px 0 4px;
                    line-height: 1.3;
                }
                .results {
                    background: #f5f5f5;
                    padding: 15px;
                    border-radius: 5px;
                    border: 1px solid #ddd;
                    margin-bottom: 20px;
                }
                .success {
                    color: #46b450;
                }
                .error {
                    color: #dc3232;
                }
                .warning {
                    color: #ffb900;
                }
                .summary {
                    font-size: 16px;
                    font-weight: bold;
                    margin-bottom: 15px;
                }
                .result-item {
                    margin-bottom: 5px;
                    padding-bottom: 5px;
                    border-bottom: 1px solid #eee;
                }
                .back-button {
                    display: inline-block;
                    padding: 8px 16px;
                    background-color: #2271b1;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    margin-top: 20px;
                }
                .taxonomy-results {
                    margin-bottom: 20px;
                    padding: 10px;
                    background-color: #f9f9f9;
                    border: 1px solid #eee;
                    border-radius: 4px;
                }
                .taxonomy-results h3 {
                    margin-top: 0;
                    margin-bottom: 10px;
                    color: #23282d;
                    font-size: 16px;
                }
                .taxonomy-results ul {
                    margin: 0;
                    padding-left: 20px;
                }
                .term-info {
                    display: block;
                    font-size: 12px;
                    color: #666;
                    margin-left: 20px;
                    margin-top: 3px;
                }
                .wp-content-results {
                    margin-bottom: 20px;
                    padding: 10px;
                    background-color: #f9f9f9;
                    border: 1px solid #eee;
                    border-radius: 4px;
                }
                .wp-content-results h3 {
                    margin-top: 0;
                    margin-bottom: 10px;
                    color: #23282d;
                    font-size: 16px;
                }
                .post-type-group {
                    margin-bottom: 15px;
                }
                .post-type-group h4 {
                    margin: 10px 0 5px;
                    color: #23282d;
                    font-size: 14px;
                    font-weight: 600;
                }
                .post-type-group ul {
                    margin: 0;
                    padding-left: 20px;
                }
            </style>
        </head>
        <body>
            <h1>Template Import Results</h1>';
        
        echo '<div class="summary">Processing ';
        $content_summary = array();
        foreach ($content_files as $type => $files) {
            $content_summary[] = count($files) . ' ' . $type . ' files';
        }
        echo implode(', ', $content_summary);
        if ($site_settings_imported) {
            echo ', site settings';
        }
        echo ', taxonomies, and WordPress content...</div>';
        echo '<div class="results">';
        
        // Display imported site settings
        if ($site_settings_imported) {
            echo '<div class="site-settings-results">';
            echo '<h3>Imported Site Settings</h3>';
            echo '<ul>';
            echo '<li class="result-item success">✅ Site settings imported successfully</li>';
            echo '</ul>';
            echo '</div>';
        }
        
        // Display imported taxonomies
        if (!empty($imported_taxonomies)) {
            echo '<div class="taxonomy-results">';
            echo '<h3>Imported Taxonomies</h3>';
            echo '<ul>';
            foreach ($imported_taxonomies as $taxonomy) {
                echo '<li class="result-item success">✅ ' . $taxonomy . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        // Display imported wp-content items
        if (!empty($imported_wp_content)) {
            echo '<div class="wp-content-results">';
            echo '<h3>Imported WordPress Content</h3>';
            
            // Group items by post type
            $grouped_items = array();
            
            foreach ($imported_wp_content as $post_type => $items) {
                foreach ($items as $item) {
                    $type = isset($item['type']) ? $item['type'] : $post_type;
                    if (!isset($grouped_items[$type])) {
                        $grouped_items[$type] = array();
                    }
                    $grouped_items[$type][] = $item;
                }
            }
            
            // Display each post type group
            foreach ($grouped_items as $type => $items) {
                echo '<div class="post-type-group">';
                echo '<h4>' . ucfirst($type) . ' (' . count($items) . ')</h4>';
                echo '<ul>';
                
                foreach ($items as $item) {
                    $icon = ($type === 'attachment') ? '📎' : '✅';
                    echo '<li class="result-item success">' . $icon . ' ' . $item['title'] . ' (ID: ' . $item['id'] . ')</li>';
                }
                
                echo '</ul>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Import all content types
        foreach ($content_files as $type => $files) {
            if (empty($files)) {
                continue;
            }
            
            echo '<div class="post-type-group">';
            echo '<h4>Importing ' . ucfirst($type) . 's</h4>';
            echo '<ul>';
            
            // Store imported content IDs for later taxonomy processing
            $imported_content_ids = array();
            
            foreach ($files as $file) {
                $filename = basename($file);
                $file_id = str_replace('.json', '', $filename);
                
                try {
                    // Read the JSON file
                    $json_string = file_get_contents($file);
                    if (empty($json_string)) {
                        throw new Exception('Empty file or failed to read file content.');
                    }
                    
                    // Parse JSON
                    $json_data = json_decode($json_string, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Invalid JSON: ' . json_last_error_msg());
                    }
                    
                    // Set the title from manifest if available
                    $names_var = $type . '_names';
                    if (isset($$names_var) && isset($$names_var[$file_id])) {
                        $json_data['title'] = $$names_var[$file_id];
                    }
                    
                    // Check for thumbnail in manifest data
                    if (!empty($manifest_data) && isset($manifest_data['content'][$type][$file_id]['thumbnail'])) {
                        $thumbnail_url = $manifest_data['content'][$type][$file_id]['thumbnail'];
                        if (!empty($thumbnail_url)) {
                            mtl_debug_log("Found thumbnail URL in manifest for {$type} ID {$file_id}: {$thumbnail_url}");
                            // Add to the JSON data
                            $json_data['thumbnail'] = $thumbnail_url;
                        }
                    }
                    
                    // Create the content based on type
                    $content_id = null;
                    if ($type === 'page') {
                        $content_id = mtl_create_page_from_template($json_data);
                    } else if ($type === 'post') {
                        $content_id = mtl_create_post_from_template($json_data);
                    } else {
                        // For other content types, use a generic function
                        $content_id = mtl_create_custom_content($json_data, $type);
                    }
                    
                    if (is_wp_error($content_id)) {
                        throw new Exception($content_id->get_error_message());
                    }
                    
                    // Store the imported content ID with its title for later taxonomy processing
                    $imported_content_ids[$file_id] = array(
                        'id' => $content_id,
                        'title' => $json_data['title']
                    );
                    
                    // Check if we need to process manifest terms
                    if (isset($_POST['process_manifest_terms']) && $_POST['process_manifest_terms'] === 'true' && 
                        isset($_POST['use_manifest_relationships']) && $_POST['use_manifest_relationships'] === 'true' && 
                        !empty($manifest_data) && isset($manifest_data['content'][$type][$file_id])) {
                        
                        // Get the post data from manifest
                        $post_data = $manifest_data['content'][$type][$file_id];
                        
                        // Process terms if they exist
                        if (isset($post_data['terms']) && is_array($post_data['terms'])) {
                            foreach ($post_data['terms'] as $term) {
                                $taxonomy = $term['taxonomy'];
                                $term_slug = $term['slug'];
                                
                                // Find the term in our mapping
                                if (isset($taxonomy_term_mapping[$taxonomy][$term['term_id']])) {
                                    $term_id = $taxonomy_term_mapping[$taxonomy][$term['term_id']];
                                    wp_set_object_terms($content_id, $term_id, $taxonomy, true);
                                    mtl_debug_log("Attached term ID {$term_id} ({$taxonomy}) to {$type} ID {$content_id}");
                                }
                            }
                        }
                        
                        // Process featured image if thumbnail URL exists in manifest
                        if (isset($post_data['thumbnail']) && !empty($post_data['thumbnail']) && 
                            isset($_POST['set_featured_image']) && $_POST['set_featured_image'] === 'true') {
                            
                            mtl_debug_log('Processing featured image from manifest thumbnail URL: ' . $post_data['thumbnail']);
                            
                            // Check if post already has a featured image
                            if (!has_post_thumbnail($content_id)) {
                                // Import the featured image from the thumbnail URL
                                $attachment_id = mtl_download_and_import_image($post_data['thumbnail']);
                                
                                if (!is_wp_error($attachment_id)) {
                                    // Set as featured image
                                    $result = set_post_thumbnail($content_id, $attachment_id);
                                    
                                    if ($result) {
                                        mtl_debug_log("Set featured image for {$type} ID {$content_id} from manifest thumbnail URL");
                                    } else {
                                        mtl_debug_log("Failed to set featured image for {$type} ID {$content_id}");
                                    }
                                } else {
                                    mtl_debug_log("Failed to import featured image for {$type} ID {$content_id}: " . $attachment_id->get_error_message());
                                }
                            } else {
                                mtl_debug_log("Post already has a featured image, skipping import for {$type} ID {$content_id}");
                            }
                        }
                        
                        // Check if we need to process Elementor data
                        if (function_exists('update_post_meta') && function_exists('get_post_meta')) {
                            $elementor_data = get_post_meta($content_id, '_elementor_data', true);
                            
                            if (!empty($elementor_data)) {
                                mtl_debug_log("Processing Elementor data for {$type} ID {$content_id}");
                                
                                // This would require a more complex implementation to map old image IDs to new ones
                                // For now, we'll just log that we found Elementor data
                                mtl_debug_log("Found Elementor data for {$type} ID {$content_id}");
                            }
                        }
                    }
                    
                    $result_text = '<li class="result-item success">✅ ' . esc_html($json_data['title']) . ' (' . $type . ' ID: ' . $content_id . ')</li>';
                    $success_count++;
                    
                } catch (Exception $e) {
                    $result_text = '<li class="result-item error">❌ Failed to import ' . esc_html($filename) . ': ' . esc_html($e->getMessage()) . '</li>';
                    $error_count++;
                }
                
                echo $result_text;
                flush();
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        // Process taxonomies for all imported content from manifest
        if (isset($_POST['process_manifest_terms']) && $_POST['process_manifest_terms'] === 'true' && 
            isset($_POST['use_manifest_relationships']) && $_POST['use_manifest_relationships'] === 'true' && 
            !empty($manifest_data) && isset($manifest_data['content'])) {
            
            echo '<div class="post-type-group">';
            echo '<h4>Processing Taxonomies</h4>';
            echo '<ul>';
            
            // Get all taxonomies from manifest
            $all_taxonomies = isset($manifest_data['taxonomies']) ? $manifest_data['taxonomies'] : array();
            
            // Process each content type
            foreach ($manifest_data['content'] as $type => $items) {
                // Skip if no taxonomies for this post type
                if (!isset($all_taxonomies[$type]) || empty($all_taxonomies[$type])) {
                    continue;
                }
                
                $taxonomies = $all_taxonomies[$type];
                
                // Process each item
                foreach ($items as $item_id => $item_data) {
                    // Skip if no terms
                    if (!isset($item_data['terms']) || empty($item_data['terms'])) {
                        continue;
                    }
                    
                    // Find the post by title
                    $args = array(
                        'post_type' => $type,
                        'post_status' => 'any',
                        'posts_per_page' => 1,
                        'title' => $item_data['title']
                    );
                    
                    $query = new WP_Query($args);
                    
                    if ($query->have_posts()) {
                        $post = $query->posts[0];
                        $local_post_id = $post->ID;
                        
                        // Process terms
                        foreach ($item_data['terms'] as $term) {
                            $taxonomy = $term['taxonomy'];
                            $term_slug = $term['slug'];
                            
                            // Skip if taxonomy not registered for this post type
                            if (!in_array($taxonomy, $taxonomies)) {
                                continue;
                            }
                            
                            // Find the term by slug
                            $existing_term = get_term_by('slug', $term_slug, $taxonomy);
                            
                            if ($existing_term) {
                                // Attach the term to the post
                                $result = wp_set_object_terms($local_post_id, $existing_term->term_id, $taxonomy, true);
                                
                                if (!is_wp_error($result)) {
                                    echo '<li class="result-item success">✅ Attached ' . $taxonomy . ': ' . $term_slug . ' to ' . $item_data['title'] . ' (' . $type . ' ID: ' . $local_post_id . ')</li>';
                                    mtl_debug_log("Attached term {$term_slug} ({$taxonomy}) to {$type} ID {$local_post_id}");
                                } else {
                                    echo '<li class="result-item error">❌ Failed to attach ' . $taxonomy . ': ' . $term_slug . ' to ' . $item_data['title'] . ' - ' . $result->get_error_message() . '</li>';
                                    mtl_debug_log("Failed to attach term {$term_slug} ({$taxonomy}) to {$type} ID {$local_post_id}: " . $result->get_error_message());
                                }
                            } else {
                                echo '<li class="result-item error">❌ Term not found: ' . $term_slug . ' (' . $taxonomy . ')</li>';
                                mtl_debug_log("Term {$term_slug} ({$taxonomy}) not found");
                            }
                            
                            flush();
                        }
                        
                        // Check if this page should be set as the front page
                        if ($type === 'page' && isset($item_data['show_on_front']) && $item_data['show_on_front'] === true) {
                            // Set this page as the front page
                            update_option('show_on_front', 'page');
                            update_option('page_on_front', $local_post_id);
                            echo '<li class="result-item success">✅ Set "' . $item_data['title'] . '" (ID: ' . $local_post_id . ') as the static homepage</li>';
                            mtl_debug_log("Set page {$item_data['title']} (ID: {$local_post_id}) as the static homepage");
                        }
                    } else {
                        echo '<li class="result-item error">❌ Post not found: ' . $item_data['title'] . ' (' . $type . ')</li>';
                        mtl_debug_log("Post not found: {$item_data['title']} ({$type})");
                    }
                }
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        // Set homepage based on manifest data if not already set
        if (!empty($manifest_data) && isset($manifest_data['content']['page'])) {
            // Process site settings
            $site_settings_results = mtl_process_site_settings($manifest_data);
            
            if (!empty($site_settings_results['success'])) {
                echo '<div class="post-type-group">';
                echo '<h4>Setting Site Options</h4>';
                echo '<ul>';
                
                foreach ($site_settings_results['success'] as $message) {
                    echo '<li class="result-item success">✅ ' . $message . '</li>';
                }
                
                echo '</ul>';
                echo '</div>';
            }
        }
        
        echo '</div>';
        
        // Display summary
        echo '<div class="summary">';
        if ($success_count > 0 || !empty($imported_taxonomies) || !empty($imported_wp_content)) {
            echo 'Import completed. ';
            if ($success_count > 0) {
                echo $success_count . ' items imported successfully, ' . $error_count . ' failed. ';
            }
            if (!empty($imported_taxonomies)) {
                echo count($imported_taxonomies) . ' taxonomy terms imported. ';
            }
            if (!empty($imported_wp_content)) {
                $wp_content_count = 0;
                foreach ($imported_wp_content as $items) {
                    $wp_content_count += count($items);
                }
                echo $wp_content_count . ' WordPress content items imported. ';
            }
        } else {
            echo 'Import failed. No items were imported successfully.';
        }
        echo '</div>';
        
        // Add a back button
        echo '<a href="javascript:history.back()" class="back-button">Go Back</a>';
        
        echo '</body></html>';
        
        // Clean up
        mtl_cleanup_directory($session_dir);
        
    } catch (Exception $e) {
        // Display error
        echo '<div class="error">';
        echo 'Error: ' . $e->getMessage();
        echo '</div>';
        
        // Add a back button
        echo '<a href="javascript:history.back()" class="back-button">Go Back</a>';
        
        echo '</body></html>';
    }
    
    exit;
    
    // At the very end, add:
    
    // Apply custom logo and site icon settings if they've been uploaded
    do_action('mtl_after_import_complete');
    
    // Redirect to success page or display success message
}
add_action('admin_post_mtl_direct_kit_import', 'mtl_direct_kit_import_handler');

/**
 * Handler for post-processing imported content
 * This function attaches taxonomies to posts based on the manifest.json file
 */
function mtl_post_process_import_handler() {
    mtl_debug_log('Starting post-processing of imported content...');
    
    // Check if user has permission
    if (!current_user_can('edit_posts')) {
        mtl_debug_log('Error: User does not have permission to process imports.');
        wp_send_json_error(array('message' => 'You do not have permission to process imports.'));
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce'])) {
        mtl_debug_log('Error: Missing nonce in post-processing request.');
        wp_send_json_error(array('message' => 'Security check failed: Missing nonce.'));
        return;
    }
    
    // Get the kit URL
    $kit_url = isset($_POST['kit_url']) ? sanitize_text_field($_POST['kit_url']) : '';
    if (empty($kit_url)) {
        mtl_debug_log('Error: No kit URL provided for post-processing.');
        wp_send_json_error(array('message' => 'No kit URL provided.'));
        return;
    }
    
    mtl_debug_log('Post-processing kit URL: ' . $kit_url);
    
    // Make sure logo and site icon are set
    mtl_ensure_logo_and_site_icon();
    
    // Increase memory limit and execution time
    mtl_increase_memory_limit();
    mtl_increase_max_execution_time();
    
    try {
        // Create a temporary directory
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/mtl_temp';
        $session_id = 'post_process_' . time() . '_' . mt_rand(1000, 9999);
        $session_dir = $temp_dir . '/' . $session_id;
        
        mtl_debug_log('Creating temporary directory for post-processing: ' . $session_dir);
        
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        wp_mkdir_p($session_dir);
        
        // Download the ZIP file
        $zip_file = $session_dir . '/template-kit.zip';
        $response = wp_remote_get($kit_url, array(
            'timeout' => 300,
            'stream' => true,
            'filename' => $zip_file
        ));
        
        if (is_wp_error($response)) {
            mtl_debug_log('Error downloading ZIP file: ' . $response->get_error_message());
            throw new Exception('Failed to download ZIP file: ' . $response->get_error_message());
        }
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            mtl_debug_log('Error downloading ZIP file: HTTP ' . wp_remote_retrieve_response_code($response));
            throw new Exception('Failed to download ZIP file: HTTP ' . wp_remote_retrieve_response_code($response));
        }
        
        // Extract the ZIP file
        $zip = new ZipArchive();
        if ($zip->open($zip_file) !== true) {
            mtl_debug_log('Error opening ZIP file');
            throw new Exception('Failed to open ZIP file.');
        }
        
        $zip->extractTo($session_dir);
        $zip->close();
        
        // Check if we need to install the theme
        $install_theme = isset($_POST['install_theme']) ? filter_var($_POST['install_theme'], FILTER_VALIDATE_BOOLEAN) : false;
        
        if ($install_theme) {
            $theme_dir = $session_dir . '/inc/theme';
            
            if (file_exists($theme_dir) && is_dir($theme_dir)) {
                $theme_files = glob($theme_dir . '/*.zip');
                
                if (!empty($theme_files)) {
                    $theme_zip = $theme_files[0]; // Take the first theme ZIP file
                    mtl_debug_log('Found theme ZIP file in post-processing: ' . $theme_zip);
                    
                    // Install and activate the theme
                    $theme_result = mtl_install_theme_from_zip($theme_zip);
                    
                    if ($theme_result['success']) {
                        $success_messages[] = 'Theme "' . $theme_result['theme_name'] . '" installed and activated successfully.';
                        mtl_debug_log('Post-processing: ' . $theme_result['message']);
                    } else {
                        $error_messages[] = 'Failed to install theme: ' . $theme_result['message'];
                        mtl_debug_log('Post-processing theme installation failed: ' . $theme_result['message']);
                    }
                }
            }
        }
        
        // Read manifest.json to get post-term relationships
        $manifest_file = $session_dir . '/manifest.json';
        if (!file_exists($manifest_file)) {
            mtl_debug_log('Error: Manifest file not found at ' . $manifest_file);
            throw new Exception('Manifest file not found.');
        }
        
        mtl_debug_log('Reading manifest file: ' . $manifest_file);
        
        $manifest_json = file_get_contents($manifest_file);
        $manifest_data = json_decode($manifest_json, true);
        
        if (empty($manifest_data) || !is_array($manifest_data)) {
            mtl_debug_log('Error: Invalid manifest data');
            throw new Exception('Invalid manifest data.');
        }
        
        $results = array(
            'success' => array(),
            'errors' => array()
        );
        
        // Process post-term relationships
        if (isset($_POST['process_manifest_terms']) && $_POST['process_manifest_terms'] === 'true') {
            mtl_debug_log('Processing manifest terms...');
            
            // Process post content
            foreach (array('post', 'page', 'product', 'portfolio') as $content_type) {
                if (isset($manifest_data['content'][$content_type]) && is_array($manifest_data['content'][$content_type])) {
                    mtl_debug_log('Processing ' . count($manifest_data['content'][$content_type]) . ' ' . $content_type . ' items');
                    
                    foreach ($manifest_data['content'][$content_type] as $post_id => $post_data) {
                        // Find the post by title
                        $args = array(
                            'post_type' => $content_type,
                            'post_status' => 'any',
                            'posts_per_page' => 1,
                            'title' => $post_data['title']
                        );
                        
                        $query = new WP_Query($args);
                        
                        if ($query->have_posts()) {
                            $post = $query->posts[0];
                            $local_post_id = $post->ID;
                            
                            mtl_debug_log('Found local post: ' . $post_data['title'] . ' (ID: ' . $local_post_id . ')');
                            
                            // Process terms if they exist
                            if (isset($post_data['terms']) && is_array($post_data['terms'])) {
                                mtl_debug_log('Post has ' . count($post_data['terms']) . ' taxonomy terms to process');
                                
                                foreach ($post_data['terms'] as $term) {
                                    $taxonomy = $term['taxonomy'];
                                    $term_slug = $term['slug'];
                                    
                                    // Find the term by slug
                                    $existing_term = get_term_by('slug', $term_slug, $taxonomy);
                                    
                                    if ($existing_term) {
                                        // Attach the term to the post
                                        $set_terms_result = wp_set_object_terms($local_post_id, $existing_term->term_id, $taxonomy, true);
                                        
                                        if (!is_wp_error($set_terms_result)) {
                                            $results['success'][] = sprintf(
                                                'Attached %s: %s to %s (ID: %d)',
                                                $taxonomy,
                                                $term_slug,
                                                $post_data['title'],
                                                $local_post_id
                                            );
                                            mtl_debug_log("Attached term {$term_slug} ({$taxonomy}) to {$content_type} ID {$local_post_id}");
                                        } else {
                                            $results['errors'][] = sprintf(
                                                'Failed to attach %s: %s to %s: %s',
                                                $taxonomy,
                                                $term_slug,
                                                $post_data['title'],
                                                $set_terms_result->get_error_message()
                                            );
                                            mtl_debug_log("Failed to attach term {$term_slug} ({$taxonomy}) to {$content_type} ID {$local_post_id}: " . $set_terms_result->get_error_message());
                                        }
                                    } else {
                                        // Try to find by name if slug doesn't match
                                        $term_name = isset($term['name']) ? $term['name'] : $term_slug;
                                        $existing_term = get_term_by('name', $term_name, $taxonomy);
                                        
                                        if ($existing_term) {
                                            $set_terms_result = wp_set_object_terms($local_post_id, $existing_term->term_id, $taxonomy, true);
                                            
                                            if (!is_wp_error($set_terms_result)) {
                                                $results['success'][] = sprintf(
                                                    'Attached %s: %s to %s (ID: %d)',
                                                    $taxonomy,
                                                    $term_name,
                                                    $post_data['title'],
                                                    $local_post_id
                                                );
                                                mtl_debug_log("Attached term {$term_name} ({$taxonomy}) to {$content_type} ID {$local_post_id}");
                                            } else {
                                                $results['errors'][] = sprintf(
                                                    'Failed to attach %s: %s to %s: %s',
                                                    $taxonomy,
                                                    $term_name,
                                                    $post_data['title'],
                                                    $set_terms_result->get_error_message()
                                                );
                                                mtl_debug_log("Failed to attach term {$term_name} ({$taxonomy}) to {$content_type} ID {$local_post_id}: " . $set_terms_result->get_error_message());
                                            }
                                        } else {
                                            $results['errors'][] = sprintf(
                                                'Term not found: %s (%s)',
                                                $term_slug,
                                                $taxonomy
                                            );
                                            mtl_debug_log("Term {$term_slug} ({$taxonomy}) not found");
                                        }
                                    }
                                }
                            }
                            
                            // Process featured image if thumbnail URL exists in manifest
                            if (isset($post_data['thumbnail']) && !empty($post_data['thumbnail'])) {
                                mtl_debug_log('Processing featured image from thumbnail URL: ' . $post_data['thumbnail']);
                                
                                // Check if post already has a featured image
                                if (!has_post_thumbnail($local_post_id)) {
                                    // Import the featured image from the thumbnail URL
                                    $attachment_id = mtl_download_and_import_image($post_data['thumbnail']);
                                    
                                    if (!is_wp_error($attachment_id)) {
                                        // Set as featured image
                                        $result = set_post_thumbnail($local_post_id, $attachment_id);
                                        
                                        if ($result) {
                                            $results['success'][] = sprintf(
                                                'Set featured image for %s (ID: %d) from %s',
                                                $post_data['title'],
                                                $local_post_id,
                                                $post_data['thumbnail']
                                            );
                                            mtl_debug_log("Set featured image for {$content_type} ID {$local_post_id} from {$post_data['thumbnail']}");
                                        } else {
                                            $results['errors'][] = sprintf(
                                                'Failed to set featured image for %s (ID: %d)',
                                                $post_data['title'],
                                                $local_post_id
                                            );
                                            mtl_debug_log("Failed to set featured image for {$content_type} ID {$local_post_id}");
                                        }
                                    } else {
                                        $results['errors'][] = sprintf(
                                            'Failed to import featured image for %s: %s',
                                            $post_data['title'],
                                            $attachment_id->get_error_message()
                                        );
                                        mtl_debug_log("Failed to import featured image for {$content_type} ID {$local_post_id}: " . $attachment_id->get_error_message());
                                    }
                                } else {
                                    mtl_debug_log("Post already has a featured image, skipping import for {$content_type} ID {$local_post_id}");
                                }
                            }
                            
                            // Process product gallery for WooCommerce products
                            if ($content_type === 'product' && isset($post_data['gallery']) && is_array($post_data['gallery']) && !empty($post_data['gallery'])) {
                                mtl_debug_log("Processing product gallery for product ID {$local_post_id}");
                                $gallery_ids = array();
                                
                                foreach ($post_data['gallery'] as $gallery_image_url) {
                                    $gallery_filename = basename($gallery_image_url);
                                    
                                    // Find the attachment by filename
                                    $attachment_args = array(
                                        'post_type' => 'attachment',
                                        'post_status' => 'inherit',
                                        'posts_per_page' => 1,
                                        's' => $gallery_filename
                                    );
                                    
                                    $attachment_query = new WP_Query($attachment_args);
                                    
                                    if ($attachment_query->have_posts()) {
                                        $attachment = $attachment_query->posts[0];
                                        $gallery_ids[] = $attachment->ID;
                                        mtl_debug_log("Found gallery image (ID: {$attachment->ID}) for product ID {$local_post_id}");
                                    } else {
                                        // Try to find by URL pattern
                                        $url_pattern = preg_replace('/\.[^.]+$/', '', $gallery_filename);
                                        
                                        $attachment_args = array(
                                            'post_type' => 'attachment',
                                            'post_status' => 'inherit',
                                            'posts_per_page' => 1,
                                            'meta_query' => array(
                                                array(
                                                    'key' => '_wp_attached_file',
                                                    'value' => $url_pattern,
                                                    'compare' => 'LIKE'
                                                )
                                            )
                                        );
                                        
                                        $attachment_query = new WP_Query($attachment_args);
                                        
                                        if ($attachment_query->have_posts()) {
                                            $attachment = $attachment_query->posts[0];
                                            $gallery_ids[] = $attachment->ID;
                                            mtl_debug_log("Found gallery image (ID: {$attachment->ID}) for product ID {$local_post_id} using URL pattern");
                                        } else {
                                            mtl_debug_log("Gallery image not found for product ID {$local_post_id}: {$gallery_filename}");
                                        }
                                    }
                                }
                                
                                if (!empty($gallery_ids)) {
                                    $gallery_ids_string = implode(',', $gallery_ids);
                                    $result = update_post_meta($local_post_id, '_product_image_gallery', $gallery_ids_string);
                                    
                                    if ($result) {
                                        $results['success'][] = sprintf(
                                            'Set product gallery for %s (ID: %d)',
                                            $post_data['title'],
                                            $local_post_id
                                        );
                                        mtl_debug_log("Set product gallery for product ID {$local_post_id}: {$gallery_ids_string}");
                                    } else {
                                        $results['errors'][] = sprintf(
                                            'Failed to set product gallery for %s',
                                            $post_data['title']
                                        );
                                        mtl_debug_log("Failed to set product gallery for product ID {$local_post_id}");
                                    }
                                }
                            }
                            
                            // Process portfolio gallery for portfolio items
                            if ($content_type === 'portfolio' && isset($post_data['gallery']) && is_array($post_data['gallery']) && !empty($post_data['gallery'])) {
                                mtl_debug_log("Processing portfolio gallery for portfolio ID {$local_post_id}");
                                $gallery_ids = array();
                                
                                foreach ($post_data['gallery'] as $gallery_image_url) {
                                    $gallery_filename = basename($gallery_image_url);
                                    
                                    // Find the attachment by filename
                                    $attachment_args = array(
                                        'post_type' => 'attachment',
                                        'post_status' => 'inherit',
                                        'posts_per_page' => 1,
                                        's' => $gallery_filename
                                    );
                                    
                                    $attachment_query = new WP_Query($attachment_args);
                                    
                                    if ($attachment_query->have_posts()) {
                                        $attachment = $attachment_query->posts[0];
                                        $gallery_ids[] = $attachment->ID;
                                        mtl_debug_log("Found gallery image (ID: {$attachment->ID}) for portfolio ID {$local_post_id}");
                                    } else {
                                        // Try to find by URL pattern
                                        $url_pattern = preg_replace('/\.[^.]+$/', '', $gallery_filename);
                                        
                                        $attachment_args = array(
                                            'post_type' => 'attachment',
                                            'post_status' => 'inherit',
                                            'posts_per_page' => 1,
                                            'meta_query' => array(
                                                array(
                                                    'key' => '_wp_attached_file',
                                                    'value' => $url_pattern,
                                                    'compare' => 'LIKE'
                                                )
                                            )
                                        );
                                        
                                        $attachment_query = new WP_Query($attachment_args);
                                        
                                        if ($attachment_query->have_posts()) {
                                            $attachment = $attachment_query->posts[0];
                                            $gallery_ids[] = $attachment->ID;
                                            mtl_debug_log("Found gallery image (ID: {$attachment->ID}) for portfolio ID {$local_post_id} using URL pattern");
                                        } else {
                                            mtl_debug_log("Gallery image not found for portfolio ID {$local_post_id}: {$gallery_filename}");
                                        }
                                    }
                                }
                                
                                if (!empty($gallery_ids)) {
                                    $gallery_ids_string = implode(',', $gallery_ids);
                                    $result = update_post_meta($local_post_id, '_portfolio_gallery', $gallery_ids_string);
                                    
                                    if ($result) {
                                        $results['success'][] = sprintf(
                                            'Set portfolio gallery for %s (ID: %d)',
                                            $post_data['title'],
                                            $local_post_id
                                        );
                                        mtl_debug_log("Set portfolio gallery for portfolio ID {$local_post_id}: {$gallery_ids_string}");
                                    } else {
                                        $results['errors'][] = sprintf(
                                            'Failed to set portfolio gallery for %s',
                                            $post_data['title']
                                        );
                                        mtl_debug_log("Failed to set portfolio gallery for portfolio ID {$local_post_id}");
                                    }
                                }
                            }
                            
                            // Process Elementor data to update image IDs if needed
                            if (function_exists('update_post_meta') && function_exists('get_post_meta')) {
                                $elementor_data = get_post_meta($local_post_id, '_elementor_data', true);
                                
                                if (!empty($elementor_data)) {
                                    mtl_debug_log("Processing Elementor data for {$content_type} ID {$local_post_id}");
                                    
                                    // This would require a more complex implementation to map old image IDs to new ones
                                    // For now, we'll just log that we found Elementor data
                                    mtl_debug_log("Found Elementor data for {$content_type} ID {$local_post_id}");
                                }
                            }
                        } else {
                            $results['errors'][] = sprintf(
                                'Post not found: %s (%s)',
                                $post_data['title'],
                                $content_type
                            );
                            mtl_debug_log("Post not found: {$post_data['title']} ({$content_type})");
                        }
                    }
                }
            }
        }
        
        // Clean up
        mtl_cleanup_directory($session_dir);
        mtl_debug_log('Post-processing completed successfully.');
        
        // Make sure logo and site icon are set
        mtl_ensure_logo_and_site_icon();
        
        // Set homepage based on manifest data if requested
        if (isset($_POST['set_homepage']) && $_POST['set_homepage'] === 'true' && 
            !empty($manifest_data) && isset($manifest_data['content']['page'])) {
            
            // Process site settings
            $site_settings_results = mtl_process_site_settings($manifest_data);
            
            // Merge results
            if (!empty($site_settings_results['success'])) {
                $results['success'] = array_merge($results['success'], $site_settings_results['success']);
            }
            
            if (!empty($site_settings_results['errors'])) {
                $results['errors'] = array_merge($results['errors'], $site_settings_results['errors']);
            }
        }
        
        // Send success response
        wp_send_json_success(array(
            'message' => 'Post-processing completed successfully.',
            'results' => $results
        ));
        
    } catch (Exception $e) {
        mtl_debug_log('Error during post-processing: ' . $e->getMessage());
        
        // Even if there's an error, try to ensure logo and icon are set
        mtl_ensure_logo_and_site_icon();
        
        wp_send_json_error(array(
            'message' => 'Error during post-processing: ' . $e->getMessage()
        ));
    }
}
add_action('wp_ajax_mtl_post_process_import', 'mtl_post_process_import_handler');

/**
 * Helper function to recursively delete a directory
 */
function mtl_cleanup_directory($dir) {
    if (!file_exists($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? mtl_cleanup_directory($path) : unlink($path);
    }
    
    return rmdir($dir);
}

/**
 * Import WordPress content from XML file
 * 
 * @param string $xml_file Path to the XML file
 * @param string $post_type The post type being imported
 * @return array Array of imported items
 */
function mtl_import_wp_content_xml($xml_file, $post_type) {
    if (!file_exists($xml_file)) {
        mtl_debug_log('XML file not found: ' . $xml_file);
        throw new Exception('XML file not found: ' . $xml_file);
    }
    
    // Extract taxonomy relationships from the XML file before importing
    $taxonomy_relationships = mtl_extract_taxonomy_relationships_from_xml($xml_file);
    mtl_debug_log('Extracted ' . count($taxonomy_relationships) . ' taxonomy relationships from ' . $xml_file);
    
    // Check if WordPress Importer plugin is available
    if (!class_exists('WP_Import')) {
        // Try to include the WordPress Importer plugin
        $importer_file = ABSPATH . 'wp-content/plugins/wordpress-importer/wordpress-importer.php';
        
        if (file_exists($importer_file)) {
            try {
                require_once $importer_file;
            } catch (Exception $e) {
                mtl_debug_log('Error loading WordPress Importer: ' . $e->getMessage());
                throw new Exception('Error loading WordPress Importer: ' . $e->getMessage());
            }
        } else {
            // If WordPress Importer is not available, use our custom importer
            try {
                require_once MTL_PLUGIN_DIR . 'includes/class-wp-import.php';
            } catch (Exception $e) {
                mtl_debug_log('Error loading custom importer: ' . $e->getMessage());
                throw new Exception('Error loading custom importer: ' . $e->getMessage());
            }
        }
    }
    
    // Create a temporary file with only the items of the specified post type
    $temp_file = mtl_filter_xml_by_post_type($xml_file, $post_type);
    
    if (!$temp_file) {
        mtl_debug_log('Failed to create temporary XML file for ' . $post_type);
        throw new Exception('Failed to create temporary XML file for ' . $post_type);
    }
    
    // Start output buffering to capture importer output
    ob_start();
    
    try {
        // Initialize the importer
        $importer = new WP_Import();
        $importer->fetch_attachments = true; // Make sure to fetch attachments
        
        // Set additional options for taxonomy handling
        $importer->map_term_ids = isset($_POST['map_term_ids']) && $_POST['map_term_ids'] === 'true';
        $importer->preserve_term_relationships = isset($_POST['preserve_term_relationships']) && $_POST['preserve_term_relationships'] === 'true';
        $importer->preserve_thumbnail_ids = isset($_POST['preserve_thumbnail_ids']) && $_POST['preserve_thumbnail_ids'] === 'true';
        
        // Run the importer
        $result = $importer->import($temp_file);
        
        // Get the output
        $output = ob_get_clean();
        
        // Delete the temporary file
        @unlink($temp_file);
        
        // Parse the output to get the imported items
        $imported_items = array();
        
        // Extract imported items from the importer output or result
        if (isset($importer->processed_posts) && !empty($importer->processed_posts)) {
            foreach ($importer->processed_posts as $old_id => $new_id) {
                $post = get_post($new_id);
                if ($post && ($post->post_type === $post_type || $post->post_type === 'attachment')) {
                    $imported_items[] = array(
                        'id' => $new_id,
                        'title' => $post->post_title,
                        'original_id' => $old_id,
                        'type' => $post->post_type
                    );
                    
                    // Apply taxonomy relationships if available
                    if (isset($taxonomy_relationships[$old_id]) && !empty($taxonomy_relationships[$old_id])) {
                        foreach ($taxonomy_relationships[$old_id] as $taxonomy => $terms) {
                            // Skip special keys that aren't taxonomies
                            if (in_array($taxonomy, array('_thumbnail_id', '_product_image_gallery', '_portfolio_gallery', '_elementor_data'))) {
                                continue;
                            }
                            
                            if (!empty($terms)) {
                                $term_ids = array();
                                
                                foreach ($terms as $term_name) {
                                    // Find the term by name
                                    $existing_term = get_term_by('name', $term_name, $taxonomy);
                                    
                                    if ($existing_term) {
                                        $term_ids[] = $existing_term->term_id;
                                    } else {
                                        // Try to find by slug
                                        $term_slug = sanitize_title($term_name);
                                        $existing_term = get_term_by('slug', $term_slug, $taxonomy);
                                        
                                        if ($existing_term) {
                                            $term_ids[] = $existing_term->term_id;
                                        }
                                    }
                                }
                                
                                if (!empty($term_ids)) {
                                    $result = wp_set_object_terms($new_id, $term_ids, $taxonomy);
                                    if (!is_wp_error($result)) {
                                        mtl_debug_log("Set {$taxonomy} terms for {$post_type} ID {$new_id}: " . implode(', ', $term_ids));
                                    } else {
                                        mtl_debug_log("Failed to set {$taxonomy} terms for {$post_type} ID {$new_id}: " . $result->get_error_message());
                                    }
                                }
                            }
                        }
                    }
                    
                    // Set featured image if available in the XML
                    if (isset($taxonomy_relationships[$old_id]['_thumbnail_id']) && !empty($taxonomy_relationships[$old_id]['_thumbnail_id'])) {
                        $thumbnail_id = $taxonomy_relationships[$old_id]['_thumbnail_id'];
                        
                        // Map the old thumbnail ID to the new one
                        if (isset($importer->processed_posts[$thumbnail_id])) {
                            $new_thumbnail_id = $importer->processed_posts[$thumbnail_id];
                            
                            // Set the featured image
                            $result = set_post_thumbnail($new_id, $new_thumbnail_id);
                            if ($result) {
                                mtl_debug_log("Set featured image (ID: {$new_thumbnail_id}) for {$post_type} ID {$new_id}");
                            } else {
                                mtl_debug_log("Failed to set featured image for {$post_type} ID {$new_id}");
                            }
                        }
                    }
                    
                    // Set product gallery for WooCommerce products
                    if ($post->post_type === 'product' && isset($taxonomy_relationships[$old_id]['_product_image_gallery']) && !empty($taxonomy_relationships[$old_id]['_product_image_gallery'])) {
                        $gallery_ids = explode(',', $taxonomy_relationships[$old_id]['_product_image_gallery']);
                        $new_gallery_ids = array();
                        
                        foreach ($gallery_ids as $gallery_id) {
                            if (isset($importer->processed_posts[$gallery_id])) {
                                $new_gallery_ids[] = $importer->processed_posts[$gallery_id];
                            }
                        }
                        
                        if (!empty($new_gallery_ids)) {
                            $result = update_post_meta($new_id, '_product_image_gallery', implode(',', $new_gallery_ids));
                            if ($result) {
                                mtl_debug_log("Set product gallery images for product ID {$new_id}: " . implode(',', $new_gallery_ids));
                            } else {
                                mtl_debug_log("Failed to set product gallery images for product ID {$new_id}");
                            }
                        }
                    }
                    
                    // Set portfolio gallery for portfolio items
                    if ($post->post_type === 'portfolio' && isset($taxonomy_relationships[$old_id]['_portfolio_gallery']) && !empty($taxonomy_relationships[$old_id]['_portfolio_gallery'])) {
                        $gallery_ids = explode(',', $taxonomy_relationships[$old_id]['_portfolio_gallery']);
                        $new_gallery_ids = array();
                        
                        foreach ($gallery_ids as $gallery_id) {
                            if (isset($importer->processed_posts[$gallery_id])) {
                                $new_gallery_ids[] = $importer->processed_posts[$gallery_id];
                            }
                        }
                        
                        if (!empty($new_gallery_ids)) {
                            $result = update_post_meta($new_id, '_portfolio_gallery', implode(',', $new_gallery_ids));
                            if ($result) {
                                mtl_debug_log("Set portfolio gallery images for portfolio ID {$new_id}: " . implode(',', $new_gallery_ids));
                            } else {
                                mtl_debug_log("Failed to set portfolio gallery images for portfolio ID {$new_id}");
                            }
                        }
                    }
                    
                    // Process Elementor data to update image IDs
                    if (isset($taxonomy_relationships[$old_id]['_elementor_data']) && !empty($taxonomy_relationships[$old_id]['_elementor_data'])) {
                        $elementor_data = $taxonomy_relationships[$old_id]['_elementor_data'];
                        $updated_elementor_data = mtl_update_elementor_image_ids($elementor_data, $importer->processed_posts);
                        
                        if ($updated_elementor_data !== $elementor_data) {
                            $result = update_post_meta($new_id, '_elementor_data', $updated_elementor_data);
                            if ($result) {
                                mtl_debug_log("Updated Elementor data with new image IDs for {$post_type} ID {$new_id}");
                            } else {
                                mtl_debug_log("Failed to update Elementor data for {$post_type} ID {$new_id}");
                            }
                        }
                    }
                }
            }
        }
        
        return $imported_items;
    } catch (Exception $e) {
        // Clean up
        ob_end_clean();
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }
        
        mtl_debug_log('Error during import: ' . $e->getMessage());
        throw new Exception('Error during import: ' . $e->getMessage());
    }
}

/**
 * Update image IDs in Elementor data
 * 
 * @param string $elementor_data The Elementor data JSON string
 * @param array $processed_posts Mapping of old post IDs to new post IDs
 * @return string Updated Elementor data JSON string
 */
function mtl_update_elementor_image_ids($elementor_data, $processed_posts) {
    try {
        $data = json_decode($elementor_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return $elementor_data;
        }
        
        // Function to recursively update image IDs
        $update_ids = function(&$item) use (&$update_ids, $processed_posts) {
            // Check if this is an image widget
            if (isset($item['widgetType']) && $item['widgetType'] === 'image') {
                if (isset($item['settings']['image']['id']) && isset($processed_posts[$item['settings']['image']['id']])) {
                    $item['settings']['image']['id'] = $processed_posts[$item['settings']['image']['id']];
                }
            }
            
            // Check if this is a gallery widget
            if (isset($item['widgetType']) && $item['widgetType'] === 'gallery') {
                if (isset($item['settings']['gallery']) && is_array($item['settings']['gallery'])) {
                    foreach ($item['settings']['gallery'] as &$gallery_item) {
                        if (isset($gallery_item['id']) && isset($processed_posts[$gallery_item['id']])) {
                            $gallery_item['id'] = $processed_posts[$gallery_item['id']];
                        }
                    }
                }
            }
            
            // Check for background images
            if (isset($item['settings']['background_image']['id']) && isset($processed_posts[$item['settings']['background_image']['id']])) {
                $item['settings']['background_image']['id'] = $processed_posts[$item['settings']['background_image']['id']];
            }
            
            // Process elements recursively
            if (isset($item['elements']) && is_array($item['elements'])) {
                foreach ($item['elements'] as &$element) {
                    $update_ids($element);
                }
            }
        };
        
        // Process all elements
        foreach ($data as &$element) {
            $update_ids($element);
        }
        
        return wp_json_encode($data);
    } catch (Exception $e) {
        mtl_debug_log('Error updating Elementor image IDs: ' . $e->getMessage());
        return $elementor_data;
    }
}

/**
 * Extract taxonomy relationships and image attachments from an XML file
 * 
 * @param string $xml_file Path to the XML file
 * @return array Array of taxonomy relationships and image attachments
 */
function mtl_extract_taxonomy_relationships_from_xml($xml_file) {
    $relationships = array();
    $image_attachments = array();
    
    try {
        $xml = simplexml_load_file($xml_file);
        
        if ($xml) {
            $namespaces = $xml->getNamespaces(true);
            $wp = $xml->channel->children($namespaces['wp']);
            
            foreach ($xml->channel->item as $item) {
                $wp_item = $item->children($namespaces['wp']);
                $post_id = (string)$wp_item->post_id;
                $post_type = (string)$wp_item->post_type;
                
                if (!empty($post_id)) {
                    $relationships[$post_id] = array();
                    
                    // Extract categories (taxonomies)
                    foreach ($item->category as $category) {
                        $domain = (string)$category['domain'];
                        $term_name = (string)$category;
                        
                        if (!empty($domain) && !empty($term_name)) {
                            if (!isset($relationships[$post_id][$domain])) {
                                $relationships[$post_id][$domain] = array();
                            }
                            
                            $relationships[$post_id][$domain][] = $term_name;
                        }
                    }
                    
                    // Extract thumbnail ID and other image attachments
                    foreach ($wp_item->postmeta as $meta) {
                        $meta_key = (string)$meta->children($namespaces['wp'])->meta_key;
                        $meta_value = (string)$meta->children($namespaces['wp'])->meta_value;
                        
                        // Featured image
                        if ($meta_key === '_thumbnail_id' && !empty($meta_value)) {
                            $relationships[$post_id]['_thumbnail_id'] = $meta_value;
                        }
                        
                        // Product gallery (for WooCommerce products)
                        if ($post_type === 'product' && $meta_key === '_product_image_gallery' && !empty($meta_value)) {
                            $relationships[$post_id]['_product_image_gallery'] = $meta_value;
                        }
                        
                        // Portfolio gallery (for portfolio items)
                        if ($post_type === 'portfolio' && $meta_key === '_portfolio_gallery' && !empty($meta_value)) {
                            $relationships[$post_id]['_portfolio_gallery'] = $meta_value;
                        }
                        
                        // Elementor data (for images in Elementor content)
                        if ($meta_key === '_elementor_data' && !empty($meta_value)) {
                            // Store the Elementor data for later processing
                            $relationships[$post_id]['_elementor_data'] = $meta_value;
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        mtl_debug_log('Error extracting relationships from XML: ' . $e->getMessage());
    }
    
    return $relationships;
}

/**
 * Filter XML file to include only items of a specific post type
 * 
 * @param string $xml_file Path to the original XML file
 * @param string $post_type The post type to filter for
 * @return string|false Path to the filtered XML file or false on failure
 */
function mtl_filter_xml_by_post_type($xml_file, $post_type) {
    $xml_content = file_get_contents($xml_file);
    
    if (!$xml_content) {
        mtl_debug_log('Failed to read XML file: ' . $xml_file);
        return false;
    }
    
    // Check if SimpleXML extension is available
    if (!class_exists('SimpleXMLElement')) {
        mtl_debug_log('SimpleXMLElement class not found. PHP SimpleXML extension may not be enabled.');
        return false;
    }
    
    // Load the XML
    try {
        $xml = simplexml_load_string($xml_content);
        
        if (!$xml) {
            mtl_debug_log('Failed to parse XML content');
            return false;
        }
    } catch (Exception $e) {
        mtl_debug_log('Error parsing XML: ' . $e->getMessage());
        return false;
    }
    
    // Create a new XML document
    try {
        $new_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/"></rss>');
        
        // Copy the channel element
        $new_channel = $new_xml->addChild('channel');
        
        // Copy basic channel information
        foreach ($xml->channel->children() as $child) {
            if ($child->getName() != 'item') {
                $node = dom_import_simplexml($child);
                $node = dom_import_simplexml($new_channel)->ownerDocument->importNode($node, true);
                dom_import_simplexml($new_channel)->appendChild($node);
            }
        }
    } catch (Exception $e) {
        mtl_debug_log('Error creating new XML document: ' . $e->getMessage());
        return false;
    }
    
    // First pass: collect all items of the specified post type and their IDs
    $post_ids = array();
    $attachment_ids = array();
    
    try {
        foreach ($xml->channel->item as $item) {
            $item_post_type = '';
            $item_id = 0;
            
            foreach ($item->children('wp', true) as $wp_child) {
                if ($wp_child->getName() === 'post_type') {
                    $item_post_type = (string)$wp_child;
                } elseif ($wp_child->getName() === 'post_id') {
                    $item_id = (int)$wp_child;
                }
            }
            
            if ($item_post_type === $post_type) {
                $post_ids[] = $item_id;
                
                // Check for thumbnail/featured image
                foreach ($item->children('wp', true)->postmeta as $meta) {
                    $meta_key = (string)$meta->children('wp', true)->meta_key;
                    $meta_value = (string)$meta->children('wp', true)->meta_value;
                    
                    if ($meta_key === '_thumbnail_id') {
                        $attachment_ids[] = (int)$meta_value;
                    }
                }
            }
        }
        
        // Second pass: add items of the specified post type and related attachments
        foreach ($xml->channel->item as $item) {
            $item_post_type = '';
            $item_id = 0;
            $item_parent = 0;
            
            foreach ($item->children('wp', true) as $wp_child) {
                if ($wp_child->getName() === 'post_type') {
                    $item_post_type = (string)$wp_child;
                } elseif ($wp_child->getName() === 'post_id') {
                    $item_id = (int)$wp_child;
                } elseif ($wp_child->getName() === 'post_parent') {
                    $item_parent = (int)$wp_child;
                }
            }
            
            // Include the item if:
            // 1. It's of the specified post type, or
            // 2. It's an attachment and its ID is in the attachment_ids array, or
            // 3. It's an attachment and its parent is in the post_ids array
            if ($item_post_type === $post_type || 
                ($item_post_type === 'attachment' && in_array($item_id, $attachment_ids)) ||
                ($item_post_type === 'attachment' && in_array($item_parent, $post_ids))) {
                
                try {
                    $node = dom_import_simplexml($item);
                    $node = dom_import_simplexml($new_channel)->ownerDocument->importNode($node, true);
                    dom_import_simplexml($new_channel)->appendChild($node);
                } catch (Exception $e) {
                    mtl_debug_log('Error importing node: ' . $e->getMessage());
                    // Continue with the next item
                    continue;
                }
            }
        }
    } catch (Exception $e) {
        mtl_debug_log('Error processing XML items: ' . $e->getMessage());
        return false;
    }
    
    // Save the new XML to a temporary file
    try {
        $temp_file = tempnam(sys_get_temp_dir(), 'mtl_import_');
        if (!$new_xml->asXML($temp_file)) {
            mtl_debug_log('Failed to save XML to temporary file');
            return false;
        }
        return $temp_file;
    } catch (Exception $e) {
        mtl_debug_log('Error saving XML to file: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create a post from template data
 */
function mtl_create_post_from_template($template_data) {
    // Check if the template data is valid
    if (empty($template_data) || !is_array($template_data)) {
        return new WP_Error('invalid_data', 'Invalid template data.');
    }
    
    // Get the title from the template data or use a default title
    $title = isset($template_data['title']) ? sanitize_text_field($template_data['title']) : 'Imported Post ' . date('Y-m-d H:i:s');
    
    // Create the post
    $post_args = array(
        'post_title'    => $title,
        'post_status'   => 'publish',
        'post_type'     => 'post',
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
    update_post_meta($post_id, '_elementor_template_type', 'post');
    update_post_meta($post_id, '_elementor_version', '3.6.0'); // Use appropriate version
    
    // If there are page settings, save them
    if (isset($template_data['page_settings'])) {
        update_post_meta($post_id, '_elementor_page_settings', $template_data['page_settings']);
    }
    
    // Set featured image if available
    if (isset($template_data['featured_image'])) {
        // Import the featured image from URL
        $attachment_id = mtl_download_and_import_image($template_data['featured_image']);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
            mtl_debug_log('Set featured image for post: ' . $title . ' (ID: ' . $post_id . ', Image ID: ' . $attachment_id . ')');
        } else {
            mtl_debug_log('Failed to set featured image for post: ' . $title . ' - ' . $attachment_id->get_error_message());
        }
    } 
    // Check for thumbnail if featured_image is not set
    else if (isset($template_data['thumbnail']) && !empty($template_data['thumbnail'])) {
        // Import the featured image from thumbnail URL
        $attachment_id = mtl_download_and_import_image($template_data['thumbnail']);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
            mtl_debug_log('Set featured image from thumbnail for post: ' . $title . ' (ID: ' . $post_id . ', Image ID: ' . $attachment_id . ')');
        } else {
            mtl_debug_log('Failed to set featured image from thumbnail for post: ' . $title . ' - ' . $attachment_id->get_error_message());
        }
    }
    
    // Set categories if available
    if (isset($template_data['categories']) && is_array($template_data['categories'])) {
        $term_ids = array();
        foreach ($template_data['categories'] as $category) {
            $term = term_exists($category, 'category');
            if (!$term) {
                $term = wp_insert_term($category, 'category');
            }
            if (!is_wp_error($term)) {
                $term_ids[] = is_array($term) ? $term['term_id'] : $term;
            }
        }
        if (!empty($term_ids)) {
            wp_set_post_categories($post_id, $term_ids);
        }
    }
    
    // Set tags if available
    if (isset($template_data['tags']) && is_array($template_data['tags'])) {
        $tag_ids = array();
        foreach ($template_data['tags'] as $tag) {
            $term = term_exists($tag, 'post_tag');
            if (!$term) {
                $term = wp_insert_term($tag, 'post_tag');
            }
            if (!is_wp_error($term)) {
                $tag_ids[] = is_array($term) ? $term['term_id'] : $term;
            }
        }
        if (!empty($tag_ids)) {
            wp_set_post_tags($post_id, $tag_ids);
        }
    }
    
    mtl_debug_log('Created post from template: ' . $title . ' (ID: ' . $post_id . ')');
    
    return $post_id;
}

/**
 * Process site settings from manifest file
 * 
 * @param array $manifest_data The manifest data
 * @return array Results of the processing
 */
function mtl_process_site_settings($manifest_data) {
    $results = array(
        'success' => array(),
        'errors' => array()
    );
    
    if (empty($manifest_data) || !isset($manifest_data['content']['page'])) {
        return $results;
    }
    
    $homepage_set = false;
    $blog_page_set = false;
    
    // First check if any page has show_on_front flag
    foreach ($manifest_data['content']['page'] as $page_id => $page_data) {
        if (isset($page_data['show_on_front']) && $page_data['show_on_front'] === true) {
            // Find the page by title
            $args = array(
                'post_type' => 'page',
                'post_status' => 'any',
                'posts_per_page' => 1,
                'title' => $page_data['title']
            );
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                $post = $query->posts[0];
                $local_post_id = $post->ID;
                
                // Set this page as the front page
                update_option('show_on_front', 'page');
                update_option('page_on_front', $local_post_id);
                
                $results['success'][] = sprintf(
                    'Set "%s" (ID: %d) as the static homepage',
                    $page_data['title'],
                    $local_post_id
                );
                
                mtl_debug_log("Set page {$page_data['title']} (ID: {$local_post_id}) as the static homepage");
                $homepage_set = true;
            }
        }
        
        // Check if this page should be set as the blog page
        if (isset($page_data['is_posts_page']) && $page_data['is_posts_page'] === true) {
            // Find the page by title
            $args = array(
                'post_type' => 'page',
                'post_status' => 'any',
                'posts_per_page' => 1,
                'title' => $page_data['title']
            );
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                $post = $query->posts[0];
                $local_post_id = $post->ID;
                
                // Set this page as the blog page
                update_option('page_for_posts', $local_post_id);
                
                $results['success'][] = sprintf(
                    'Set "%s" (ID: %d) as the blog page',
                    $page_data['title'],
                    $local_post_id
                );
                
                mtl_debug_log("Set page {$page_data['title']} (ID: {$local_post_id}) as the blog page");
                $blog_page_set = true;
            }
        }
    }
    
    // If no page with show_on_front flag, check for a page named "Home"
    if (!$homepage_set) {
        foreach ($manifest_data['content']['page'] as $page_id => $page_data) {
            if ($page_data['title'] === 'Home') {
                // Find the page by title
                $args = array(
                    'post_type' => 'page',
                    'post_status' => 'any',
                    'posts_per_page' => 1,
                    'title' => 'Home'
                );
                
                $query = new WP_Query($args);
                
                if ($query->have_posts()) {
                    $post = $query->posts[0];
                    $local_post_id = $post->ID;
                    
                    // Set this page as the front page
                    update_option('show_on_front', 'page');
                    update_option('page_on_front', $local_post_id);
                    
                    $results['success'][] = sprintf(
                        'Set "Home" page (ID: %d) as the static homepage',
                        $local_post_id
                    );
                    
                    mtl_debug_log("Set 'Home' page (ID: {$local_post_id}) as the static homepage");
                }
            }
        }
    }
    
    // If no blog page set, check for a page named "Blog"
    if (!$blog_page_set) {
        foreach ($manifest_data['content']['page'] as $page_id => $page_data) {
            if ($page_data['title'] === 'Blog') {
                // Find the page by title
                $args = array(
                    'post_type' => 'page',
                    'post_status' => 'any',
                    'posts_per_page' => 1,
                    'title' => 'Blog'
                );
                
                $query = new WP_Query($args);
                
                if ($query->have_posts()) {
                    $post = $query->posts[0];
                    $local_post_id = $post->ID;
                    
                    // Set this page as the blog page
                    update_option('page_for_posts', $local_post_id);
                    
                    $results['success'][] = sprintf(
                        'Set "Blog" page (ID: %d) as the blog page',
                        $local_post_id
                    );
                    
                    mtl_debug_log("Set 'Blog' page (ID: {$local_post_id}) as the blog page");
                }
            }
        }
    }
    
    // If we have widget data
    if (isset($data['sidebars_widgets']) && is_array($data['sidebars_widgets'])) {
        // The widgets data needs special handling
        update_option('sidebars_widgets', $data['sidebars_widgets']);
        mtl_debug_log('Updated sidebar widgets');
    }
    
    // FINAL CHECKS - Make absolutely sure logo and site icon are set
    mtl_ensure_logo_and_site_icon();
    
    return $results;
}

/**
 * Make sure logo and site icon are set
 */
function mtl_ensure_logo_and_site_icon() {
    // Check if we have a flag indicating user uploaded images
    $user_uploaded_logo = get_option('_mtl_user_uploaded_logo', false);
    $user_uploaded_icon = get_option('_mtl_user_uploaded_icon', false);
    
    // Check logo
    $logo_id = get_theme_mod('custom_logo');
    if (empty($logo_id) && !$user_uploaded_logo) {
        mtl_debug_log('No logo set after import, setting default logo');
        $logo_url = 'https://digo.iamabdus.com/v1-4/wp-content/uploads/2022/11/logo-digo-dark.png';
        $attachment_id = mtl_download_and_import_image($logo_url);
        if (!is_wp_error($attachment_id)) {
            set_theme_mod('custom_logo', $attachment_id);
            mtl_debug_log('Set default logo: ' . $attachment_id);
        }
    } else {
        mtl_debug_log('Logo already set, skipping default logo');
    }
    
    // Check site icon
    $site_icon = get_option('site_icon');
    if (empty($site_icon) && !$user_uploaded_icon) {
        mtl_debug_log('No site icon set after import, setting default icon');
        $icon_url = 'https://digo.iamabdus.com/v1-4/wp-content/uploads/2022/11/cropped-favicon-32x32.png';
        $attachment_id = mtl_download_and_import_image($icon_url);
        if (!is_wp_error($attachment_id)) {
            update_option('site_icon', $attachment_id);
            mtl_debug_log('Set default site icon: ' . $attachment_id);
        }
    } else {
        mtl_debug_log('Site icon already set, skipping default icon');
    }
    
    // Final check for site title and tagline
    $site_title = get_option('blogname');
    $site_tagline = get_option('blogdescription');
    
    if (empty($site_title)) {
        update_option('blogname', 'Digo - Digital Marketing Agency WordPress Theme');
        mtl_debug_log('Set default site title in final check');
    }
    
    if (empty($site_tagline)) {
        update_option('blogdescription', 'Digital Marketing WordPress Theme');
        mtl_debug_log('Set default site tagline in final check');
    }
}

/**
 * Fallback for direct access URL registration
 */
if (!function_exists('mtl_register_direct_access_url')) {
    function mtl_register_direct_access_url() {
        add_rewrite_rule(
            'mtl-direct-import/?$',
            'index.php?mtl_direct_import=1',
            'top'
        );
        
        add_rewrite_tag('%mtl_direct_import%', '([0-9]+)');
    }
}

/**
 * Add the direct import page to the admin menu
 */
function mtl_add_import_menu_page() {
    add_menu_page(
        'Template Import', // Page title
        'Template Import', // Menu title
        'edit_posts', // Capability
        'mtl-import', // Menu slug
        'mtl_direct_import_page_content', // Callback function
        'dashicons-upload', // Icon
        30 // Position
    );
}
// Removing the Template Import menu from the dashboard
// add_action('admin_menu', 'mtl_add_import_menu_page');

/**
 * Fallback for direct import page content
 */
if (!function_exists('mtl_direct_import_page_content')) {
    function mtl_direct_import_page_content() {
        // Check if the direct-import.php file exists and include it
        $direct_import_file = MTL_PLUGIN_DIR . 'includes/direct-import.php';
        if (file_exists($direct_import_file)) {
            require_once $direct_import_file;
            // If the function now exists, call it
            if (function_exists('mtl_direct_import_page_content')) {
                mtl_direct_import_page_content();
                return;
            }
        }
        
        // Fallback content if the function still doesn't exist
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Template Import', 'my-template-library'); ?></h1>
            
            <div class="notice notice-warning">
                <p><?php echo esc_html__('The direct import functionality is not fully loaded. Please check your plugin installation.', 'my-template-library'); ?></p>
            </div>
            
            <div class="card">
                <h2><?php echo esc_html__('Manual Import Instructions', 'my-template-library'); ?></h2>
                <p><?php echo esc_html__('To manually import templates:', 'my-template-library'); ?></p>
                <ol>
                    <li><?php echo esc_html__('Make sure the plugin is properly installed and activated.', 'my-template-library'); ?></li>
                    <li><?php echo esc_html__('Check that the includes/direct-import.php file exists.', 'my-template-library'); ?></li>
                    <li><?php echo esc_html__('Try deactivating and reactivating the plugin.', 'my-template-library'); ?></li>
                    <li><?php echo esc_html__('If issues persist, contact the plugin developer.', 'my-template-library'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }
}

/**
 * Import customizer settings from a .dat file
 * Based on the approach used by One Click Demo Import plugin
 */
function mtl_import_customizer_data($customizer_file) {
    if (!file_exists($customizer_file)) {
        return new WP_Error('missing_file', sprintf(__('Customizer import file does not exist at %s', 'my-template-library'), $customizer_file));
    }
    
    mtl_debug_log('Importing customizer data from: ' . $customizer_file);
    
    // Get the customizer data from the file
    $raw_data = file_get_contents($customizer_file);
    
    // Log the first 1000 characters of the raw data for debugging
    mtl_debug_log('Raw customizer data (first 1000 chars): ' . substr($raw_data, 0, 1000));
    
    // Try to unserialize the data
    $data = @unserialize($raw_data);
    if (!is_array($data)) {
        mtl_debug_log('Failed to unserialize data from ' . $customizer_file);
        return new WP_Error('invalid_data', __('Invalid customizer data in the import file.', 'my-template-library'));
    }
    
    mtl_debug_log('Customizer data structure: ' . print_r(array_keys($data), true));
    mtl_debug_log('Customizer data template: ' . (isset($data['template']) ? $data['template'] : 'Not set'));
    
    if (isset($data['mods'])) {
        mtl_debug_log('Customizer mods keys: ' . print_r(array_keys($data['mods']), true));
    }
    
    if (isset($data['options'])) {
        mtl_debug_log('Customizer options keys: ' . print_r(array_keys($data['options']), true));
    }
    
    // FORCE SITE TITLE AND TAGLINE - these are critical
    update_option('blogname', 'Digo - Digital Marketing Agency WordPress Theme');
    update_option('blogdescription', 'Digital Marketing WordPress Theme');
    mtl_debug_log('Forced site title and tagline to default values');
    
    // FORCE SITE ICON - Handle based on the known value from digo-kit
    if (isset($data['options']['site_icon']) && !empty($data['options']['site_icon'])) {
        $site_icon_id = intval($data['options']['site_icon']);
        mtl_debug_log('Found site icon ID in options: ' . $site_icon_id);
        
        // Create a placeholder image for the site icon
        $logo_url = plugin_dir_url(__FILE__) . 'assets/images/default-icon.png';
        
        // Check if the default icon exists
        if (!file_exists(plugin_dir_path(__FILE__) . 'assets/images/default-icon.png')) {
            // Fallback to a hard-coded URL for a common favicon
            $logo_url = 'https://digo.iamabdus.com/v1-4/wp-content/uploads/2022/11/logo-digo-dark.png';
        }
        
        $attachment_id = mtl_download_and_import_image($logo_url);
        if (!is_wp_error($attachment_id)) {
            update_option('site_icon', $attachment_id);
            mtl_debug_log('Set site icon to default image: ' . $attachment_id);
        }
    }
    
    $results = array(
        'status' => 'success',
        'message' => __('Customizer settings imported successfully', 'my-template-library')
    );
    
    // Import theme name
    if (isset($data['template'])) {
        $template = $data['template'];
        mtl_debug_log('Target theme for customizer import: ' . $template);
        
        // Check if the theme exists
        $theme_exists = wp_get_theme($template)->exists();
        
        if (!$theme_exists) {
            mtl_debug_log('Theme ' . $template . ' does not exist. Customizer settings will be saved for later.');
            
            // Save settings for later application
            update_option('mtl_pending_customizer_import', array(
                'theme' => $template,
                'data' => $data
            ));
            
            $results['status'] = 'pending';
            $results['message'] = sprintf(
                __('Theme %s is not currently active. Customizer settings have been saved and will be imported when the theme is activated.', 'my-template-library'),
                $template
            );
            
            return $results;
        }
    }
    
    // Process core WordPress settings from the options array
    if (isset($data['options']) && is_array($data['options'])) {
        // Process site title and tagline
        if (isset($data['options']['blogname'])) {
            update_option('blogname', sanitize_text_field($data['options']['blogname']));
            mtl_debug_log('Updated site title from options');
        } else {
            // Check for site title in theme-specific options
            $found_site_title = false;
            foreach ($data['options'] as $key => $value) {
                if (is_string($value) && (strpos($key, 'site_title') !== false || strpos($key, 'sitename') !== false)) {
                    update_option('blogname', sanitize_text_field($value));
                    mtl_debug_log('Updated site title from theme-specific option: ' . $key);
                    $found_site_title = true;
                    break;
                }
            }
            
            if (!$found_site_title) {
                // Check theme mods too
                foreach ($data['mods'] as $key => $value) {
                    if (is_string($value) && (strpos($key, 'site_title') !== false || strpos($key, 'sitename') !== false)) {
                        update_option('blogname', sanitize_text_field($value));
                        mtl_debug_log('Updated site title from theme mod: ' . $key);
                        $found_site_title = true;
                        break;
                    }
                }
            }
            
            if (!$found_site_title) {
                // Try to preserve the existing site title since it's not in the imported data
                $site_title = get_option('blogname');
                mtl_debug_log('Site title not found in import data. Current title: ' . $site_title);
            }
        }
        
        if (isset($data['options']['blogdescription'])) {
            update_option('blogdescription', sanitize_text_field($data['options']['blogdescription']));
            mtl_debug_log('Updated site tagline from options');
        } else {
            // Check for tagline in theme-specific options
            $found_tagline = false;
            foreach ($data['options'] as $key => $value) {
                if (is_string($value) && (strpos($key, 'tagline') !== false || strpos($key, 'description') !== false)) {
                    update_option('blogdescription', sanitize_text_field($value));
                    mtl_debug_log('Updated site tagline from theme-specific option: ' . $key);
                    $found_tagline = true;
                    break;
                }
            }
            
            if (!$found_tagline) {
                // Check theme mods too
                foreach ($data['mods'] as $key => $value) {
                    if (is_string($value) && (strpos($key, 'tagline') !== false || strpos($key, 'description') !== false)) {
                        update_option('blogdescription', sanitize_text_field($value));
                        mtl_debug_log('Updated site tagline from theme mod: ' . $key);
                        $found_tagline = true;
                        break;
                    }
                }
            }
            
            if (!$found_tagline) {
                // Try to preserve the existing site tagline since it's not in the imported data
                $site_tagline = get_option('blogdescription');
                mtl_debug_log('Site tagline not found in import data. Current tagline: ' . $site_tagline);
            }
        }
        
        // Process site icon
        if (isset($data['options']['site_icon']) && !empty($data['options']['site_icon'])) {
            $site_icon_id = intval($data['options']['site_icon']);
            
            // Check if we need to download the site icon from a URL
            if (isset($data['options']['site_icon_url']) && !empty($data['options']['site_icon_url'])) {
                $attachment_id = mtl_download_and_import_image($data['options']['site_icon_url']);
                if (!is_wp_error($attachment_id)) {
                    update_option('site_icon', $attachment_id);
                    mtl_debug_log('Updated site icon from URL');
                }
            } else {
                // Try to find the site icon from its ID
                $attachment = get_post($site_icon_id);
                if ($attachment && $attachment->post_type === 'attachment') {
                    mtl_debug_log('Found existing site icon attachment: ' . $attachment->post_title);
                    // Site icon already exists, we can use it
                } else {
                    // Site icon not found by ID, try to find it elsewhere
                    mtl_debug_log('Site icon attachment not found with ID: ' . $site_icon_id . '. Looking for URLs.');
                    
                    // Search for potential site icon URLs in the data
                    $icon_url = null;
                    
                    // Search in theme mods
                    foreach ($data['mods'] as $key => $value) {
                        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL) && 
                            (strpos(strtolower($key), 'icon') !== false || strpos(strtolower($key), 'favicon') !== false)) {
                            $icon_url = $value;
                            mtl_debug_log('Found potential site icon URL in theme mods: ' . $icon_url);
                            break;
                        }
                    }
                    
                    // If no URL found in theme mods, try options
                    if (!$icon_url) {
                        foreach ($data['options'] as $key => $value) {
                            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL) && 
                                (strpos(strtolower($key), 'icon') !== false || strpos(strtolower($key), 'favicon') !== false)) {
                                $icon_url = $value;
                                mtl_debug_log('Found potential site icon URL in options: ' . $icon_url);
                                break;
                            }
                        }
                    }
                    
                    // If we found a URL, import it
                    if ($icon_url) {
                        $attachment_id = mtl_download_and_import_image($icon_url);
                        if (!is_wp_error($attachment_id)) {
                            update_option('site_icon', $attachment_id);
                            mtl_debug_log('Updated site icon from found URL, new ID: ' . $attachment_id);
                        } else {
                            mtl_debug_log('Failed to import site icon: ' . $attachment_id->get_error_message());
                        }
                    } else {
                        // No URL found, use the ID anyway as a last resort
                        update_option('site_icon', $site_icon_id);
                        mtl_debug_log('Using original site icon ID from data: ' . $site_icon_id);
                    }
                }
            }
        }
    }
    
    // If we have theme mods
    if (isset($data['mods']) && is_array($data['mods'])) {
        // Delete existing theme mods - but first save the ones we want to preserve
        $current_mods = get_theme_mods();
        remove_theme_mods();
        
        // FORCE CUSTOM LOGO - Direct handling for Digo theme
        if (isset($data['mods']['custom_logo'])) {
            mtl_debug_log('Found custom logo ID in theme mods: ' . $data['mods']['custom_logo']);
            
            // Use a known good URL for the logo
            $logo_url = 'https://digo.iamabdus.com/v1-4/wp-content/uploads/2022/11/logo-digo-dark.png';
            
            // Download and import the logo
            $attachment_id = mtl_download_and_import_image($logo_url);
            if (!is_wp_error($attachment_id)) {
                $data['mods']['custom_logo'] = $attachment_id;
                mtl_debug_log('Set custom logo to default image: ' . $attachment_id);
            } else {
                mtl_debug_log('Failed to import logo: ' . $attachment_id->get_error_message());
            }
        }
        
        // Process special cases for logo, custom header, etc.
        $processed_image_mods = array();
        
        // Handle custom logo
        if (isset($data['mods']['custom_logo']) && !empty($data['mods']['custom_logo'])) {
            $logo_id = intval($data['mods']['custom_logo']);
            
            // If we have a logo URL in the data, download and import it
            if (isset($data['mods']['custom_logo_url']) && !empty($data['mods']['custom_logo_url'])) {
                $attachment_id = mtl_download_and_import_image($data['mods']['custom_logo_url']);
                if (!is_wp_error($attachment_id)) {
                    $data['mods']['custom_logo'] = $attachment_id;
                    mtl_debug_log('Imported custom logo from URL, new ID: ' . $attachment_id);
                    $processed_image_mods[] = 'custom_logo';
                }
            } else {
                // For digo-kit: Try to find the logo using the attachment ID from the .dat file
                mtl_debug_log('Attempting to find existing logo attachment with ID: ' . $logo_id);
                
                // First try to get the attachment directly
                $attachment = get_post($logo_id);
                if ($attachment && $attachment->post_type === 'attachment') {
                    mtl_debug_log('Found existing logo attachment: ' . $attachment->post_title);
                    // Logo already exists, we can use it
                    $processed_image_mods[] = 'custom_logo';
                } else {
                    // Logo not found, we need to create it
                    mtl_debug_log('Logo attachment not found. Checking if there is a corresponding image URL in the options.');
                    
                    // Check if there's a URL for this logo somewhere in the options
                    $logo_url = null;
                    
                    // Look for common logo URL patterns in options
                    if (isset($data['options']['site_logo']) && filter_var($data['options']['site_logo'], FILTER_VALIDATE_URL)) {
                        $logo_url = $data['options']['site_logo'];
                    }
                    
                    if (!$logo_url && isset($data['options']['header_image']) && $data['options']['header_image'] !== 'remove-header') {
                        $logo_url = $data['options']['header_image'];
                    }
                    
                    // Look for URLs in other mods that might be the logo
                    foreach ($data['mods'] as $mod_key => $mod_value) {
                        if (is_string($mod_value) && filter_var($mod_value, FILTER_VALIDATE_URL) && 
                            (strpos($mod_key, 'logo') !== false || strpos($mod_key, 'header') !== false)) {
                            $logo_url = $mod_value;
                            break;
                        }
                    }
                    
                    if ($logo_url) {
                        mtl_debug_log('Found potential logo URL: ' . $logo_url);
                        $attachment_id = mtl_download_and_import_image($logo_url);
                        if (!is_wp_error($attachment_id)) {
                            $data['mods']['custom_logo'] = $attachment_id;
                            mtl_debug_log('Imported custom logo from URL, new ID: ' . $attachment_id);
                            $processed_image_mods[] = 'custom_logo';
                        }
                    } else {
                        mtl_debug_log('No logo URL found in options or theme mods.');
                    }
                }
            }
        }
        
        // Handle site icon via theme_mod (some themes use this)
        if (isset($data['mods']['site_icon']) && !empty($data['mods']['site_icon'])) {
            $site_icon_id = intval($data['mods']['site_icon']);
            
            // If we have a site icon URL in the data, download and import it
            if (isset($data['mods']['site_icon_url']) && !empty($data['mods']['site_icon_url'])) {
                $attachment_id = mtl_download_and_import_image($data['mods']['site_icon_url']);
                if (!is_wp_error($attachment_id)) {
                    $data['mods']['site_icon'] = $attachment_id;
                    update_option('site_icon', $attachment_id); // Update the WP option too
                    mtl_debug_log('Imported site icon from URL, new ID: ' . $attachment_id);
                    $processed_image_mods[] = 'site_icon';
                }
            }
        }

        // Handle preloader image
        $preloader_image_keys = array(
            'digo_preloader_image_setting',
            'preloader_image',
            'preloader_image_setting',
            'site_preloader_image'
        );
        
        foreach ($preloader_image_keys as $key) {
            if (isset($data['mods'][$key]) && !empty($data['mods'][$key])) {
                // Check if it's a URL
                if (filter_var($data['mods'][$key], FILTER_VALIDATE_URL)) {
                    mtl_debug_log('Found preloader image URL: ' . $data['mods'][$key]);
                    $attachment_id = mtl_download_and_import_image($data['mods'][$key]);
                    if (!is_wp_error($attachment_id)) {
                        // Get the URL of the imported image
                        $image_url = wp_get_attachment_url($attachment_id);
                        if ($image_url) {
                            $data['mods'][$key] = $image_url;
                            mtl_debug_log('Imported preloader image from URL: ' . $image_url);
                            $processed_image_mods[] = $key;
                        }
                    } else {
                        mtl_debug_log('Failed to import preloader image: ' . $attachment_id->get_error_message());
                    }
                }
            }
        }
        
        // Specifically look for and set Digo theme preloader settings
        if (isset($data['mods']['enable_site_preloader'])) {
            mtl_debug_log('Found Digo theme preloader setting: ' . $data['mods']['enable_site_preloader']);
            // Make sure the preloader is enabled
            set_theme_mod('enable_site_preloader', 1);
        }
        
        if (isset($data['mods']['enable_site_preloader_for_home_page'])) {
            mtl_debug_log('Found Digo theme preloader setting for home page: ' . $data['mods']['enable_site_preloader_for_home_page']);
            set_theme_mod('enable_site_preloader_for_home_page', $data['mods']['enable_site_preloader_for_home_page']);
        }
        
        if (isset($data['mods']['digo_preloader_background_color'])) {
            mtl_debug_log('Found Digo theme preloader background color: ' . $data['mods']['digo_preloader_background_color']);
            set_theme_mod('digo_preloader_background_color', $data['mods']['digo_preloader_background_color']);
        }

        // Handle background image
        if (isset($data['mods']['background_image']) && !empty($data['mods']['background_image'])) {
            if (filter_var($data['mods']['background_image'], FILTER_VALIDATE_URL)) {
                $attachment_id = mtl_download_and_import_image($data['mods']['background_image']);
                if (!is_wp_error($attachment_id)) {
                    // Get the URL of the imported image
                    $image_url = wp_get_attachment_url($attachment_id);
                    if ($image_url) {
                        $data['mods']['background_image'] = $image_url;
                        mtl_debug_log('Imported background image from URL: ' . $image_url);
                        $processed_image_mods[] = 'background_image';
                    }
                }
            }
        }

        // Handle header image
        if (isset($data['mods']['header_image']) && !empty($data['mods']['header_image']) 
            && $data['mods']['header_image'] !== 'remove-header') {
            if (filter_var($data['mods']['header_image'], FILTER_VALIDATE_URL)) {
                $attachment_id = mtl_download_and_import_image($data['mods']['header_image']);
                if (!is_wp_error($attachment_id)) {
                    // Get the URL of the imported image
                    $image_url = wp_get_attachment_url($attachment_id);
                    if ($image_url) {
                        $data['mods']['header_image'] = $image_url;
                        $data['mods']['header_image_data'] = (object) array('url' => $image_url);
                        mtl_debug_log('Imported header image from URL: ' . $image_url);
                        $processed_image_mods[] = 'header_image';
                    }
                }
            }
        }

        // Process any other image URLs in theme mods
        foreach ($data['mods'] as $key => $value) {
            // Skip already processed image mods
            if (in_array($key, $processed_image_mods)) {
                continue;
            }
            
            // Handle image URLs
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL) 
                && preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $value)) {
                // This looks like an image URL, try to import it
                $attachment_id = mtl_download_and_import_image($value);
                if (!is_wp_error($attachment_id)) {
                    // Get the URL of the imported image
                    $image_url = wp_get_attachment_url($attachment_id);
                    if ($image_url) {
                        $data['mods'][$key] = $image_url;
                        mtl_debug_log('Imported image from URL for ' . $key . ': ' . $image_url);
                    }
                }
            }
            
            // Handle array values that might contain URLs (like background_image_thumb)
            if (is_array($value)) {
                foreach ($value as $sub_key => $sub_value) {
                    if (is_string($sub_value) && filter_var($sub_value, FILTER_VALIDATE_URL) 
                        && preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $sub_value)) {
                        $attachment_id = mtl_download_and_import_image($sub_value);
                        if (!is_wp_error($attachment_id)) {
                            $image_url = wp_get_attachment_url($attachment_id);
                            if ($image_url) {
                                $data['mods'][$key][$sub_key] = $image_url;
                                mtl_debug_log('Imported image from URL for ' . $key . '[' . $sub_key . ']: ' . $image_url);
                            }
                        }
                    }
                }
            }
        }
        
        // Use a loop to add each theme mod one by one (more reliable)
        foreach ($data['mods'] as $key => $value) {
            // Apply the theme mod with error handling
            set_theme_mod($key, $value);
        }
        
        mtl_debug_log('Applied ' . count($data['mods']) . ' theme mods');
    }
    
    // If we have options
    if (isset($data['options']) && is_array($data['options'])) {
        foreach ($data['options'] as $option_key => $option_value) {
            // Handle custom CSS option specially
            if ($option_key === 'wp_css' && !empty($option_value)) {
                wp_update_custom_css_post($option_value);
                mtl_debug_log('Updated custom CSS');
                continue;
            }
            
            // Skip blogname and blogdescription as we've handled them above
            if (in_array($option_key, array('blogname', 'blogdescription', 'site_icon'))) {
                continue;
            }
            
            // Update other options
            update_option($option_key, $option_value);
        }
        
        mtl_debug_log('Applied ' . count($data['options']) . ' options');
    }
    
    // If we have widget data
    if (isset($data['sidebars_widgets']) && is_array($data['sidebars_widgets'])) {
        // The widgets data needs special handling
        update_option('sidebars_widgets', $data['sidebars_widgets']);
        mtl_debug_log('Updated sidebar widgets');
    }
    
    // FINAL CHECKS - Make absolutely sure logo and site icon are set
    mtl_ensure_logo_and_site_icon();
    
    return $results;
}

/**
 * Helper function to download and import an image from a URL
 */
function mtl_download_and_import_image($url) {
    if (empty($url)) {
        return new WP_Error('empty_url', __('Empty image URL provided', 'my-template-library'));
    }
    
    // Check if the URL is valid
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return new WP_Error('invalid_url', __('Invalid image URL provided', 'my-template-library'));
    }
    
    // Get the file name from the URL
    $file_name = basename(parse_url($url, PHP_URL_PATH));
    
    // Remove query strings if any
    $file_name = preg_replace('/\?.*/', '', $file_name);
    
    // Check if this image already exists in the media library
    $existing_attachment = get_page_by_title($file_name, OBJECT, 'attachment');
    
    if ($existing_attachment) {
        return $existing_attachment->ID;
    }
    
    // Get the file and save it
    $tmp_file = download_url($url);
    
    if (is_wp_error($tmp_file)) {
        return $tmp_file;
    }
    
    // Prepare file data for wp_handle_sideload
    $file_data = array(
        'name'     => $file_name,
        'tmp_name' => $tmp_file
    );
    
    // Move the temporary file to the uploads directory
    $results = wp_handle_sideload(
        $file_data,
        array('test_form' => false)
    );
    
    if (!empty($results['error'])) {
        @unlink($tmp_file); // Delete the temp file
        return new WP_Error('upload_error', $results['error']);
    }
    
    // Insert the image as an attachment
    $attachment = array(
        'guid'           => $results['url'],
        'post_mime_type' => $results['type'],
        'post_title'     => preg_replace('/\.[^.]+$/', '', $file_name),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    
    $attachment_id = wp_insert_attachment($attachment, $results['file']);
    
    if (is_wp_error($attachment_id)) {
        @unlink($results['file']); // Delete the file
        return $attachment_id;
    }
    
    // Generate metadata for the attachment
    $attachment_data = wp_generate_attachment_metadata($attachment_id, $results['file']);
    wp_update_attachment_metadata($attachment_id, $attachment_data);
    
    return $attachment_id;
}

/**
 * Hook for theme activation to apply pending customizer data
 */
function mtl_apply_pending_customizer_import() {
    $pending_import = get_option('mtl_pending_customizer_import', false);
    
    if (!$pending_import || !is_array($pending_import)) {
        return;
    }
    
    $theme = $pending_import['theme'];
    $data = $pending_import['data'];
    
    // Check if this is the theme we're waiting for
    $current_theme = get_option('template');
    
    if ($current_theme === $theme) {
        mtl_debug_log('Applying pending customizer import for theme: ' . $theme);
        
        // Simulate a direct import
        if (isset($data['mods'])) {
            // Delete existing theme mods
            remove_theme_mods();
            
            // Add each theme mod
            foreach ($data['mods'] as $key => $value) {
                set_theme_mod($key, $value);
            }
        }
        
        // Import options
        if (isset($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $option_key => $option_value) {
                if ($option_key === 'wp_css' && !empty($option_value)) {
                    wp_update_custom_css_post($option_value);
                    continue;
                }
                
                update_option($option_key, $option_value);
            }
        }
        
        // Import widget data
        if (isset($data['sidebars_widgets'])) {
            update_option('sidebars_widgets', $data['sidebars_widgets']);
        }
        
        // Clear the pending import
        delete_option('mtl_pending_customizer_import');
        
        mtl_debug_log('Successfully applied pending customizer import');
    }
}
add_action('after_switch_theme', 'mtl_apply_pending_customizer_import');

/**
 * Install and activate a theme from a ZIP file
 * 
 * @param string $zip_file Path to the theme ZIP file
 * @return array Result of the operation with status and message
 */
function mtl_install_theme_from_zip($zip_file) {
    if (!file_exists($zip_file)) {
        return array(
            'success' => false,
            'message' => sprintf(__('Theme ZIP file not found at %s', 'my-template-library'), $zip_file)
        );
    }
    
    mtl_debug_log('Installing theme from ZIP file: ' . $zip_file);
    
    // Check if we have the necessary file system access
    if (!function_exists('WP_Filesystem')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    
    WP_Filesystem();
    
    // Include the Theme_Upgrader class
    if (!class_exists('Theme_Upgrader')) {
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/theme-install.php');
        require_once(ABSPATH . 'wp-admin/includes/class-theme-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/theme.php');
    }
    
    // Create an object of WP_Theme_Install_Skin
    $skin = new WP_Ajax_Upgrader_Skin();
    
    // Create an object of Theme_Upgrader
    $upgrader = new Theme_Upgrader($skin);
    
    // Install the theme
    $result = $upgrader->install($zip_file);
    
    if (is_wp_error($result)) {
        mtl_debug_log('Theme installation failed: ' . $result->get_error_message());
        return array(
            'success' => false,
            'message' => $result->get_error_message()
        );
    }
    
    if (!$result) {
        $error = $skin->get_errors();
        $error_message = is_wp_error($error) ? $error->get_error_message() : __('Unknown error during theme installation', 'my-template-library');
        mtl_debug_log('Theme installation failed: ' . $error_message);
        return array(
            'success' => false,
            'message' => $error_message
        );
    }
    
    // Get the installed theme directory
    $theme_info = $upgrader->theme_info();
    if (!$theme_info) {
        mtl_debug_log('Theme installed but unable to get theme info');
        return array(
            'success' => true,
            'activated' => false,
            'message' => __('Theme installed but unable to activate automatically', 'my-template-library')
        );
    }
    
    $theme_name = $theme_info->get('Name');
    $theme_stylesheet = $theme_info->get_stylesheet();
    
    // Activate the theme
    switch_theme($theme_stylesheet);
    
    mtl_debug_log('Theme installed and activated successfully: ' . $theme_name);
    
    return array(
        'success' => true,
        'activated' => true,
        'theme_name' => $theme_name,
        'stylesheet' => $theme_stylesheet,
        'message' => sprintf(__('Theme "%s" installed and activated successfully', 'my-template-library'), $theme_name)
    );
}

// Register AJAX handlers for logo and icon uploads
add_action('wp_ajax_mtl_upload_site_logo', 'mtl_ajax_upload_site_logo');
add_action('wp_ajax_mtl_upload_site_icon', 'mtl_ajax_upload_site_icon');

/**
 * AJAX handler for uploading site logo
 */
function mtl_ajax_upload_site_logo() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mtl_plugin_installation_nonce')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
    }
    
    // Check if user has permission
    if (!current_user_can('edit_theme_options')) {
        wp_send_json_error(['message' => 'You do not have permission to upload a logo.']);
    }
    
    // Check if file is uploaded
    if (empty($_FILES['logo'])) {
        wp_send_json_error(['message' => 'No file was uploaded.']);
    }
    
    $file = $_FILES['logo'];
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Upload error: ';
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message .= 'File is too large.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message .= 'File was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message .= 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message .= 'Missing temporary folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message .= 'Failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message .= 'A PHP extension stopped the file upload.';
                break;
            default:
                $error_message .= 'Unknown error.';
        }
        wp_send_json_error(['message' => $error_message]);
    }
    
    // Check file type
    $file_type = wp_check_filetype($file['name'], ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp']);
    if (!$file_type['type']) {
        wp_send_json_error(['message' => 'Invalid file type. Please upload a valid image file (JPG, PNG, GIF, or WEBP).']);
    }
    
    // Prepare for upload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    // Handle the upload
    $attachment_id = media_handle_upload('logo', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(['message' => 'Upload failed: ' . $attachment_id->get_error_message()]);
    }
    
    // Get attachment URL
    $attachment_url = wp_get_attachment_url($attachment_id);
    
    // Get current theme
    $theme = wp_get_theme();
    
    // Set as custom logo using multiple methods for reliability
    set_theme_mod('custom_logo', $attachment_id);
    
    // Store the attachment ID in a separate option for backup
    update_option('_mtl_user_uploaded_logo_id', $attachment_id);
    
    // Force update using direct database approach for all available themes
    $available_themes = wp_get_themes();
    foreach ($available_themes as $theme_key => $theme_obj) {
        $theme_mods = get_option('theme_mods_' . $theme_key, []);
        if (is_array($theme_mods)) {
            $theme_mods['custom_logo'] = $attachment_id;
            update_option('theme_mods_' . $theme_key, $theme_mods);
        }
    }
    
    // Also update for the active theme specifically
    $active_theme = get_option('stylesheet');
    if ($active_theme) {
        $active_theme_mods = get_option('theme_mods_' . $active_theme, []);
        if (is_array($active_theme_mods)) {
            $active_theme_mods['custom_logo'] = $attachment_id;
            update_option('theme_mods_' . $active_theme, $active_theme_mods);
        }
    }
    
    // If WP Customizer is available, try to use it directly
    if (class_exists('WP_Customize_Manager')) {
        try {
            global $wp_customize;
            if (!$wp_customize) {
                $wp_customize = new WP_Customize_Manager();
            }
            $wp_customize->set_post_value('custom_logo', $attachment_id);
            $wp_customize->save_changeset_post(['status' => 'publish']);
        } catch (Exception $e) {
            mtl_debug_log('Error using WP_Customize_Manager for logo: ' . $e->getMessage());
        }
    }
    
    // Try to force WordPress to flush its caches
    wp_cache_delete('alloptions', 'options');
    wp_cache_delete('theme_mods_' . $theme->get_stylesheet(), 'options');
    
    // Set flag to indicate user uploaded logo
    update_option('_mtl_user_uploaded_logo', true);
    
    // Log success
    mtl_debug_log('Logo uploaded and set as custom_logo: ' . $attachment_id);
    
    // Return success
    wp_send_json_success([
        'attachment_id' => $attachment_id,
        'attachment_url' => $attachment_url,
        'message' => 'Logo uploaded successfully.'
    ]);
}

/**
 * AJAX handler for uploading site icon
 */
function mtl_ajax_upload_site_icon() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mtl_plugin_installation_nonce')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
    }
    
    // Check if user has permission
    if (!current_user_can('edit_theme_options')) {
        wp_send_json_error(['message' => 'You do not have permission to upload a site icon.']);
    }
    
    // Check if file is uploaded
    if (empty($_FILES['icon'])) {
        wp_send_json_error(['message' => 'No file was uploaded.']);
    }
    
    $file = $_FILES['icon'];
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Upload error: ';
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message .= 'File is too large.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message .= 'File was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message .= 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message .= 'Missing temporary folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message .= 'Failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message .= 'A PHP extension stopped the file upload.';
                break;
            default:
                $error_message .= 'Unknown error.';
        }
        wp_send_json_error(['message' => $error_message]);
    }
    
    // Check file type
    $file_type = wp_check_filetype($file['name'], ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'ico' => 'image/x-icon']);
    if (!$file_type['type']) {
        wp_send_json_error(['message' => 'Invalid file type. Please upload a valid image file (JPG, PNG, or ICO).']);
    }
    
    // Prepare for upload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    // Handle the upload
    $attachment_id = media_handle_upload('icon', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(['message' => 'Upload failed: ' . $attachment_id->get_error_message()]);
    }
    
    // Get attachment URL
    $attachment_url = wp_get_attachment_url($attachment_id);
    
    // Set as site icon using multiple methods for reliability
    update_option('site_icon', $attachment_id);
    
    // Store the attachment ID in a separate option for backup
    update_option('_mtl_user_uploaded_icon_id', $attachment_id);
    
    // If this is a multisite, update the network option too
    if (is_multisite()) {
        update_network_option(get_current_network_id(), 'site_icon', $attachment_id);
    }
    
    // Try the direct theme mod approach in addition to the option
    $active_theme = get_option('stylesheet');
    if ($active_theme) {
        $theme_mods = get_option('theme_mods_' . $active_theme, []);
        if (is_array($theme_mods)) {
            $theme_mods['site_icon'] = $attachment_id;
            update_option('theme_mods_' . $active_theme, $theme_mods);
        }
    }
    
    // Try using the Customizer API directly
    if (class_exists('WP_Customize_Manager')) {
        try {
            global $wp_customize;
            if (!$wp_customize) {
                $wp_customize = new WP_Customize_Manager();
            }
            $wp_customize->set_post_value('site_icon', $attachment_id);
            $wp_customize->save_changeset_post(['status' => 'publish']);
        } catch (Exception $e) {
            mtl_debug_log('Error using WP_Customize_Manager for site icon: ' . $e->getMessage());
        }
    }
    
    // Additionally use the WP Site Icon API if available
    if (function_exists('has_site_icon') && function_exists('update_option')) {
        update_option('site_icon', $attachment_id);
        // Force refresh any caches
        delete_option('site_icon_meta');
    }
    
    // Try to force WordPress to flush its caches
    wp_cache_delete('alloptions', 'options');
    
    // Set flag to indicate user uploaded icon
    update_option('_mtl_user_uploaded_icon', true);
    
    // Log success
    mtl_debug_log('Site icon uploaded and set: ' . $attachment_id);
    
    // Return success
    wp_send_json_success([
        'attachment_id' => $attachment_id,
        'attachment_url' => $attachment_url,
        'message' => 'Site icon uploaded successfully and set as your site favicon.'
    ]);
}

/**
 * AJAX handler for direct updates to the customizer settings
 * This provides a more forceful approach to setting the customizer values
 */
function mtl_ajax_direct_customizer_update() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mtl_plugin_installation_nonce')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
    }
    
    // Check if user has permission
    if (!current_user_can('edit_theme_options')) {
        wp_send_json_error(['message' => 'You do not have permission to modify theme settings.']);
    }
    
    $update_logo = isset($_POST['update_logo']) && $_POST['update_logo'] === 'true';
    $update_icon = isset($_POST['update_icon']) && $_POST['update_icon'] === 'true';
    $success = true;
    $messages = [];
    
    // Get the current theme
    $theme = wp_get_theme();
    
    // Handle logo direct update
    if ($update_logo) {
        // First try to get from theme mod
        $logo_id = get_theme_mod('custom_logo');
        
        // If not found, try to get from options
        if (!$logo_id) {
            $logo_id = get_option('_mtl_user_uploaded_logo_id');
        }
        
        // If we have a logo ID, apply it to the customizer directly
        if ($logo_id) {
            // Force update using direct database approach
            update_option('theme_mods_' . $theme->get_stylesheet(), array_merge(
                get_option('theme_mods_' . $theme->get_stylesheet(), []),
                ['custom_logo' => $logo_id]
            ));
            
            // Also set the theme mod directly
            set_theme_mod('custom_logo', $logo_id);
            
            // For extra insurance, save to the database
            $GLOBALS['wp_customize'] = new WP_Customize_Manager();
            $GLOBALS['wp_customize']->set_post_value('custom_logo', $logo_id);
            $GLOBALS['wp_customize']->save_changeset_post();
            
            $messages[] = 'Logo applied using direct database approach.';
        } else {
            $success = false;
            $messages[] = 'No logo ID found for direct update.';
        }
    }
    
    // Handle icon direct update
    if ($update_icon) {
        // First try to get from option
        $icon_id = get_option('site_icon');
        
        // If not found, try to get from backup
        if (!$icon_id) {
            $icon_id = get_option('_mtl_user_uploaded_icon_id');
        }
        
        // If we have an icon ID, apply it directly
        if ($icon_id) {
            // Force update via direct option
            update_option('site_icon', $icon_id);
            
            // For extra insurance, save to the database
            if (isset($GLOBALS['wp_customize'])) {
                $GLOBALS['wp_customize']->set_post_value('site_icon', $icon_id);
                $GLOBALS['wp_customize']->save_changeset_post();
            } else {
                $GLOBALS['wp_customize'] = new WP_Customize_Manager();
                $GLOBALS['wp_customize']->set_post_value('site_icon', $icon_id);
                $GLOBALS['wp_customize']->save_changeset_post();
            }
            
            $messages[] = 'Site icon applied using direct database approach.';
        } else {
            $success = false;
            $messages[] = 'No site icon ID found for direct update.';
        }
    }
    
    // Log the process
    mtl_debug_log('Direct customizer update: ' . implode(' | ', $messages));
    
    if ($success) {
        wp_send_json_success([
            'message' => 'Direct customizer update completed.',
            'details' => $messages
        ]);
    } else {
        wp_send_json_error([
            'message' => 'There were issues with the direct customizer update.',
            'details' => $messages
        ]);
    }
}

/**
 * Ensures that the logo and site icon are properly set in the customizer
 * This function forces WordPress to update all related settings and flush caches
 */
function mtl_ensure_customizer_settings() {
    // Force the site to refresh customizer settings
    
    // For logo
    $logo_id = get_option('_mtl_user_uploaded_logo_id');
    if ($logo_id) {
        mtl_debug_log('Ensuring custom logo is set with ID: ' . $logo_id);
        
        // Set for current theme
        set_theme_mod('custom_logo', $logo_id);
        
        // Set for all available themes
        $available_themes = wp_get_themes();
        foreach ($available_themes as $theme_key => $theme_obj) {
            $theme_mods = get_option('theme_mods_' . $theme_key, []);
            if (is_array($theme_mods)) {
                $theme_mods['custom_logo'] = $logo_id;
                update_option('theme_mods_' . $theme_key, $theme_mods);
            }
        }
        
        // Set specifically for active theme
        $active_theme = get_option('stylesheet');
        if ($active_theme) {
            $theme_mods = get_option('theme_mods_' . $active_theme, []);
            if (is_array($theme_mods)) {
                $theme_mods['custom_logo'] = $logo_id;
                update_option('theme_mods_' . $active_theme, $theme_mods);
            }
        }
        
        // Try direct database access as last resort
        global $wpdb;
        $wpdb->update(
            $wpdb->options,
            ['option_value' => maybe_serialize(['custom_logo' => $logo_id])],
            ['option_name' => 'theme_mods_' . $active_theme],
            ['%s'],
            ['%s']
        );
    }
    
    // For site icon
    $icon_id = get_option('_mtl_user_uploaded_icon_id');
    if ($icon_id) {
        mtl_debug_log('Ensuring site icon is set with ID: ' . $icon_id);
        
        // Set the site icon
        update_option('site_icon', $icon_id);
        
        // Force refresh site icon meta
        delete_option('site_icon_meta');
        
        // Set in all theme mods too
        $active_theme = get_option('stylesheet');
        if ($active_theme) {
            $theme_mods = get_option('theme_mods_' . $active_theme, []);
            if (is_array($theme_mods)) {
                $theme_mods['site_icon'] = $icon_id;
                update_option('theme_mods_' . $active_theme, $theme_mods);
            }
        }
        
        // Try direct database access
        global $wpdb;
        $wpdb->update(
            $wpdb->options,
            ['option_value' => $icon_id],
            ['option_name' => 'site_icon'],
            ['%s'],
            ['%s']
        );
    }
    
    // Flush all caches
    wp_cache_flush();
    
    // If running on a server with object caching, try to flush that too
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    return true;
}

// Add a hook to ensure the customizer settings are applied after the import
add_action('mtl_after_import_complete', 'mtl_ensure_customizer_settings', 10);