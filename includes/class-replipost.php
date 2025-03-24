<?php
/**
 * Main RepliPost Class
 *
 * @package RepliPost
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global function for sanitizing post types
 *
 * @param mixed $input The value being saved.
 * @return array
 */
function sanitize_post_types_callback($input) {
    if (!is_array($input)) {
        return array('post', 'page');
    }
    
    $sanitized = array();
    $available_post_types = get_post_types(array('public' => true));
    
    foreach ($input as $post_type) {
        $post_type = sanitize_key($post_type);
        if (in_array($post_type, $available_post_types)) {
            $sanitized[] = $post_type;
        }
    }
    
    return !empty($sanitized) ? $sanitized : array('post', 'page');
}

/**
 * Main RepliPost Class
 */
class RepliPost {

    /**
     * Plugin instance
     *
     * @var object
     */
    private static $instance = null;

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Get plugin instance
     *
     * @return object
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Add post row actions for enabled post types
        add_action('admin_init', array($this, 'register_duplicate_actions'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Handle post duplication
        add_action('admin_action_replipost_duplicate', array($this, 'duplicate_post_action'));

        // Add admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Register plugin settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add bulk actions
        add_action('admin_init', array($this, 'register_bulk_actions'));
        
        // Handle bulk action
        add_action('admin_notices', array($this, 'bulk_action_admin_notice'));
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('RepliPost', false, dirname(plugin_basename(REPLIPOST_PLUGIN_DIR)) . '/languages');
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Post types setting
        register_setting(
            'replipost_settings',                // Option group
            'replipost_post_types',              // Option name
            'sanitize_post_types_callback'       // Sanitization callback
        );
        
        // Title suffix setting
        register_setting(
            'replipost_settings',                // Option group
            'replipost_copy_title_suffix',       // Option name
            'sanitize_text_field'                // Using WordPress core sanitization
        );
        
        // Default status setting
        register_setting(
            'replipost_settings',                // Option group
            'replipost_default_status',          // Option name
            'sanitize_key'                       // Using WordPress core sanitization
        );

        // Set default options if not already set
        if (false === get_option('replipost_post_types')) {
            update_option('replipost_post_types', array('post', 'page'));
        }
        
        if (false === get_option('replipost_copy_title_suffix')) {
            update_option('replipost_copy_title_suffix', __('(Copy)', 'RepliPost'));
        }
        
        if (false === get_option('replipost_default_status')) {
            update_option('replipost_default_status', 'draft');
        }
    }

    /**
     * Sanitize post types setting
     *
     * @param mixed $input The value being saved.
     * @return array
     */
    public static function sanitize_post_types_setting($input) {
        if (!is_array($input)) {
            return array('post', 'page');
        }

        $sanitized = array();
        $available_post_types = get_post_types(array('public' => true));

        foreach ($input as $post_type) {
            $post_type = sanitize_key($post_type);
            if (in_array($post_type, $available_post_types)) {
                $sanitized[] = $post_type;
            }
        }

        return !empty($sanitized) ? $sanitized : array('post', 'page');
    }

    /**
     * Sanitize title suffix setting
     *
     * @param mixed $input The value being saved.
     * @return string
     */
    public static function sanitize_title_suffix_setting($input) {
        $sanitized = sanitize_text_field($input);
        return !empty($sanitized) ? $sanitized : __('(Copy)', 'RepliPost');
    }

    /**
     * Sanitize status setting
     *
     * @param mixed $input The value being saved.
     * @return string
     */
    public static function sanitize_status_setting($input) {
        $allowed_statuses = array('draft', 'publish', 'pending', 'private');
        $sanitized = sanitize_key($input);
        return in_array($sanitized, $allowed_statuses) ? $sanitized : 'draft';
    }

    /**
     * Validate post types array
     *
     * @param array $post_types Array of post types.
     * @return bool|WP_Error
     */
    public function validate_post_types($post_types) {
        if (!is_array($post_types)) {
            return new WP_Error('invalid_post_types', __('Post types must be an array', 'RepliPost'));
        }

        $available_post_types = get_post_types(array('public' => true));
        foreach ($post_types as $post_type) {
            if (!in_array($post_type, $available_post_types)) {
                /* translators: %s: Post type name */
                return new WP_Error('invalid_post_type', sprintf(__('Invalid post type: %s', 'RepliPost'), $post_type));
            }
        }
        return true;
    }

    /**
     * Validate title suffix
     *
     * @param string $suffix The title suffix.
     * @return bool|WP_Error
     */
    public function validate_title_suffix($suffix) {
        if (empty($suffix)) {
            return new WP_Error('empty_suffix', __('Title suffix cannot be empty', 'RepliPost'));
        }
        if (strlen($suffix) > 50) {
            return new WP_Error('suffix_too_long', __('Title suffix is too long', 'RepliPost'));
        }
        return true;
    }

    /**
     * Validate post status
     *
     * @param string $status Post status.
     * @return bool|WP_Error
     */
    public function validate_post_status($status) {
        $allowed_statuses = array('draft', 'publish', 'pending', 'private');
        if (!in_array($status, $allowed_statuses)) {
            return new WP_Error('invalid_status', __('Invalid post status', 'RepliPost'));
        }
        return true;
    }

    /**
     * Sanitize post types array
     *
     * @param array $post_types Array of post types.
     * @return array
     */
    public function sanitize_post_types($post_types) {
        if (!is_array($post_types)) {
            return array('post', 'page');
        }
        
        return array_map('sanitize_key', $post_types);
    }

    /**
     * Sanitize post status
     *
     * @param string $status Post status.
     * @return string
     */
    public function sanitize_post_status($status) {
        $allowed_statuses = array('draft', 'publish', 'pending', 'private');
        $status = sanitize_key($status);
        
        return in_array($status, $allowed_statuses) ? $status : 'draft';
    }

    /**
     * Register duplicate actions for enabled post types
     */
    public function register_duplicate_actions() {
        $post_types = get_option('replipost_post_types', array('post', 'page'));
        
        if (!empty($post_types)) {
            foreach ($post_types as $post_type) {
                add_filter($post_type . '_row_actions', array($this, 'add_duplicate_link'), 10, 2);
            }
        }
    }

    /**
     * Register bulk actions for duplicate
     */
    public function register_bulk_actions() {
        $post_types = get_option('replipost_post_types', array('post', 'page'));
        
        if (!empty($post_types)) {
            foreach ($post_types as $post_type) {
                // Add bulk action
                add_filter('bulk_actions-edit-' . $post_type, array($this, 'add_bulk_duplicate_action'));
                
                // Handle bulk action
                add_filter('handle_bulk_actions-edit-' . $post_type, array($this, 'handle_bulk_duplicate'), 10, 3);
            }
        }
    }
    
    /**
     * Add bulk duplicate action
     *
     * @param array $actions Bulk actions.
     * @return array
     */
    public function add_bulk_duplicate_action($actions) {
        $actions['duplicate'] = __('Duplicate', 'RepliPost');
        return $actions;
    }
    
    /**
     * Handle bulk duplicate action
     *
     * @param string $redirect_to Redirect URL.
     * @param string $doaction    Action name.
     * @param array  $post_ids    Array of post IDs.
     * @return string
     */
    public function handle_bulk_duplicate($redirect_to, $doaction, $post_ids) {
        if ('duplicate' !== $doaction) {
            return $redirect_to;
        }
        
        $duplicated = 0;
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            
            if (!$post) {
                continue;
            }
            
            // Check if post type is enabled for duplication
            $enabled_post_types = get_option('replipost_post_types', array('post', 'page'));
            if (!in_array($post->post_type, $enabled_post_types)) {
                continue;
            }
            
            $new_post_id = $this->duplicate_post($post);
            
            if (!is_wp_error($new_post_id)) {
                $duplicated++;
            }
        }
        
        return add_query_arg(array(
            'duplicated' => $duplicated,
            'post_type'  => get_post_type($post_ids[0])
        ), $redirect_to);
    }
    
    /**
     * Display admin notice after bulk duplication
     */
    public function bulk_action_admin_notice() {
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'bulk-posts')) {
            return;
        }

        if (!empty($_REQUEST['duplicated'])) {
            $count = intval($_REQUEST['duplicated']);
            
            
            $notice = sprintf(
                 /* translators: %s: number of duplicated posts/pages */
                _n(
                    '%s item duplicated successfully.',
                    '%s items duplicated successfully.',
                    $count,
                    'RepliPost'
                ),
                number_format_i18n($count)
            );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($notice) . '</p></div>';
        }
    }

    /**
     * Add duplicate link to post/page actions
     *
     * @param array   $actions Array of actions.
     * @param WP_Post $post    Post object.
     * @return array
     */
    public function add_duplicate_link($actions, $post) {
        if (current_user_can('edit_posts')) {
            $duplicate_url = admin_url('admin.php?action=replipost_duplicate&post=' . $post->ID . '&nonce=' . wp_create_nonce('replipost_duplicate_nonce'));
            
            $actions['duplicate'] = sprintf(
                '<a href="%s" title="%s">%s</a>',
                esc_url($duplicate_url),
                esc_attr__('Duplicate this item', 'RepliPost'),
                esc_html__('Duplicate', 'RepliPost')
            );
        }
        return $actions;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            esc_html__('RepliPost Settings', 'RepliPost'),
            esc_html__('RepliPost', 'RepliPost'),
            'manage_options',
            'replipost-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Settings page
     */
    public function settings_page() {
        include REPLIPOST_PLUGIN_DIR . 'admin/settings-page.php';
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets() {
        wp_enqueue_style('replipost-admin', REPLIPOST_PLUGIN_URL . 'assets/css/admin.css', array(), REPLIPOST_VERSION);
        wp_enqueue_script('replipost-admin', REPLIPOST_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), REPLIPOST_VERSION, true);
        
        // Localize script
        wp_localize_script('replipost-admin', 'repliPostAdmin', array(
            'confirmDuplicate' => __('Are you sure you want to duplicate this item?', 'RepliPost'),
            'confirmBulkDuplicate' => __('Are you sure you want to duplicate the selected items?', 'RepliPost'),
            'ajaxUrl'          => admin_url('admin-ajax.php'),
            'nonce'            => wp_create_nonce('replipost_nonce')
        ));
    }

    /**
     * Duplicate post action
     */
    public function duplicate_post_action() {
        // Check if user has proper permission
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('You do not have permission to duplicate this content.', 'RepliPost'));
        }

        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_GET['nonce'])), 'replipost_duplicate_nonce')) {
            wp_die(esc_html__('Security check failed. Please try again.', 'RepliPost'));
        }

        // Check if post ID is provided
        if (!isset($_GET['post'])) {
            wp_die(esc_html__('No post to duplicate has been provided.', 'RepliPost'));
        }

        // Get the original post
        $post_id = absint($_GET['post']);
        $post = get_post($post_id);

        if (!$post) {
            wp_die(esc_html__('Post creation failed, could not find original post.', 'RepliPost'));
        }

        // Check if post type is enabled for duplication
        $enabled_post_types = get_option('replipost_post_types', array('post', 'page'));
        if (!in_array($post->post_type, $enabled_post_types)) {
            wp_die(esc_html__('This post type is not enabled for duplication.', 'RepliPost'));
        }

        // Create duplicate post
        $new_post_id = $this->duplicate_post($post);

        if (is_wp_error($new_post_id)) {
            wp_die(esc_html($new_post_id->get_error_message()));
        }

        // Redirect to the edit screen for the new post
        wp_safe_redirect(admin_url('post.php?action=edit&post=' . absint($new_post_id)));
        exit;
    }

    /**
     * Create duplicate of a post
     *
     * @param WP_Post $post Post object.
     * @return int|WP_Error
     */
    private function duplicate_post($post) {
        // Get settings
        $title_suffix = get_option('replipost_copy_title_suffix', __('(Copy)', 'RepliPost'));
        $default_status = get_option('replipost_default_status', 'draft');
        
        // Get the post data
        $args = array(
            'post_author'    => $post->post_author,
            'post_content'   => $post->post_content,
            'post_excerpt'   => $post->post_excerpt,
            'post_name'      => $post->post_name,
            'post_parent'    => $post->post_parent,
            'post_password'  => $post->post_password,
            'post_status'    => $default_status,
            'post_title'     => $post->post_title . ' ' . $title_suffix,
            'post_type'      => $post->post_type,
            'comment_status' => $post->comment_status,
            'ping_status'    => $post->ping_status,
            'to_ping'        => $post->to_ping,
            'menu_order'     => $post->menu_order
        );

        // Insert the post
        $new_post_id = wp_insert_post($args);

        if (is_wp_error($new_post_id)) {
            return $new_post_id;
        }

        // Get all current post terms and set them to the new post
        $taxonomies = get_object_taxonomies($post->post_type);
        if (!empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'slugs'));
                wp_set_object_terms($new_post_id, $terms, $taxonomy, false);
            }
        }

        // Duplicate post meta
        $post_meta = get_post_meta($post->ID);
        if (!empty($post_meta)) {
            foreach ($post_meta as $meta_key => $meta_values) {
                if ('_edit_lock' === $meta_key || '_edit_last' === $meta_key) {
                    continue;
                }
                
                foreach ($meta_values as $meta_value) {
                    add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
                }
            }
        }

        // Apply filter to allow custom actions after duplication
        do_action('replipost_after_duplicate', $new_post_id, $post);

        return $new_post_id;
    }
}