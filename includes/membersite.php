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

		/* webinar の RSS にはメッセージを付けない。イベント日時を入れる */
		if ( 'webinar' === $p->post_type ) {
			$t = strtotime( get_field( 'time_start', $post_id ) );
			return date( 'n月d日(D) H:i 〜', $t ) . ' ' . $title;
		}

		/* feedタイプが、podcast の場合だけ */
		global $wp_query;
		if ( 'podcast' === $wp_query->query['feed'] ) {
			if ( ! current_user_can( 'wc_memberships_view_delayed_post_content', $post_id )
				|| ! current_user_can( 'wc_memberships_view_restricted_post_content', $post_id ) ) {

				$title = "【ご覧いただけません】 {$title}";
			}
		}
		return $title;
	},
	10,
	2
);

function sv_wc_memberships_restricted_message_remove_from_feeds( $message ) {
	global $wp_query;

	/* 原因がわからないが、nullにすると抜粋が丸ごと消えるので、spanを挿入 */
	if ( $wp_query instanceof \WP_Query && $wp_query->is_feed() ) {
		$message = '<span></span>';
	}

	return $message;
}
add_filter( 'wc_memberships_restricted_message', 'sv_wc_memberships_restricted_message_remove_from_feeds' );


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

