<?php

namespace OpengraphPost;

use WP_Error;

function PostFromOpenGraph(string $url, ?int $post_id = null, ?string $type = "post"): bool|int {
    error_log("Running ". __FUNCTION__);
    $html = fetch_content_with_browser_headers($url);
    if (is_wp_error($html)) {
        error_log("html is a error");
        error_log($html->get_error_code() . "\n " . print_r($html->get_all_error_data($html->get_error_code()), true));
        return false;
    }

    $opengraph = OpenGraph::parse($html);
    if (!$opengraph) {
        $error_message = "We were unable to grab any opengraph data you will need to add the information by hand.";
        AdminError::error($error_message);
        return false;
    }

    remove_action('save_post', __NAMESPACE__.'\add_post_content_from_opengraph');

    $updated_post = array(
        'post_excerpt' => $opengraph->description,
        'post_title'   => $opengraph->title,
        'post_type'   => $type,
        'post_status' => 'publish',
    );
    if (isset($post_id)) {
        $updated_post['ID'] = $post_id;
        $post_id = wp_update_post($updated_post, true);
    } else {
        $post_id = wp_insert_post($updated_post, true);
    }

    if (is_wp_error($post_id)) {
        AdminError::error($post_id->get_error_message());
        return false;
    }

    update_post_meta($post_id, 'og_source_description', $opengraph->description);
    if ($opengraph->{"video:url"}) {
        update_post_meta($post_id, 'og_source_video', $opengraph->{"video:url"});
    }
    if ($opengraph->icon) {
        update_post_meta($post_id, 'og_source_icon', $opengraph->icon);
    }
    $image_id = add_to_media($opengraph->image);
    if ($image_id) {
        set_post_thumbnail($post_id, $image_id);
    } else {
        AdminError::error("No Image could be found");
        error_log('image_id is null: ' . $image_id);
    }

    update_field('refresh_content', false, $post_id);
    update_field(URL_FIELD, $url, $post_id);

    return $post_id;
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




function fetch_content_with_browser_headers($url) {
    // Define the headers to mimic a real browser
    $args = array(
        'headers' => array(
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, defalte',
            'Connection'      => 'keep-alive',
            'Referer'         => 'https://zekehernandez.net',  // Optional, change if needed
        ),
        'sslverify' => false,
        'redirects' => 0,
        'timeout' => 30,

    );

    // Perform the GET request
    $response = wp_remote_get($url, $args);

    // Check for an error
    if (is_wp_error($response)) {
        return $response;
    }

    // Get the response body
    $body = wp_remote_retrieve_body($response);

    // Return the body content
    return $body;
}
