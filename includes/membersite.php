<?php

/**
 * ユーザー固有のトークンを生成、取得する
 *
 * @param string $user_id
 * @return void
 */
function bpcast_get_user_token( $user_id = '' ) {
	if ( $user_id === '' ) {
		$user = wp_get_current_user();
	} else {
		$user = get_user_by( 'ID', $user_id );
	}

	if ( false === $user || $user === 0 ) {
		return false;
	} else {
		$user_id = $user->ID;
	}

	$token = get_user_meta( $user_id, 'bpcast_token', true );

	if ( '' === $token ) {
		$stuck = str_split( 'abcdefghijklmnopqrstuvwxyz01234567890' );
		$token = '';
		$len   = count( $stuck );
		for ( $i = 0; $i < 8; $i++ ) {
			$token .= $stuck[ rand( 0, $len ) ];
		}

		update_user_meta( $user_id, 'bpcast_token', $token );
	}

	return $token;
}

/**
 * フィードを読み込むときに一度だけ実行される ssp_feed_access フィルターを利用して
 * ユーザーIDを取得。このユーザーIDは、エピソード（item）の閲覧権限のチェックに使います
 * $bpcast_user_id に格納。
 */
function bpcast_ssp_feed_access( $give_access, $series_id ) {
	global $bpcast_user_id;

	preg_match( '|/bpcast_token/([^/]+)/?|', $_SERVER['REQUEST_URI'], $matches );

	if ( ! isset( $matches[1] ) ) {
		$bpcast_user_id = false;
		return $give_access;
	}

	$token = $matches[1];

	$user_query = get_users(
		array(
			'meta_key'   => 'bpcast_token',
			'meta_value' => $token,
		)
	);

	if ( count( $user_query ) ) {
		$bpcast_user_id = $user_query[0]->ID;
		wp_set_current_user( $bpcast_user_id );
	} else {
		$bpcast_user_id = false;
	}

	return $give_access;
}
add_filter( 'ssp_feed_access', 'bpcast_ssp_feed_access', 10, 2 );


function bpcast_ssp_feed_item_enclosure( $enclosure, $id ) {
	global $bpcast_user_id;

	if ( false === $bpcast_user_id ) {
		return get_stylesheet_directory_uri() . '/restricted-message.m4v';
	} else {
		if ( ! current_user_can( 'wc_memberships_view_delayed_post_content', $id )
			|| ! current_user_can( 'wc_memberships_view_restricted_post_content', $id ) ) {
				return get_stylesheet_directory_uri() . '/restricted-message.m4v';
		}
		return $enclosure;
	}
}
add_filter( 'ssp_feed_item_enclosure', 'bpcast_ssp_feed_item_enclosure', 10, 2 );

/**
 * bpcast_token スラッグが、feed のクエリとして使われている場合は、削除する。
 *
 * @param [type] $args
 * @param [type] $context
 * @return void
 */
function bpcast_reset_args( $args, $context ) {
	if ( isset( $args['tax_query'] ) ) {
		foreach ( $args['tax_query'] as $i => $v ) {
			if ( 'bpcast_token' === $v['terms'] ) {
				unset( $args['tax_query'][ $i ] );
			}
		}
	}
	return $args;
}
add_filter( 'ssp_episode_query_args', 'bpcast_reset_args', 10, 2 );


add_filter(
	'the_title_rss',
	function( $title ) {
		if ( is_admin() ) {
			return $title;
		}

		$p       = get_post();
		$post_id = $p->ID;

		if ( ! current_user_can( 'wc_memberships_view_delayed_post_content', $post_id )
			|| ! current_user_can( 'wc_memberships_view_restricted_post_content', $post_id ) ) {

			$title = "【ご覧いただけません】 {$title}";
		}
		return $title;
	},
	10,
	2
);


function bpcast_wc_redirect() {
	$redirect_url = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	return $redirect_url;
}
add_filter( 'woocommerce_login_redirect', 'bpcast_wc_redirect' );

/**
 * 管理バーを表示しない
 */
add_filter(
	'show_admin_bar',
	function ( $content ) {
		return ( current_user_can( 'administrator' ) ) ? $content : false;
	}
);

// 登録フォームに「名前」と「苗字」を挿入
add_action(
	'woocommerce_register_form_start',
	function () {?>
		<p class="form-row form-row-first">
			<label for="reg_billing_last_name"><?php _e( 'Last name', 'woocommerce' ); ?><span class="required">*</span></label>
			<input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php
			if ( ! empty( $_POST['billing_last_name'] ) ) {
				esc_attr_e( $_POST['billing_last_name'] );
			}
			?>" />
		</p>
		<p class="form-row form-row-last">
			<label for="reg_billing_first_name"><?php _e( 'First name', 'woocommerce' ); ?></label>
			<input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php
			if ( ! empty( $_POST['billing_first_name'] ) ) {
				esc_attr_e( $_POST['billing_first_name'] );
			}
			?>" />
		</p>        
		<div class="clear"></div>
	   
		<?php
	}
);

// 登録フォームのバリデーション
add_action(
	'woocommerce_register_post',
	function ( $username, $email, $validation_errors ) {
		if ( isset( $_POST['billing_last_name'] ) && empty( $_POST['billing_last_name'] ) ) {
			$validation_errors->add( 'billing_last_name_error', __( '<strong>Error</strong>: Last name is required!.', 'woocommerce' ) );

		}
		return $validation_errors;
	},
	10,
	3
);

// 登録フォームに追加したデータの保存
add_action(
	'woocommerce_created_customer',
	function ( $customer_id ) {
		if ( isset( $_POST['billing_first_name'] ) ) {
			   // First name field which is by default
			   update_user_meta( $customer_id, 'first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
			   // First name field which is used in WooCommerce
			   update_user_meta( $customer_id, 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
		}
		if ( isset( $_POST['billing_last_name'] ) ) {
			   // Last name field which is by default
			   update_user_meta( $customer_id, 'last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
			   // Last name field which is used in WooCommerce
			   update_user_meta( $customer_id, 'billing_last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
		}

	}
);

