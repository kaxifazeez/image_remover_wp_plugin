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

use SebastianBergmann\Environment\Console;

class Image_Remover
{
    public function __construct()
    {

        add_action('wp_enqueue_scripts',                [$this, 'wpbootstrap_enqueue_styles']);
        add_action('admin_enqueue_scripts',             [$this, 'wpbootstrap_enqueue_styles']);
        register_activation_hook(__FILE__,              [$this, 'remove_activation']);
        register_deactivation_hook(__FILE__,            [$this, 'remove_deactivation']);
        add_action('admin_menu',                        [$this, 'removeImage_admin_setting']);
        add_action('add_meta_boxes',                    [$this, 'remove_image_button_edit_post']);
        add_action('attachment_submitbox_misc_actions', [$this, 'remove_image_button_edit_media']);
        add_action('edit_attachment',                   [$this, 'remove_specific_image_fun']);
        add_action('wp_ajax_remove_images',             [$this, 'remove_image_fun']);
        add_action('wp_ajax_remove_images_media',       [$this, 'remove_specific_image_fun']);
        add_action('admin_enqueue_scripts',             [$this, 'enqueue_form_ajax_script']);
    }
    public function wpbootstrap_enqueue_styles()
    {
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css');
    }

    public  function remove_activation()
    {
        //activation
    }

    public function remove_deactivation()
    {
        //deactivation
    }
    //admin page
    public  function removeImage_admin_setting()
    {
        add_menu_page(
            __('Admin Setting', 'textdomain'),
            'Image Remover',
            'manage_options',
            'remove_image_admin',
            [$this, 'removeImage_admin_page'],
            'dashicons-dashboard',
            2
        );
    }
    //remove all images from all posts from admin sidebar.
    public function removeImage_admin_page()
    {
?>
        <div class="d-flex">
            <h3 class="p-3">Click the Button to Remove images from all posts and content</h3>
            <form method="post" action="">
                <button type="submit" name="remove_images" class="btn mt-3 btn-secondary">Remove</button>
            </form>
        </div>
        <?php
        if (isset($_POST['remove_images'])) {
            $this->remove_images();
            echo '<h3 class="p-3"> Images has been removed successfully</h3>';
        }
    }


    public  function remove_images()
    {
        //getting all posts 
        $posts = get_posts(
            array(
                'post_type' => 'post',
                'numberposts' => -1
            )
        );
        //delete thumbnail
        foreach ($posts as $post) {

            delete_post_thumbnail($post->ID);
            $post = get_post($post->ID);
            $content = $post->post_content;
            $content = preg_replace('/<img[^>]+>/', '', $content);
            wp_update_post(array(
                'ID'           => $post->ID,
                'post_content' => $content,
            ));
        }
    }

    public function remove_image_button_edit_post()
    {
        $screens = ['post'];
        foreach ($screens as $screen) {
            add_meta_box(
                'wporg_' . $screen . '_meta_box',
                'Remove Post Images',
                [$this, 'remove_image_metabox'],
                $screen,
                'side'
            );
        }
    }
    public function remove_image_metabox($post)
    {
        ?>
        <div>
            <form id="remove-images-form" method="post">
                <button type="button" id="remove-images" class="button button-primary">Remove Images</button>
                <p id='output'></p>
            </form>
        </div>
        <?php
    }

    function enqueue_form_ajax_script()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('custom-ajax-script', plugins_url('ajaxScript.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('custom-ajax-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function remove_image_fun()
    {

        $post_id = intval($_POST['post_id']);
        if ($post_id) {
            delete_post_thumbnail($post_id);
            $post = get_post($post_id);
            $content = preg_replace('/<img[^>]+>/', '', $post->post_content);
            wp_update_post(array('ID' => $post->ID, 'post_content' => $content));
            wp_send_json_success('Images removed successfully.');
        } else {
            wp_send_json_error('Invalid post ID or insufficient permissions.');
        }
    }
    public function remove_image_button_edit_media()
    {
        global $post;
        if ($post && $post->post_type === 'attachment') {
        ?>
            <div>
                <form id="remove-images-media-form" method="post">
                    <button type="button" id="remove-images-media" class="button button-primary">Remove Images</button>
                    <p id='output-media'></p>
                </form>
            </div>
<?php
        }
    }

    public function remove_specific_image_fun()
    {

        $attachment_id = intval($_POST['attachment_id']);

        if ($attachment_id) {
            global $wpdb;

            $query = $wpdb->prepare(
                "SELECT * FROM $wpdb->posts WHERE post_type = 'post' AND post_content LIKE %s",
                '%' . $attachment_id . '%'
            );
            $posts = $wpdb->get_results($query);
            foreach ($posts as $post) {
                $attached_images = get_attached_media('image', $post->ID);
                foreach ($attached_images as $attached_image) {
                    if ($attached_image->ID === $attachment_id) {
                        delete_post_thumbnail($post->ID);
                        $post = get_post($post->ID);
                        $content = $post->post_content;
                        $pattern = '/<img[^>]+class="[^"]*\bwp-image-' . $attachment_id . '\b[^"]*"[^>]*>/';
                        $new_content = preg_replace($pattern, '', $content);
                        $wpdb->update(
                            $wpdb->posts,
                            array('post_content' => $new_content),
                            array('ID' => $post->ID)
                        );
                    }
                }
            }
            wp_send_json_success('Images removed successfully');
        } else {
            wp_send_json_error('Attachment Not found');
        }
    }
}

new Image_Remover();

?>
