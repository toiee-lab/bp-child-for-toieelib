<?php
/*
 * ACF Pro でオプションページを作成する
 */

if( function_exists('acf_add_options_page') ) {
	
	acf_add_options_page(array(
		'page_title' 	=> 'カメラジ設定',
		'menu_title'	=> 'カメラジ設定',
		'menu_slug' 	=> 'theme-general-settings',
		'capability'	=> 'edit_posts',
		'icon_url' => 'dashicons-money',
		'redirect'		=> false
	));
}
