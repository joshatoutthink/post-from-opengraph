<?php

// Hook to add the dashboard widget

namespace OpengraphPost;

// use function OpengraphPost\PostFromOpenGraph;
// use function OpengraphPost\supported_post_types;

add_action('wp_dashboard_setup', __NAMESPACE__.'\custom_dashboard_widget');

function custom_dashboard_widget() {
    wp_add_dashboard_widget(
        'og_quick_link',    // Widget slug
        'Add a link',         // Title
       __NAMESPACE__.'\og_new_link_widget_form'  // Display callback
    );
}

// Function to display the widget content
function og_new_link_widget_form() {
    // Check if the form has been submitted
    if (isset($_POST['create_og_link_nonce']) && wp_verify_nonce($_POST['create_og_link_nonce'], 'create_og_link')) {
        create_link_from_form();
    }
    $ptypes = supported_post_types();
    // Display the form
?>
    <form method="post" action="">
        <p>
            <label for="url">Link Url:</label><br>
            <input type="url" id="url" name="og_link_url" value="" style="width:100%;" required />
        </p>

        <p>
            <label for="post_type">Post Type</label><br>
            <select id="post_type" name="og_link_ptype">
                <?php foreach (array_keys($ptypes) as $ptype) { ?>
                    <option value="<?= $ptype; ?>"><?= $ptype; ?></option>
                <?php } ?>
            </select>
        </p>

        <p>
            <input type="submit" value="Create Link" class="button-primary">
        </p>
        <?php wp_nonce_field('create_og_link', 'create_og_link_nonce'); ?>
    </form>
<?php
}

// Function to handle form submission and create the post
function create_link_from_form() {
    // Get form data
    $url = sanitize_text_field($_POST['og_link_url']);
    $pid = PostFromOpenGraph($url, type:$_POST['og_link_ptype']);

    if ($pid) {
        echo '<div class="updated notice is-dismissible"><p>Post created successfully!</p><a href=' . get_edit_post_link($pid) . '>Edit</a></div>';
    } else {
        echo '<div class="error notice is-dismissible"><p>Failed to create post. Please try again.</p></div>';
    }
}
