<?php
/**
 * トライアルメンバーのときに、メッセージを表示する
 */
if( function_exists('acf_add_options_page') &&  function_exists( 'wc_memberships_get_user_active_memberships' ) ) :

	function tlib_user_is_trial( $return_end_date = false ) {
		$user_id     = get_current_user_id();
		$memberships = wc_memberships_get_user_active_memberships( $user_id );
		$premiums    = get_field( 'premium_member_plans', 'option' );
		$trial       = get_field( 'trial_member_plan', 'option' );
		$trial_msgid = get_field( 'trial_message_id', 'option' );

		$mem_ids = array();
		foreach ( $memberships as $m ) {
			$mem_ids[$m->plan_id] = $m;
		}

		// プレミアム・メンバーを持っていたら「return ''」する
		foreach ( $premiums as $prm ) {
			if ( isset( $mem_ids[ $prm ] ) ) {
				return false;
			}
		}

		// トライアルを持っていたら、メッセージを出す
		if ( isset( $mem_ids[ $trial ] ) ) {
			if ( $return_end_date ) {
				$id       = ($mem_ids[ $trial ])->id;
				$end_date = get_post_meta( $id, '_end_date', true );
				return $end_date;
			} else {
				return true;
			}
		}

		return false;
	}

	add_action(
		'beacon_logged_in_script',
		function (){
			if( tlib_user_is_trial() ) {
				$trial_msgid = get_field( 'trial_message_id', 'option' );
				$user_id     = get_current_user_id();
				$acc_time    = get_user_meta( $user_id, 'kmr_acc_time', true );

				$interval_hour = apply_filters( 'kmr_interval_hour', 12 );
				if ( '' == $acc_time || (60 * 60 * $interval_hour) < (time() - $acc_time) ) {
					echo "Beacon('show-message', '{$trial_msgid}', { force: true } );\n";
					update_user_meta( $user_id, 'kmr_acc_time', time() );
				} else {
					echo "Beacon('show-message', '{$trial_msgid}' );\n";
				}
			}
		}
	);

	add_filter(
		'body_class',
		function( $classes ) {
			if ( tlib_user_is_trial() ) {
				$classes[] = 'user_is_trial';
				return $classes;
			}
			return $classes;
		}
	);

	acf_add_local_field_group(array(
		'key' => 'group_5fb7ba7b14cf6',
		'title' => 'トライアル設定',
		'fields' => array(
			array(
				'key' => 'field_5fb7ee3033418',
				'label' => 'トライアルプラン',
				'name' => 'trial_member_plan',
				'type' => 'post_object',
				'instructions' => 'トライアルの会員権限を指定してください',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'post_type' => array(
					0 => 'wc_membership_plan',
				),
				'taxonomy' => '',
				'allow_null' => 0,
				'multiple' => 0,
				'return_format' => 'id',
				'ui' => 1,
			),
			array(
				'key' => 'field_5fb92bf401329',
				'label' => '上位プラン',
				'name' => 'premium_member_plans',
				'type' => 'post_object',
				'instructions' => '有料プランを指定してください。既に有料プランに入っている人にメッセージを出させないために指定します。',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'post_type' => array(
					0 => 'wc_membership_plan',
				),
				'taxonomy' => '',
				'allow_null' => 0,
				'multiple' => 1,
				'return_format' => 'id',
				'ui' => 1,
			),
			array(
				'key' => 'field_5fb932aa78aed',
				'label' => 'トライアル向け Message ID',
				'name' => 'trial_message_id',
				'type' => 'text',
				'instructions' => 'トライアル向けに作成した メッセージの id を記載します。記載しなければ、使いません。',
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
	
endif;


/**
 * トライアルバーを表示するための設定
 */
function tlib_trial_trial_bar( $wp_customize ) {
	// Top Bar
	$wp_customize->add_section( 'kameradio_trial_bar', array(
		'title'    => esc_html__( 'トライアル・バー', 'kameradio' ),
		'priority' => 50,
	) );
	$wp_customize->add_setting( 'kameradio_enable_trial_bar', array(
		'default'           => '',
		'sanitize_callback' => 'businesspress_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'kameradio_enable_trial_bar', array(
		'label'    => esc_html__( 'Enable Trial Bar', 'kameradio' ),
		'section'  => 'kameradio_trial_bar',
		'type'     => 'checkbox',
		'priority' => 1,
	) );
	$wp_customize->add_setting( 'kameradio_trial_bar_text', array(
		'default'           => '',
		'sanitize_callback' => 'wp_kses',
	) );
	$wp_customize->add_control( 'kameradio_trial_bar_text', array(
		'label'    => esc_html__( 'Message', 'kameradio' ),
		'section'  => 'kameradio_trial_bar',
		'type'     => 'text',
		'priority' => 2,
	) );
}
add_action( 'customize_register', 'tlib_trial_trial_bar' );

/**
 * トライアルメッセージを表示する
 *
 * @return void
 */
function kameradio_top_bar_trial_message() {
	$_end_date = tlib_user_is_trial( true );
	$end_date  = date( 'Y/m/d', strtotime( $_end_date ) );
	$remain    = floor( ( strtotime( $_end_date ) - time() ) / 60 / 60 / 24 );
	$message   = get_theme_mod( 'kameradio_trial_bar_text' );
	$message   = get_theme_mod( 'kameradio_trial_bar_text' );

	$message = str_replace( array( '%remain%', '%end_date%' ), array( $remain, $end_date ), $message );
	echo $message;
}