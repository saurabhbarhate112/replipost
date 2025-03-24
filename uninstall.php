<?php
/**
 * RepliPost Uninstall
 *
 * @package RepliPost
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('replipost_post_types');
delete_option('replipost_copy_title_suffix');
delete_option('replipost_default_status');

// Clear any cached data that has been removed
wp_cache_flush(); 
 