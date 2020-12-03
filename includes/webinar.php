<?php
/**
 * Webinarのためのスクリプト
 *
 * @package kameradio
 */

/**
 * Webinarの表示される日付を無理矢理、開始日時に設定
 */
add_filter(
	'get_the_date',
	function( $the_date, $format, $post ) {
		if ( is_admin() ) {
			return $the_date;
		}

		if ( 'webinar' === $post->post_type && '' === $format ) {
			$d       = get_field( 'time_start', $post );
			$t       = strtotime( $d );
			$_format = ! empty( $format ) ? $format : get_option( 'date_format' );
			$the_d   = date_i18n( $_format . '(D)', $t );
			return $the_d;
		} else {
			return $the_date;
		}
	},
	10,
	3
);

/**
 * ウェビナーのチケットを削除するための処理をフックで追加
 */
add_filter( 'acf/save_post', 'possibly_delete_post' );
/**
 * 投稿を削除できるメソッドを作成
 *
 * @param object $post_id オブジェクト.
 * @return void
 */
function possibly_delete_post( $post_id ) {
	$post_type = get_post_type( $post_id );
	// change to post type you want them to be able to delete
	if ( 'webinar-ticket' !== $post_type ) {
		return;
	}

	if ( isset( $_POST['acf']['delete_this_post'] ) &&  $_POST['acf']['delete_this_post'] ) {
		$force_delete = true;
		wp_delete_post( $post_id, $force_delete );
	}
}

/**
 * 指定されたwebinarのチケットの投稿オブジェクトが格納された配列を返す
 *
 * @param int $webinar_id Webinar ID.
 * @return array チケット投稿オブジェクトが格納された配列.
 */
function get_tickets( $webinar_id ) {
	$tickets = get_posts(
		array(
			'post_type'      => 'webinar-ticket',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => 'webinar',
					'value' => $webinar_id,
				),
			),
		)
	);

	return $tickets;
}

/**
 * チケットを持っているかを判定し、結果を返す。
 *
 * @param int   $current_user_id User id.
 * @param array $tickets チケットオブジェクトが格納された配列.
 * @return array 参加者, チケットid.
 */
function has_ticket( $current_user_id, $tickets ) {
	$current_user_id = ( wp_get_current_user() )->ID;
	$is_attendee     = false;
	$ticket_id       = false;
	foreach ( $tickets as $ticket ) {
		if ( $ticket->post_author === $current_user_id ) {
			$is_attendee = true;
			$ticket_id   = $ticket->ID;
			break;
		}
	}

	return array( $is_attendee, $ticket_id );
}

/**
 * 予約可能かをチェックする
 *
 * @param int    $now チェックしたい時間（unix time）.
 * @param string $close_option 募集締め切りのオプション値.
 * @param int    $end_time イベントの終了時間.
 * @return array 予約可能、メッセージ.
 */
function can_reserve( $now_time, $close_option, $close_time, $end_time ) {
	switch ( $close_option ) {
		case '10min':
			$close_time -= 60 * 10;
			$close_msg   = '10分前まで';
			break;
		case '30min':
			$close_time -= 60 * 30;
			$close_msg   = '30分前まで';
			break;
		case '1d':
			$close_time  = strtotime( date( 'Y-m-d 23:59:59', $close_time ) );
			$close_time -= 24 * 60 * 60;
			$close_msg   = '前日まで';
			break;
		case '3d':
			$close_time  = strtotime( date( 'Y-m-d 23:59:59', $close_time ) );
			$close_time -= 3 * 24 * 60 * 60;
			$close_msg   = '3日前まで';
			break;
		default:
			$close_time = $end_time;
			$close_msg  = '途中参加OK';
			break;
	}
	$can_reserve = $now_time < $close_time;

	return array( $can_reserve, $close_time, $close_msg );
}

/**
 * acf
 */
if( function_exists('acf_add_local_field_group') ):

	acf_add_local_field_group(array(
		'key' => 'group_5f9bc6bbc72e7',
		'title' => 'ウェビナー',
		'fields' => array(
			array(
				'key' => 'field_5f9bc6cf4ec70',
				'label' => '募集中',
				'name' => 'open',
				'type' => 'true_false',
				'instructions' => 'イベントが募集中である状態',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'message' => '募集中にする。期間が過ぎていれば募集中ではなくなります',
				'default_value' => 1,
				'ui' => 1,
				'ui_on_text' => '',
				'ui_off_text' => '',
			),
			array(
				'key' => 'field_5f9bc71d4ec71',
				'label' => '開始時間',
				'name' => 'time_start',
				'type' => 'date_time_picker',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'display_format' => 'Y年m月d日 H:i',
				'return_format' => 'Y-m-d H:i:s',
				'first_day' => 1,
			),
			array(
				'key' => 'field_5f9bc7824ec73',
				'label' => '終了時間',
				'name' => 'time_end',
				'type' => 'date_time_picker',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'display_format' => 'Y年m月d日 H:i',
				'return_format' => 'Y-m-d H:i:s',
				'first_day' => 1,
			),
			array(
				'key' => 'field_5f9bc7be4ec75',
				'label' => '締め切り',
				'name' => 'time_close',
				'type' => 'select',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'choices' => array(
					'0'     => '終了まで',
					'10min' => '10分前',
					'30min' => '30分前',
					'1h' => '1時間前',
					'1d' => '1日前',
					'3d' => '3日前',
				),
				'default_value' => '0',
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'ajax' => 0,
				'placeholder' => '',
			),
			array(
				'key' => 'field_5f9bc7ed4ec76',
				'label' => '募集人数',
				'name' => 'limit',
				'type' => 'number',
				'instructions' => '参加者の人数',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => 10,
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'min' => '',
				'max' => '',
				'step' => '',
			),
			array(
				'key' => 'field_5f9bc8194ec77',
				'label' => '参加者メッセージ',
				'name' => 'message',
				'type' => 'wysiwyg',
				'instructions' => '申し込みされた方だけに見せるコンテンツ。WebinarのURLなどを貼り付けてください。、',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'tabs' => 'all',
				'toolbar' => 'full',
				'media_upload' => 1,
				'delay' => 0,
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'webinar',
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
	
	acf_add_local_field_group(array(
		'key' => 'group_5f9c24eb3fee6',
		'title' => 'チケット',
		'fields' => array(
			array(
				'key' => 'field_5f9c24f2276d4',
				'label' => 'ウェビナー',
				'name' => 'webinar',
				'type' => 'relationship',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'post_type' => array(
					0 => 'webinar',
				),
				'taxonomy' => '',
				'filters' => array(
					0 => 'search',
				),
				'elements' => '',
				'min' => '',
				'max' => '',
				'return_format' => 'object',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'webinar-ticket',
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
 * ctp
 */
function cptui_register_my_cpts() {

	/**
	 * Post Type: ウェビナー.
	 */

	$labels = [
		"name" => __( "ウェビナー", "businesspress" ),
		"singular_name" => __( "ウェビナー", "businesspress" ),
	];

	$args = [
		"label" => __( "ウェビナー", "businesspress" ),
		"labels" => $labels,
		"description" => "ウェビナーイベントのための投稿",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"has_archive" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => [ "slug" => "webinar", "with_front" => true ],
		"query_var" => true,
		"menu_icon" => "dashicons-tickets",
		"supports" => [ "title", "editor", "thumbnail" ],
	];

	register_post_type( "webinar", $args );

	/**
	 * Post Type: チケット.
	 */

	$labels = [
		"name" => __( "チケット", "businesspress" ),
		"singular_name" => __( "チケット", "businesspress" ),
	];

	$args = [
		"label" => __( "チケット", "businesspress" ),
		"labels" => $labels,
		"description" => "Webinarの参加チケット",
		"public" => false,
		"publicly_queryable" => false,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"has_archive" => false,
		"show_in_menu" => "edit.php?post_type=webinar",
		"show_in_nav_menus" => false,
		"delete_with_user" => false,
		"exclude_from_search" => true,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => false,
		"query_var" => false,
		"supports" => [ "title", "author" ],
	];

	register_post_type( "webinar-ticket", $args );
}

add_action( 'init', 'cptui_register_my_cpts' );
