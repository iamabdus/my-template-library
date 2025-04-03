<?php
/**
 * Direct Import functionality for My Template Library
 *
 * @package My_Template_Library
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Register the direct access URL for template imports
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
 * Handle the direct import request
 */
if (!function_exists('mtl_handle_direct_import')) {
    function mtl_handle_direct_import() {
        if (get_query_var('mtl_direct_import')) {
            // This will be handled by the main plugin file
            // Just ensuring this function exists for the activation hook
        }
    }
}

// Add the query var for direct imports
if (!function_exists('mtl_add_query_vars')) {
    function mtl_add_query_vars($vars) {
        $vars[] = 'mtl_direct_import';
        return $vars;
    }
    add_filter('query_vars', 'mtl_add_query_vars');
} 