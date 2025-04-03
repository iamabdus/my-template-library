<?php
class My_Template_Library {
    
    private static $instance = null;
    private $templates = [];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        $this->setup_templates();
    }

    private function setup_templates() {
        require_once MTL_PLUGIN_DIR . 'includes/templates-data.php';
        $this->templates = get_template_data();
    }
    
    public function init() {
        load_plugin_textdomain('my-template-library', false, dirname(plugin_basename(__FILE__)) . '/languages');
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Template Library', 'my-template-library'),
            __('Template Library', 'my-template-library'),
            'manage_options',
            'template-library',
            array($this, 'display_admin_page'),
            'dashicons-layout',
            30
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_template-library' !== $hook) {
            return;
        }
        
        wp_enqueue_style('mtl-admin-style', MTL_PLUGIN_URL . 'assets/css/admin.css', array(), MTL_VERSION);
        wp_enqueue_script('mtl-admin-script', MTL_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), MTL_VERSION, true);
    }
    
    public function display_admin_page() {
        require_once MTL_PLUGIN_DIR . 'templates/admin-page.php';
    }
}