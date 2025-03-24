<?php
/**
 * Plugin Name: RepliPost
 * Plugin URI: 
 * Description: Easily duplicate posts, pages, and custom post types with a single click.
 * Version: 1.0.0
 * Author: 
 * Author URI: 
 * Text Domain: RepliPost
 * Domain Path: /languages
 * License: GPL-2.0+
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('REPLIPOST_VERSION', '1.0.0');
define('REPLIPOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REPLIPOST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once REPLIPOST_PLUGIN_DIR . 'includes/class-replipost.php';

// Initialize the plugin
function replipost_init() {
    return RepliPost::get_instance();
}
add_action('plugins_loaded', 'replipost_init');

// Activation hook
register_activation_hook(__FILE__, 'replipost_activate');
function replipost_activate() {
    // Activation tasks
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'replipost_deactivate');
function replipost_deactivate() {
    // Deactivation tasks
    flush_rewrite_rules();
} 