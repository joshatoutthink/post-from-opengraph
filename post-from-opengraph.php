<?php

/**
 * Plugin Name:     Post From Opengraph
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     post-from-opengraph
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Post_From_Opengraph
 */

use OpengraphPost\AdminError;

use function OpengraphPost\supported_post_types;

// Your code starts here.
require_once plugin_dir_path(__FILE__) . "includes/opengraph.php";
require_once plugin_dir_path(__FILE__) . "includes/options_page.php";
require_once plugin_dir_path(__FILE__) . "includes/acf_fields.php";
require_once plugin_dir_path(__FILE__) . "includes/AdminError.php";

define('URL_FIELD', 'og_source_url');
define('REFRESH_FIELD', 'og_source_url');

add_action('acf/save_post', 'add_post_content_from_opengraph');
function add_post_content_from_opengraph(int $post_id): WP_Error|bool {

    if ((wp_is_post_revision($post_id)) || (wp_is_post_autosave($post_id))) {
        return false;
    }

    if (!function_exists('get_field')) {
        error_log('get_field does not exist');
        return false;
    }

    $url = get_field(URL_FIELD, $post_id);
    if (!$url || !get_field(REFRESH_FIELD, $post_id)) {
        return false;
    }
    $html = wp_remote_get($url);
    if (is_wp_error($html)) {
        error_log("html is a error");
        error_log($html->get_error_code() . "\n " . print_r($html->get_all_error_data($html->get_error_code()), true));
        return false;
    }

    $html = $html['body'];

    $opengraph = OpenGraph::parse($html);
    if (!$opengraph) {
        $error_message = "We were unable to grab any opengraph data you will need to add the information by hand.";
        AdminError::error($error_message);

        return new WP_Error('my_custom_error_code', $error_message);
    }
    remove_action('save_post', 'add_post_content_from_opengraph');

    if(!property_exists($opengraph, "description")){
        AdminError::error("No Descripton exists");
    }
    $updated_post = array(
        'ID'           => $post_id,
        'post_excerpt' => $opengraph->description,
        'post_title'   => $opengraph->title,
    );
    wp_update_post($updated_post);

    update_post_meta($post_id, 'og_source_description', $opengraph->description);
    if ($opengraph->{"video:url"}) {
        update_post_meta($post_id, 'og_source_video', $opengraph->{"video:url"});
    }
    $image_id = add_to_media($opengraph->image);
    if ($image_id) {
        set_post_thumbnail($post_id, $image_id);
    } else {
        AdminError::error("No Image could be found");

        error_log('image_id is null: ' . $image_id);
    }

    update_field('refresh_content', false, $post_id);
    return true;
}

function add_to_media($url) {
    // Gives us access to the download_url() and wp_handle_sideload() functions
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    // URL to the WordPress logo
    $timeout_seconds = 5;
    $filename = strpos($url, "?") > 0 ? basename(explode("?", $url)[0]) : basename($url);
    // Download file to temp dir
    $temp_file = download_url($url, $timeout_seconds);
    $wp_filetype = wp_check_filetype($filename, null);

    if (!is_wp_error($temp_file)) {
        // Array based on $_FILE as seen in PHP file uploads
        $file = array(
            'name'     => $filename, // ex: wp-header-logo.png
            'type'     => $wp_filetype['type'],
            'tmp_name' => $temp_file,
            'error'    => 0,
            'size'     => filesize($temp_file),
        );

        $overrides = array(
            'test_form' => false,
            'test_type' => false,
        );

        // Move the temporary file into the uploads directory
        $results = wp_handle_sideload($file, $overrides);

        if (!empty($results['error'])) {

            return $results['error'];
        } else {

            $filename  = $results['file']; // Full path to the file
            $local_url = $results['url'];  // URL to the file in the uploads dir
            $type      = $results['type']; // MIME type of the file

            $attachment = array(
                'guid'    => $local_url,
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => $filename,
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $local_url);
            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
            $post_id = wp_update_attachment_metadata($attach_id, $attach_data);
            return $attach_id;
        }
    } else {
        return null;
    }
}

error_log(get_transient(AdminError::key));


add_shortcode('opengraph_content', 'display_opengraph_content');
function display_opengraph_content($atts) {
    ['get' => $name] = shortcode_atts(array(
        'get' => null,
    ), $atts);
    if ($name && $content = get_post_meta(get_the_ID(), "og_source_$name", true)) {
        return $content;
    }
}

$ptypes = supported_post_types();
foreach (array_keys($ptypes) as $ptype) {
    add_action("rest_after_insert_$ptype", "on_rest_after_insert", 10, 2);
}

function on_rest_after_insert($post, $request) {
    if (AdminError::hasError()) {
        $error = AdminError::wpError();
        AdminError::clear();
        wp_die($error->get_error_message());
    }
    return $post;
}
