# Create Posts From Opengraph Meta

## Installation and Setup

1. Install zip as a wp plugin

2. Select the post types you would like to create posts from opengraph meta on the settings page

https://YOUR-SITE/wp-admin/options-general.php?page=post-from-og-setting

3. Create a post

   1. Add a title

   2. Add the url the the Field labled "Opengraph Source URL"

   3. Save the Post. This will trigger the action to go and get the opengraph meta and save it to the post

4. Add the Shortcodes

Either in the template or in post content add the shortcode `[opengraph_content get=description]` or `[opengraph_content get=video]`

5. All done 🥳.
