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


/** --------------------------------------------------------------------
 * Mailerlite に連動して登録するためのフック
 */
if ( function_exists( 'woo_ml_is_active' ) && woo_ml_is_active() ) :

	/*
	 * WooCommerceの商品ページに、関連するMailerliteグループを指定するための設定
	 */
	function bplib_add_ml_select() {

		// options から値を取得しておく
		$mailerlite_groups = woo_ml_settings_get_group_options();

		// 表示
		woocommerce_wp_select(
			array(
				'id'      => '_mailerlite_group',
				'label'   => __( 'MailerLiteグループ', 'wc-ext-toiee' ),
				'options' => $mailerlite_groups,
			)
		);
		?>
		<p>Mailerliteのグループリストを更新するには、<a href="<?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=integration&section=mailerlite">Mailerlite連携の設定ページ</a>のIntegration DetailsのGroupsで再読み込みを行ってください。</p>
		<?php
	}
	add_action( 'woocommerce_product_options_general_product_data', 'bplib_add_ml_select' );

	function bplib_save_ml_select( $post_id ){
		$woocommerce_select = $_POST['_mailerlite_group'];
		update_post_meta( $post_id, '_mailerlite_group', esc_attr( $woocommerce_select ) );
	}
	add_action( 'woocommerce_process_product_meta', 'bplib_save_ml_select' );

	function bplib_add_ml_select_variation( $loop, $variation_data, $variation ) {
		// options から値を取得
		$value  = get_post_meta( $variation->ID, '_variation_mailerlite_group', '' );
		$groups = woo_ml_settings_get_group_options();

		// 表示
		woocommerce_wp_select(
			array(
				'id'      => '_variation_mailerlite_group[' . $variation->ID . ']',
				'label'   => __( 'MailerLiteグループ', 'woocommerce' ),
				'options' => $groups,
				'value'   => $value,
			)
		);
	}
	add_action( 'woocommerce_product_after_variable_attributes', 'bplib_add_ml_select_variation', 10, 3 );

	function bplib_save_ml_select_variation( $variation_id ){
		$woocommerce_select = $_POST['_variation_mailerlite_group'][ $variation_id ];

		if ( ! empty( $woocommerce_select ) ) {
			update_post_meta( $variation_id, '_variation_mailerlite_group', esc_attr( $woocommerce_select ) );
		} else {
			delete_post_meta( $variation_id, '_variation_mailerlite_group' );
		}
	}
	add_action( 'woocommerce_save_product_variation', 'bplib_save_ml_select_variation', 10, 2 );


	// 注文の状態変化を検知する
	function bplib_update_mailerlite_group( $order_id, $old_status, $new_status ){
		if ( $new_status == 'completed' ) {         // completed なら登録を実行
			bplib_ml_group_from_order( $order_id );
		} elseif ( $old_status == 'completed' ) { // 削除を実行
			bplib_ml_group_from_order( $order_id, false );
		}
	}
	add_action( 'woocommerce_order_status_changed', 'bplib_update_mailerlite_group', 10, 3 );

	function bplib_update_mailerlite_group_subscription( $subscription, $new_status, $old_status ){
		$order_id = $subscription->get_order_number();

		// istallement の場合、expire を active として処理する
		$order = wc_get_order( $order_id );
		foreach ( $order->get_items() as $item_id => $item_values ) {
			$product_id  = $item_values->get_product_id();
			$installment = get_post_meta( $product_id, '_installment_subscription', true );
			if ( $installment && $new_status == 'expired' ) {
				$new_status = 'active';
				break;
			}
		}

		if ( $new_status == 'active' ) {
			bplib_ml_group_from_order( $order_id );
		} elseif ( $old_status == 'active' ) {
			bplib_ml_group_from_order( $order_id, false );
		}

	}
	add_action(	'woocommerce_subscription_status_updated', 'bplib_update_mailerlite_group_subscription', 10, 3 );

	function bplib_ml_group_from_order( $order_id, $add = true ) {
		$order   = wc_get_order( $order_id );
		$user_id = $order->get_customer_id();

		foreach ( $order->get_items() as $item_id => $item_values ) {

			// 準備
			$product_id = $item_values->get_product_id();
			$data       = $item_values->get_data();

			// 登録先を探す (variation を考慮）
			if ( isset( $data['variation_id'] ) && $data['variation_id'] != 0 ) {  // variation なら
				$gid = get_post_meta( $data['variation_id'], '_variation_mailerlite_group', true );
			} else { // 通常なら
				$gid = get_post_meta( $product_id, '_mailerlite_group', true );
			}

			// 登録 or 削除
			if ( $gid ) {
				$groupsApi = ( new \MailerLiteApi\MailerLite( MAILERLITE_WP_API_KEY ) )->groups();
				$subscriber = bplib_ml_group_update( $user_id, $gid, $add );
			}
		}
	}

	function bplib_ml_group_update( $user_id, $group_id, $add = true ) {
		try {
			if ( $add ) {
				$subscriber = bplib_update_user( $user_id );
				$groupsApi  = ( new \MailerLiteApi\MailerLite( MAILERLITE_WP_API_KEY ) )->groups();
				$subscriber = $groupsApi->addSubscriber( $group_id, $subscriber );
			} else {
				$groupsApi  = ( new \MailerLiteApi\MailerLite( MAILERLITE_WP_API_KEY ) )->groups();
				$subscriber = $groupsApi->removeSubscriber( $group_id, $subscriber->id );
			}	
		} catch ( \MailerLiteApi\Exceptions\MailerLiteSdkException $e ) {
			// TODO
		} catch ( Exception $e ) {
			// TODO
		}
	}

	/**
	 * ユーザーをアップデートする（必要なら追加する）。ユーザー登録した時点で、指定されたグループに追加する
	 * subscriber を返す
	 *
	 * @param  $user_id
	 * @return array
	 * @throws \MailerLiteApi\Exceptions\MailerLiteSdkException
	 */
	function bplib_update_user( $user_id, $load_address = '' ) {
		// get WordPress user data
		$user_data      = get_userdata( $user_id );
		$user_meta_data = get_metadata( 'user', $user_id, '', true );

		// generate data for mailerlite
		$email  = $user_data->user_email;
		$fields = array(
			'name'         => $user_meta_data['first_name'][0],
			'last_name'    => $user_meta_data['last_name'][0],
			// 'company'   => $user_meta_data['last_name'][0],
			'country'      => $user_meta_data['billing_country'][0],
			'city'         => $user_meta_data['billing_city'][0],
			'phone'        => $user_meta_data['billing_phone'][0],
			'state'        => $user_meta_data['billing_state'][0],
			'zip'          => $user_meta_data['billing_postcode'][0],
			'kameradio_id' => $user_id,
		);

		if ( '' === $fields['name'] && isset( $_POST['billing_first_name'] ) ) {
			$fields['name'] = $_POST['billing_first_name'];
		}
		if ( '' === $fields['last_name'] && isset( $_POST['billing_last_name'] ) ) {
			$fields['last_name'] = $_POST['billing_last_name'];
		}

		// user check
		$subscribersApi = ( new \MailerLiteApi\MailerLite( MAILERLITE_WP_API_KEY ) )->subscribers();
		try {
			$subscriber = $subscribersApi->find( $email );
		} catch ( Exception $e ) {
			// TODO ユーザーがアップデートできなかったときの処理
		}

		if ( isset( $subscriber->error ) ) { // ユーザーがいないなら、登録
			$subscriber = array(
				'email'  => $email,
				'fields' => $fields,
			);

			$group_id = woo_ml_get_option( 'group' );
			$groupsApi  = ( new \MailerLiteApi\MailerLite( MAILERLITE_WP_API_KEY ) )->groups();
			$subscriber = $groupsApi->addSubscriber( $group_id, $subscriber );

			return $subscriber;
		} else { // 更新する

				$subscriberEmail = $email;
				$subscriberData  = array(
					'fields' => $fields,
				);

				$subscriber = $subscribersApi->update( $subscriberEmail, $subscriberData ); // returns object of updated subscriber

				return $subscriber;
		}
	}
	// ユーザーのプロフィール設定
	add_action( 'woocommerce_save_account_details', 'bplib_update_user', 10, 1 );
	add_action( 'woocommerce_checkout_update_user_meta', 'bplib_update_user', 10, 1 );
	add_action( 'woocommerce_customer_save_address', 'bplib_update_user', 10, 2 );

	add_action( 'personal_options_update', 'bplib_update_user', 10, 1 );
	add_action( 'edit_user_profile_update', 'bplib_update_user', 10, 1 );
	add_action( 'user_register', 'bplib_update_user', 99, 1 ); // ユーザーの作成

endif;
