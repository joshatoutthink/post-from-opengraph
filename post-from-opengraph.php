<?php

/**
 * Plugin Name:     Post From Opengraph
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     post-from-opengraph
 * Domain Path:     /languages
 * Version:         0.2.2
 *
 * @package         Post_From_Opengraph
 */
namespace OpengraphPost;
define("OpengraphPost_VERSION","0.2.2");
define("OpengraphPost_FILE",__FILE__);

// Your code starts here.
require_once plugin_dir_path(__FILE__) . "updatePlugin/init.php";
require_once plugin_dir_path(__FILE__) . "includes/opengraph.php";
require_once plugin_dir_path(__FILE__) . "includes/options_page.php";
require_once plugin_dir_path(__FILE__) . "includes/acf_fields.php";
require_once plugin_dir_path(__FILE__) . "includes/AdminError.php";
require_once plugin_dir_path(__FILE__) . "includes/postfromopengraph.php";

require_once plugin_dir_path(__FILE__) . "widget.php";

define('URL_FIELD', 'og_source_url');
define('REFRESH_FIELD', 'refresh_content');
add_action('acf/save_post', __NAMESPACE__.'\add_post_content_from_opengraph');
function add_post_content_from_opengraph(int $post_id): bool {

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
    $pid = PostFromOpenGraph($url, $post_id, get_post_type($post_id));
    return true;
}

add_shortcode('opengraph_content', __NAMESPACE__.'\display_opengraph_content');
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
    add_action("rest_after_insert_$ptype", __NAMESPACE__."\on_rest_after_insert", 10, 2);
}

function on_rest_after_insert($post, $request) {
    if (AdminError::hasError()) {
        $error = AdminError::wpError();
        AdminError::clear();
        wp_die($error->get_error_message());
    }
    return $post;
}
