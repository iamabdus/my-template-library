<?php
/**
 * WordPress Importer class for handling WXR files
 */

// Check if the WP_Import class is already defined
if (class_exists('WP_Import')) {
    return;
}

class WP_Import {
    /**
     * Processed posts
     * @var array
     */
    public $processed_posts = array();
    
    /**
     * Processed terms
     * @var array
     */
    public $processed_terms = array();
    
    /**
     * Processed menu items
     * @var array
     */
    public $processed_menu_items = array();
    
    /**
     * Whether to fetch attachments
     * @var bool
     */
    public $fetch_attachments = true;
    
    /**
     * URL remap
     * @var array
     */
    public $url_remap = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do here
    }
    
    /**
     * Import WXR file
     * 
     * @param string $file Path to the WXR file to import
     * @return array|WP_Error
     */
    public function import($file) {
        // Check if file exists
        if (!file_exists($file)) {
            return new WP_Error('file_not_found', 'The file does not exist: ' . $file);
        }
        
        // Parse the file
        $parser = new WXR_Parser();
        $data = $parser->parse($file);
        
        if (is_wp_error($data)) {
            return $data;
        }
        
        // Import the content
        $this->import_start($data);
        $this->process_authors($data['authors']);
        $this->process_categories($data['categories']);
        $this->process_tags($data['tags']);
        $this->process_terms($data['terms']);
        $this->process_posts($data['posts']);
        $this->import_end();
        
        return true;
    }
    
    /**
     * Start the import process
     * 
     * @param array $data Data from the WXR file
     */
    public function import_start($data) {
        // Increase memory limit and execution time
        wp_raise_memory_limit('admin');
        set_time_limit(0);
        
        // Start the import
        do_action('import_start');
    }
    
    /**
     * End the import process
     */
    public function import_end() {
        // End the import
        do_action('import_end');
    }
    
    /**
     * Process authors
     * 
     * @param array $authors Authors from the WXR file
     */
    public function process_authors($authors) {
        // Process authors
        foreach ($authors as $author) {
            // Check if the author already exists
            $user_id = username_exists($author['login']);
            
            if (!$user_id) {
                // Create the user
                $user_id = wp_create_user($author['login'], wp_generate_password(), $author['email']);
                
                if (!is_wp_error($user_id)) {
                    // Update user data
                    wp_update_user(array(
                        'ID' => $user_id,
                        'display_name' => $author['display_name'],
                        'first_name' => $author['first_name'],
                        'last_name' => $author['last_name']
                    ));
                }
            }
        }
    }
    
    /**
     * Process categories
     * 
     * @param array $categories Categories from the WXR file
     */
    public function process_categories($categories) {
        // Process categories
        foreach ($categories as $cat) {
            // Check if the category already exists
            $term = term_exists($cat['slug'], 'category');
            
            if (!$term) {
                // Create the category
                $term = wp_insert_term($cat['name'], 'category', array(
                    'slug' => $cat['slug'],
                    'description' => $cat['description'],
                    'parent' => $cat['parent']
                ));
                
                if (!is_wp_error($term)) {
                    $this->processed_terms[$cat['term_id']] = $term['term_id'];
                }
            } else {
                $this->processed_terms[$cat['term_id']] = $term['term_id'];
            }
        }
    }
    
    /**
     * Process tags
     * 
     * @param array $tags Tags from the WXR file
     */
    public function process_tags($tags) {
        // Process tags
        foreach ($tags as $tag) {
            // Check if the tag already exists
            $term = term_exists($tag['slug'], 'post_tag');
            
            if (!$term) {
                // Create the tag
                $term = wp_insert_term($tag['name'], 'post_tag', array(
                    'slug' => $tag['slug'],
                    'description' => $tag['description']
                ));
                
                if (!is_wp_error($term)) {
                    $this->processed_terms[$tag['term_id']] = $term['term_id'];
                }
            } else {
                $this->processed_terms[$tag['term_id']] = $term['term_id'];
            }
        }
    }
    
    /**
     * Process terms
     * 
     * @param array $terms Terms from the WXR file
     */
    public function process_terms($terms) {
        // Process terms
        foreach ($terms as $term) {
            // Check if the term already exists
            $term_exists = term_exists($term['slug'], $term['taxonomy']);
            
            if (!$term_exists) {
                // Create the term
                $term_result = wp_insert_term($term['name'], $term['taxonomy'], array(
                    'slug' => $term['slug'],
                    'description' => $term['description'],
                    'parent' => $term['parent']
                ));
                
                if (!is_wp_error($term_result)) {
                    $this->processed_terms[$term['term_id']] = $term_result['term_id'];
                }
            } else {
                $this->processed_terms[$term['term_id']] = $term_exists['term_id'];
            }
        }
    }
    
    /**
     * Process posts
     * 
     * @param array $posts Posts from the WXR file
     */
    public function process_posts($posts) {
        // Process posts
        foreach ($posts as $post) {
            // Skip attachment posts if fetch_attachments is false
            if ($post['post_type'] == 'attachment' && !$this->fetch_attachments) {
                continue;
            }
            
            // Check if the post already exists
            $post_exists = post_exists($post['post_title'], '', $post['post_date']);
            
            if (!$post_exists) {
                // Prepare post data
                $post_data = array(
                    'post_author' => 1, // Default to admin
                    'post_date' => $post['post_date'],
                    'post_date_gmt' => $post['post_date_gmt'],
                    'post_content' => $post['post_content'],
                    'post_excerpt' => $post['post_excerpt'],
                    'post_title' => $post['post_title'],
                    'post_status' => 'publish', // Always set to publish
                    'post_name' => $post['post_name'],
                    'comment_status' => $post['comment_status'],
                    'ping_status' => $post['ping_status'],
                    'guid' => $post['guid'],
                    'post_parent' => isset($post['post_parent']) ? $post['post_parent'] : 0,
                    'menu_order' => $post['menu_order'],
                    'post_type' => $post['post_type'],
                    'post_password' => $post['post_password']
                );
                
                // Handle attachments specially
                if ($post['post_type'] == 'attachment') {
                    $remote_url = !empty($post['attachment_url']) ? $post['attachment_url'] : $post['guid'];
                    
                    // Try to use the guid as the attachment URL if no attachment_url is provided
                    if (empty($remote_url)) {
                        continue;
                    }
                    
                    // Download the attachment
                    $attachment_id = $this->process_attachment($post, $remote_url);
                    
                    if (is_wp_error($attachment_id)) {
                        continue;
                    }
                    
                    $this->processed_posts[$post['post_id']] = $attachment_id;
                    continue;
                }
                
                // Set post parent if exists
                if (isset($post['post_parent']) && !empty($post['post_parent'])) {
                    if (isset($this->processed_posts[$post['post_parent']])) {
                        $post_data['post_parent'] = $this->processed_posts[$post['post_parent']];
                    }
                }
                
                // Insert the post
                $post_id = wp_insert_post($post_data, true);
                
                if (!is_wp_error($post_id)) {
                    $this->processed_posts[$post['post_id']] = $post_id;
                    
                    // Add post meta
                    foreach ($post['postmeta'] as $meta) {
                        // Skip _wp_attached_file and _wp_attachment_metadata for non-attachments
                        if ($post['post_type'] != 'attachment' && 
                            ($meta['key'] == '_wp_attached_file' || $meta['key'] == '_wp_attachment_metadata')) {
                            continue;
                        }
                        
                        add_post_meta($post_id, $meta['key'], $meta['value']);
                    }
                    
                    // Set terms
                    foreach ($post['terms'] as $term) {
                        $term_id = isset($this->processed_terms[$term['term_id']]) ? $this->processed_terms[$term['term_id']] : 0;
                        
                        if ($term_id) {
                            wp_set_object_terms($post_id, $term_id, $term['taxonomy'], true);
                        }
                    }
                    
                    // Handle post thumbnails (featured images)
                    if (isset($post['postmeta'])) {
                        foreach ($post['postmeta'] as $meta) {
                            if ($meta['key'] == '_thumbnail_id' && isset($this->processed_posts[(int)$meta['value']])) {
                                set_post_thumbnail($post_id, $this->processed_posts[(int)$meta['value']]);
                            }
                        }
                    }
                }
            } else {
                $this->processed_posts[$post['post_id']] = $post_exists;
            }
        }
    }
    
    /**
     * Process an attachment
     * 
     * @param array $post Post data
     * @param string $url URL of the attachment
     * @return int|WP_Error Attachment ID on success, WP_Error on failure
     */
    public function process_attachment($post, $url) {
        // Get the upload directory
        $upload = wp_upload_dir($post['post_date']);
        $upload_dir = $upload['path'];
        $upload_url = $upload['url'];
        
        // Get the file name
        $file_name = basename($url);
        
        // Check if the file already exists in the upload directory
        if (file_exists($upload_dir . '/' . $file_name)) {
            $file_name = wp_unique_filename($upload_dir, $file_name);
        }
        
        // Download the file
        $file_path = $upload_dir . '/' . $file_name;
        
        // Try to download the file
        $response = wp_remote_get($url, array(
            'timeout' => 300,
            'stream' => true,
            'filename' => $file_path
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            @unlink($file_path);
            return new WP_Error('import_file_error', 'Failed to download attachment file: ' . $url);
        }
        
        // Get file type
        $file_type = wp_check_filetype($file_name, null);
        
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'guid' => $upload_url . '/' . $file_name,
            'post_title' => $post['post_title'],
            'post_content' => $post['post_content'],
            'post_status' => 'inherit'
        );
        
        // Insert the attachment
        $attachment_id = wp_insert_attachment($attachment, $file_path);
        
        if (is_wp_error($attachment_id)) {
            @unlink($file_path);
            return $attachment_id;
        }
        
        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
        
        // Add post meta
        foreach ($post['postmeta'] as $meta) {
            add_post_meta($attachment_id, $meta['key'], $meta['value']);
        }
        
        return $attachment_id;
    }
}

/**
 * WXR Parser class
 */
class WXR_Parser {
    /**
     * Parse a WXR file
     * 
     * @param string $file Path to the WXR file
     * @return array|WP_Error
     */
    public function parse($file) {
        // Load the XML
        $xml = simplexml_load_file($file);
        
        if (!$xml) {
            return new WP_Error('invalid_xml', 'The XML file could not be parsed.');
        }
        
        // Initialize data arrays
        $data = array(
            'authors' => array(),
            'posts' => array(),
            'categories' => array(),
            'tags' => array(),
            'terms' => array(),
            'base_url' => ''
        );
        
        // Get base URL
        $data['base_url'] = (string) $xml->channel->link;
        
        // Process authors
        foreach ($xml->xpath('//wp:author') as $author) {
            $a = array(
                'login' => (string) $author->children('wp', true)->author_login,
                'email' => (string) $author->children('wp', true)->author_email,
                'display_name' => (string) $author->children('wp', true)->author_display_name,
                'first_name' => (string) $author->children('wp', true)->author_first_name,
                'last_name' => (string) $author->children('wp', true)->author_last_name
            );
            
            $data['authors'][] = $a;
        }
        
        // Process categories
        foreach ($xml->xpath('//wp:category') as $cat) {
            $c = array(
                'term_id' => (int) $cat->children('wp', true)->term_id,
                'name' => (string) $cat->children('wp', true)->cat_name,
                'slug' => (string) $cat->children('wp', true)->category_nicename,
                'parent' => (int) $cat->children('wp', true)->category_parent,
                'description' => (string) $cat->children('wp', true)->category_description
            );
            
            $data['categories'][] = $c;
        }
        
        // Process tags
        foreach ($xml->xpath('//wp:tag') as $tag) {
            $t = array(
                'term_id' => (int) $tag->children('wp', true)->term_id,
                'name' => (string) $tag->children('wp', true)->tag_name,
                'slug' => (string) $tag->children('wp', true)->tag_slug,
                'description' => (string) $tag->children('wp', true)->tag_description
            );
            
            $data['tags'][] = $t;
        }
        
        // Process terms
        foreach ($xml->xpath('//wp:term') as $term) {
            $t = array(
                'term_id' => (int) $term->children('wp', true)->term_id,
                'name' => (string) $term->children('wp', true)->term_name,
                'slug' => (string) $term->children('wp', true)->term_slug,
                'taxonomy' => (string) $term->children('wp', true)->term_taxonomy,
                'parent' => (int) $term->children('wp', true)->term_parent,
                'description' => (string) $term->children('wp', true)->term_description
            );
            
            $data['terms'][] = $t;
        }
        
        // Process posts
        foreach ($xml->channel->item as $item) {
            $post = array(
                'post_id' => (int) $item->children('wp', true)->post_id,
                'post_title' => (string) $item->title,
                'post_date' => (string) $item->children('wp', true)->post_date,
                'post_date_gmt' => (string) $item->children('wp', true)->post_date_gmt,
                'post_content' => (string) $item->children('content', true)->encoded,
                'post_excerpt' => (string) $item->children('excerpt', true)->encoded,
                'post_name' => (string) $item->children('wp', true)->post_name,
                'status' => (string) $item->children('wp', true)->status,
                'comment_status' => (string) $item->children('wp', true)->comment_status,
                'ping_status' => (string) $item->children('wp', true)->ping_status,
                'guid' => (string) $item->guid,
                'menu_order' => (int) $item->children('wp', true)->menu_order,
                'post_type' => (string) $item->children('wp', true)->post_type,
                'post_password' => (string) $item->children('wp', true)->post_password,
                'postmeta' => array(),
                'terms' => array()
            );
            
            // Get post parent if exists
            if ($item->children('wp', true)->post_parent) {
                $post['post_parent'] = (int) $item->children('wp', true)->post_parent;
            }
            
            // Get attachment URL if this is an attachment
            if ($post['post_type'] == 'attachment') {
                $post['attachment_url'] = (string) $item->children('wp', true)->attachment_url;
            }
            
            // Process post meta
            foreach ($item->children('wp', true)->postmeta as $meta) {
                $post['postmeta'][] = array(
                    'key' => (string) $meta->children('wp', true)->meta_key,
                    'value' => (string) $meta->children('wp', true)->meta_value
                );
            }
            
            // Process terms
            foreach ($item->children('wp', true)->category as $term) {
                $t = array(
                    'term_id' => (int) $term->attributes()->term_id,
                    'taxonomy' => (string) $term->attributes()->domain,
                    'slug' => (string) $term->attributes()->nicename
                );
                
                $post['terms'][] = $t;
            }
            
            $data['posts'][] = $post;
        }
        
        return $data;
    }
}

// Helper function to check if a post exists
if (!function_exists('post_exists')) {
    function post_exists($title, $content = '', $date = '') {
        global $wpdb;
        
        $post_title = wp_unslash(sanitize_post_field('post_title', $title, 0, 'db'));
        $post_content = wp_unslash(sanitize_post_field('post_content', $content, 0, 'db'));
        $post_date = wp_unslash(sanitize_post_field('post_date', $date, 0, 'db'));
        
        $query = "SELECT ID FROM $wpdb->posts WHERE 1=1";
        $args = array();
        
        if (!empty($date)) {
            $query .= " AND post_date = %s";
            $args[] = $post_date;
        }
        
        if (!empty($title)) {
            $query .= " AND post_title = %s";
            $args[] = $post_title;
        }
        
        if (!empty($content)) {
            $query .= " AND post_content = %s";
            $args[] = $post_content;
        }
        
        if (!empty($args)) {
            return (int) $wpdb->get_var($wpdb->prepare($query, $args));
        }
        
        return 0;
    }
} 