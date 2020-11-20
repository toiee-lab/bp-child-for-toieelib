<?php

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
