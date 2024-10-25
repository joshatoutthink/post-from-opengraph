
<?php if( function_exists('acf_add_local_field_group') ):

$locations = [];
foreach(get_option('og_supported_types',['post'=>1]) as $ptype=>$value){
	$locations[][]=array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => $ptype,
				);
}


$res = acf_add_local_field_group(array(
	'key' => 'group_62977bac07eb9',
	'title' => 'Post From Opengraph Data',
	'fields' => array(
		array(
			'key' => 'field_629908ffa2c47',
			'label' => 'Refresh Content',
			'name' => 'refresh_content',
			'type' => 'true_false',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_62977bf8d5d70',
						'operator' => '!=empty',
					),
				),
			),
			'wrapper' => array(
				'width' => '33',
				'class' => '',
				'id' => '',
			),
			'message' => '',
			'default_value' => 1,
			'ui' => 0,
			'ui_on_text' => '',
			'ui_off_text' => '',
		),
		array(
			'key' => 'field_62977bf8d5d70',
			'label' => 'Opengraph Source URL',
			'name' => 'og_source_url',
			'type' => 'url',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '66',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
		),
	),
	'location' => $locations,
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
	'show_in_rest' => 1,
));
endif;		
