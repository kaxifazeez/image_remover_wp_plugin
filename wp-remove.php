<?php
/*
 * Plugin Name:       Image Remover
 * Plugin URI:        https://localhost/plugin/wp-remove
 * Description:       Remove Images of all content and posts
 * Version:           1.10.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Kashif Aziz 
 * Author URI:        https://localhost/plugin/wp-remove
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://localhost/plugin/wp-remove
 * Text Domain:       textdomain
 * Domain Path:       /languages
 */
function wpbootstrap_enqueue_styles()
{
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"');
}
add_action('wp_enqueue_scripts', 'wpbootstrap_enqueue_styles');
add_action('admin_enqueue_scripts', 'wpbootstrap_enqueue_styles');


function remove_activation()
{
    //activation
}
register_activation_hook(__FILE__, 'remove_activation');

function remove_deactivation()
{
    //deactivation
}
register_deactivation_hook(__FILE__, 'remove_deactivation');

//admin page
function removeImage_admin_setting()
{
    add_menu_page(
        __('Admin Setting', 'textdomain'),
        'Image Remover',
        'manage_options',
        'remove_image_admin',
        'removeImage_admin_page',
        'dashicons-dashboard',
        2
    );
}
add_action('admin_menu', 'removeImage_admin_setting');

function removeImage_admin_page()
{
?>
    <div class="d-flex">
        <h3 class="p-3">Click the Button to Remove images from all posts and content</h3>
        <form method="post" action="">
            <button type="submit" name="remove_images" class="btn mt-3 btn-secondary">Remove</button>
        </form>
    </div>
    <?php
    if (isset($_POST['remove_images'])) 
    {
        remove_images();
        echo '<h3 class="p-3"> Images has been removed successfully</h3>';
    }
}

function remove_images()
{
    //getting all posts 
    $posts = get_posts(
        array(
            'post_type' => 'post',
            'numberposts' => -1
        )
    );
    //delete thumbnail
    foreach ($posts as $post)
    {

        delete_post_thumbnail($post->ID);
        $post = get_post($post->ID);
        $content = $post->post_content;
        $content = preg_replace('/<img[^>]+>/', '', $content);
        wp_update_post(array(
            'ID'           => $post->ID,
            'post_content' => $content,
        ));
        /**I use Preg_replace here : It replace Image tag content
         with whitespace and update the content */
    }
}

function remove_image_button_edit_post()
{
    $screens = ['post'];
    foreach ($screens as $screen)
     {
        add_meta_box(
            'wporg_' . $screen . '_meta_box',
            'Remove Post Images',
            'remove_image_metabox',
            $screen,
            'side'
        );
    }
}
add_action('add_meta_boxes', 'remove_image_button_edit_post');

function remove_image_metabox($post)
{
    ?>
    <div class="d-flex">

        <form method="post">
            <p>Remove All Images</p>
            <select name="remove_images" id="remove_images">
                <option value="">Select option</option>
                <option value="yes">Delete all images</option>
            </select>
        </form>
    </div>
    <?php

}
add_action('save_post', 'remove_image_fun');

function remove_image_fun($post)
{
    global $wpdb;
    if ($_POST['remove_images'] === 'yes') 
    {
        $post_id = get_the_ID();
        delete_post_thumbnail($post_id);
        $post = get_post($post_id);
        $content = $post->post_content;

        $new_content = preg_replace('/<img[^>]+>/', '', $content);
        $wpdb->update
        (
            $wpdb->posts,
            array('post_content' => $new_content),
            array('ID' => $post_id)
        );
    }
}

function remove_image_button_edit_media()
{
    global $post;
    if ($post && $post->post_type === 'attachment') 
    {
    ?>
        <div class="d-flex">
            <p>Remove All Images</p>
            <select name="remove_spec_images" id="remove_spec_images">
                <option value="">Select option</option>
                <option value="yes">Delete all images</option>
            </select>
        </div>
    <?php
    }
}
add_action('attachment_submitbox_misc_actions', 'remove_image_button_edit_media');

function remove_specific_image_fun($post)
{
    global $wpdb;
    if ($_POST['remove_spec_images'] === 'yes') 
    {
        $attachment_id = get_the_ID();
        $query = $wpdb->prepare
        (
            "SELECT * FROM $wpdb->posts WHERE post_type = 'post' AND post_content LIKE %s",
            '%' . $attachment_id . '%'
        );
        $posts = $wpdb->get_results($query);
        foreach ($posts as $post) 
        {
            delete_post_thumbnail($post->ID);
            $post = get_post($post->ID);
            $content = $post->post_content;
            $new_content  = preg_replace('/<img[^>]+>/', '', $content);
            $wpdb->update
            (
                $wpdb->posts,
                array('post_content' => $new_content),
                array('ID' => $post->ID)
            );
        }
    }
}
add_action('edit_attachment', 'remove_specific_image_fun');
