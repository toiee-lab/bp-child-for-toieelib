<?php

/**
 * オプションページを作成
 */
if( function_exists('acf_add_options_page') ) {

	acf_add_local_field_group(array(
		'key' => 'group_5fb61ca2aaa2d',
		'title' => 'HelpScout Beacon',
		'fields' => array(
			array(
				'key' => 'field_5fb61cc7bbb2a',
				'label' => 'ログイン前のBeacon ID',
				'name' => 'beacon_id_logged_out',
				'type' => 'text',
				'instructions' => 'ログイン前に表示したいHelpScout Beaconを作成して、そのIDを貼り付けてください',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'maxlength' => '',
			),
			array(
				'key' => 'field_5fb61d49bbb2c',
				'label' => 'ログイン後のBeacon ID',
				'name' => 'beacon_id_logged_in',
				'type' => 'text',
				'instructions' => 'ログイン後に表示したいHelpScout Beaconを作成して、そのIDを貼り付けてください',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'maxlength' => '',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'options_page',
					'operator' => '==',
					'value' => 'theme-general-settings',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => '',
	));
		
	


	add_action(
		'wp_head',
		function (){
			if ( is_user_logged_in() ) {
				$beacon_id = get_field( 'beacon_id_logged_in', 'option' );
				if ( '' != $beacon_id ) {
					$current_user = wp_get_current_user();
					$name         = $current_user->user_lastname . ' ' . $current_user->user_firstname;
					
					$name = ( '' === trim( $name ) ) ? '名前登録なし' : $name;
					?>
	<script type="text/javascript">!function(e,t,n){function a(){var e=t.getElementsByTagName("script")[0],n=t.createElement("script");n.type="text/javascript",n.async=!0,n.src="https://beacon-v2.helpscout.net",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],"complete"===t.readyState)return a();e.attachEvent?e.attachEvent("onload",a):e.addEventListener("load",a,!1)}(window,document,window.Beacon||function(){});</script>
	<script type="text/javascript">
		window.Beacon('init', '<?php echo $beacon_id; ?>');
		Beacon('identify', {
			name: '<?php echo $name; ?>',
			email: '<?php echo esc_html( $current_user->user_email ); ?>',
		});
		<?php do_action( 'beacon_logged_in_script' ); ?>
	</script>
					<?php
				}
			} else {
				$beacon_id = get_field( 'beacon_id_logged_out', 'option' );
				if ( '' != $beacon_id ) {
					?>
	<script type="text/javascript">!function(e,t,n){function a(){var e=t.getElementsByTagName("script")[0],n=t.createElement("script");n.type="text/javascript",n.async=!0,n.src="https://beacon-v2.helpscout.net",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],"complete"===t.readyState)return a();e.attachEvent?e.attachEvent("onload",a):e.addEventListener("load",a,!1)}(window,document,window.Beacon||function(){});</script>
	<script type="text/javascript">window.Beacon('init', '<?php echo $beacon_id; ?>');</script>
					<?php
				}
			}
		}
	);
}
