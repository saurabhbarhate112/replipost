<?php
/**
 * RepliPost Settings Page
 *
 * @package RepliPost
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Save settings
if (isset($_POST['replipost_save_settings']) && check_admin_referer('replipost_settings_nonce', 'replipost_nonce')) {
    $post_types = isset($_POST['replipost_post_types']) ? array_map('sanitize_key', wp_unslash((array) $_POST['replipost_post_types'])) : array();
    update_option('replipost_post_types', $post_types);
    
    $copy_title_suffix = isset($_POST['replipost_copy_title_suffix']) ? sanitize_text_field(wp_unslash($_POST['replipost_copy_title_suffix'])) : __('(Copy)', 'RepliPost');
    update_option('replipost_copy_title_suffix', $copy_title_suffix);
    
    $default_status = isset($_POST['replipost_default_status']) ? sanitize_key(wp_unslash($_POST['replipost_default_status'])) : 'draft';
    update_option('replipost_default_status', $default_status);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'RepliPost') . '</p></div>';
}

// Get current settings
$selected_post_types = get_option('replipost_post_types', array('post', 'page'));
$copy_title_suffix = get_option('replipost_copy_title_suffix', __('(Copy)', 'RepliPost'));
$default_status = get_option('replipost_default_status', 'draft');

// Get all post types
$post_types = get_post_types(array('public' => true), 'objects');
?>

<div class="wrap">
    <h1><?php echo esc_html__('RepliPost Settings', 'RepliPost'); ?></h1>
    
    <div class="replipost-form">
        <form method="post" action="">
            <?php wp_nonce_field('replipost_settings_nonce', 'replipost_nonce'); ?>
            
            <div class="replipost-settings-section">
                <h2><?php echo esc_html__('Post Types to Duplicate', 'RepliPost'); ?></h2>
                <p><?php echo esc_html__('Select which post types can be duplicated with RepliPost.', 'RepliPost'); ?></p>
                
                <p>
                    <a href="#" id="replipost-select-all" class="button button-secondary"><?php echo esc_html__('Select All', 'RepliPost'); ?></a>
                    <a href="#" id="replipost-deselect-all" class="button button-secondary"><?php echo esc_html__('Deselect All', 'RepliPost'); ?></a>
                </p>
                
                <div class="checkbox-group" style="margin-top: 10px;">
                    <?php foreach ($post_types as $post_type) : ?>
                        <label>
                            <input type="checkbox" name="replipost_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $selected_post_types)); ?>>
                            <?php echo esc_html($post_type->label); ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="replipost-settings-section">
                <h2><?php echo esc_html__('Duplication Settings', 'RepliPost'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Title Suffix', 'RepliPost'); ?></th>
                        <td>
                            <input type="text" name="replipost_copy_title_suffix" value="<?php echo esc_attr($copy_title_suffix); ?>" class="regular-text">
                            <p class="description"><?php echo esc_html__('Text to append to the duplicate post title.', 'RepliPost'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Default Status', 'RepliPost'); ?></th>
                        <td>
                            <select name="replipost_default_status">
                                <option value="draft" <?php selected('draft', $default_status); ?>><?php echo esc_html__('Draft', 'RepliPost'); ?></option>
                                <option value="publish" <?php selected('publish', $default_status); ?>><?php echo esc_html__('Published', 'RepliPost'); ?></option>
                                <option value="pending" <?php selected('pending', $default_status); ?>><?php echo esc_html__('Pending', 'RepliPost'); ?></option>
                                <option value="private" <?php selected('private', $default_status); ?>><?php echo esc_html__('Private', 'RepliPost'); ?></option>
                            </select>
                            <p class="description"><?php echo esc_html__('Status to assign to the duplicate post.', 'RepliPost'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="replipost_save_settings" class="button-primary" value="<?php echo esc_attr__('Save Settings', 'RepliPost'); ?>">
            </p>
        </form>
    </div>
    
    <div class="replipost-settings-section">
        <h2><?php echo esc_html__('How to Use', 'RepliPost'); ?></h2>
        <p><?php echo esc_html__('To duplicate a post, page, or custom post type:', 'RepliPost'); ?></p>
        <ol>
            <li><?php echo esc_html__('Go to the list of posts, pages, or custom post types.', 'RepliPost'); ?></li>
            <li><?php echo esc_html__('Hover over the title of the item you want to duplicate.', 'RepliPost'); ?></li>
            <li><?php echo esc_html__('Click the "Duplicate" link that appears.', 'RepliPost'); ?></li>
            <li><?php echo esc_html__('A copy will be created with your chosen settings and you will be redirected to edit it.', 'RepliPost'); ?></li>
        </ol>
    </div>
</div> 