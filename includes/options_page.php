<?php
add_action('admin_menu',function(){
	add_options_page('Post From OG Settings', 'Post From Open Graph Settings','manage_options', 'post-from-og-setting','pog_settings');
});
function pog_settings(){
	if($_POST){
		$updated_settings = [];
		foreach(get_post_types() as $ptype ){
			if(isset($_POST["type-$ptype"])){
				$updated_settings[$ptype] = 1;
			}
		}	
		update_option('og_supported_types', $updated_settings);
	}
	?>
	<header>
		<h1>Post From Open Graph Settings</h1>
	</header>
	<main>
		<h2>Supported Post Types</h2>
		<form action="<?php echo admin_url("admin.php?page=post-from-og-setting"); ?>" method="POST">
			<fieldset> <?php foreach(get_post_types([], 'object') as $ptype ): ?>
				<label for="<?php echo $ptype->name; ?>">

					<input type="checkbox" name="type-<?php echo $ptype->name; ?>" id="<?php echo $ptype->name; ?>" <?php  if(isset(get_option("og_supported_types", ["post"=>1])[$ptype->name])) echo "checked"; ?> value="<?php echo $ptype->name; ?>"/>
				<span><?php echo $ptype->label; ?>	
				</label>	
			<?php endforeach; ?> </fieldset>	
			<input type="hidden" name="page" value="post-from-og-setting">
			<button class="is-primary button">Save</button>
		</form>
		<pre>
		<?php echo print_r(get_option('og_supported_types', ['post'=>1]), true); ?>
		</pre>
		
	</main>
<?php }

