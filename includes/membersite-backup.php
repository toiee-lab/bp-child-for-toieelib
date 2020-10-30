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


if ( function_exists( 'wc_memberships_is_product_viewing_restricted' ) ) {

	/**
	 * メンバーシップ・プランのタブに「Mailerlite連携」を追加
	 *
	 * @param [type] $tabs
	 * @return void
	 */
	function bplib_add_wcm_plan_data_tab( $tabs ) {
		$tabs['mailerlite'] = array(
			'label'  => 'Mailerlite連携',
			'target' => 'membership-plan-mailerlite',
		);

		return $tabs;
	}
	add_filter( 'wc_membership_plan_data_tabs', 'bplib_add_wcm_plan_data_tab', 10, 1 );

	/**
	 * メンバーシップ・プランのタブの「Mailerlite連携」に対応するコンテンツ、フォームを追加
	 *
	 * @return void
	 */
	function bplib_output_wcm_plan_data_tab() {
		global $post;

		$mailerlite_groups   = woo_ml_settings_get_group_options();
		$mailerlite_selected = get_post_meta( $post->ID, 'mailerlite_group_id', true );
		?>
	<div id="membership-plan-mailerlite" class="panel woocommerce_options_panel">
		<div class="options_group">
			<p>Mailerliteグループに、自動で登録、自動で解除を実行します。
			この機能を利用するには、WooCommerce Mailerlite公式プラグインをインストールし、設定してください。</p>
			<?php if ( function_exists( 'woo_ml_is_active' ) && woo_ml_is_active() ) : ?>
			<p class="form-field">
				<label for="_mailerlite_sections">Mailerliteグループ : </label>
				<select
					name="mailerlite_group_id"
					id="mailerlite_group_id"
					class="wc-enhanced-select-nostd"
					data-allow_clear="true"
					style="width: 90%;">
					<?php foreach ( $mailerlite_groups as $section_id => $section_name ) : ?>
						<option value="<?php echo esc_attr( $section_id ); ?>" <?php selected( true, $section_id == $mailerlite_selected ); ?>><?php echo esc_html( $section_name ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>Mailerliteのグループリストを更新するには、<a href="<?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=integration&section=mailerlite">Mailerlite連携の設定ページ</a>のIntegration DetailsのGroupsで再読み込みを行ってください。</p>
			<?php endif; ?>
		</div>
	</div>
		<?php
	}
	add_action( 'wc_membership_plan_data_panels', 'bplib_output_wcm_plan_data_tab' );

	/**
	 * Mailerlite連携のフォームの値を格納
	 *
	 * @param [type] $post_id
	 * @return void
	 */
	function bplib_save_wcm_plan_data_tab( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		$post = get_post( $post_id );

		if ( 'wc_membership_plan' == $post->post_type ) {
			update_post_meta( $post_id, 'mailerlite_group_id', sanitize_text_field( $_POST['mailerlite_group_id'] ) );
		}
	}
	add_action( 'save_post', 'bplib_save_wcm_plan_data_tab' );


	/** --------------------------------------------------------------------
	 * Mailerlite に連動して登録するためのフック
	 */
	if ( function_exists( 'woo_ml_is_active' ) && woo_ml_is_active() ) :
		/**
		 * メンバーシップの追加、更新のタイミングで、登録あるいは削除を実行する。
		 *
		 * @param [type] $membership_plan
		 * @param [type] $args
		 * @return void
		 */
		function bplib_mailerlite_add_remove( $membership_plan, $args ) {

			// membership_plan が指定されていなければ、何もしない
			if ( false == $membership_plan ) {
				return false;
			}

			$user_id            = $args['user_id'];
			$user_membership_id = $args['user_membership_id'];
			$is_update          = $args['is_update'];

			// 1. メンバーシップ IDから、Mailerliteのデータを取り出す
			$mailerlite_group_id = get_post_meta( $membership_plan->id, 'mailerlite_group_id', true );
			$mailerlite_groups   = woo_ml_settings_get_group_options();

			// 2. あれば処理続行、なければ処理終わり
			$flag = array_key_exists( $mailerlite_group_id, $mailerlite_groups);
			if ( ! array_key_exists( $mailerlite_group_id, $mailerlite_groups, true ) ) {
				return '';
			}

			// 3. user_membership_id でステータスをチェックする
			$user_membership = get_post( $user_membership_id );

			// 4. wcm-active なら登録する、
			if ( 'wcm-active' == $user_membership->post_status ) {
				bplib_ml_group_update( $user_id, $mailerlite_group_id );
			} else {
				bplib_ml_group_update( $user_id, $mailerlite_group_id, false );
			}
		}
		add_action( 'wc_memberships_user_membership_saved', 'bplib_mailerlite_add_remove', 10, 2 );

		function bplib_mailerlite_add_remove_from_status( $user_membership, $old_status, $new_status ) {
			// 1. $user_membership の post の post_parent を取り出す
			$membership_id = $user_membership->get_plan_id();

			// 2. post_parent の mailerliteデータを取り出す
			$mailerlite_group_id = get_post_meta( $membership_id, 'mailerlite_group_id', true );
			$mailerlite_groups   = woo_ml_settings_get_group_options();

			// 3. あれば処理続行、なければ終了
			if ( ! in_array( $mailerlite_group_id, $mailerlite_groups, true ) ) {
				return '';
			}

			// 4. new_status が wm-active なら登録
			// 5. new_status が wm-active以外 なら削除
			if ( 'wcm-active' == $new_status ) {
				bplib_ml_group_update( $user_membership->get_user_id(), $mailerlite_group_id );
			} else {
				bplib_ml_group_update( $user_membership->get_user_id(), $mailerlite_group_id, false );
			}

		}
		add_action( 'wc_memberships_user_membership_status_changed', 'bplib_mailerlite_add_remove_from_status', 10, 3 );

		function bplib_mailerlite_remove_at_delete_post( $post_id, $post ) {
			// post_parent を取り出す
			$membership_id = $post->post_parent;

			// mailerlite連携を持っているかチェックする
			$mailerlite_group_id = get_post_meta( $membership_id, 'mailerlite_group_id', true );
			$mailerlite_groups   = woo_ml_settings_get_group_options();

			// 3. あれば処理続行、なければ終了
			// 持っていれば、登録を削除する
			if ( ! in_array( $mailerlite_group_id, $mailerlite_groups, true ) ) {
				return '';
			} else {
				$user_id = $post->post_author;
				bplib_ml_group_update( $user_id, $mailerlite_group_id, false );
			}
		}
		add_action( 'delete_post', 'bplib_mailerlite_remove_at_delete_post', 10, 2 );

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
		 * ユーザーをアップデートする（必要なら追加する）
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


		// subscription の変更で動作させる
		add_action( 'woocommerce_order_status_changed', array( $this, 'update_mailerlite_group' ), 10, 3 );
		add_action( 'woocommerce_subscription_status_updated', array( $this, 'update_mailerlite_group_subscription' ), 10, 3 );

	endif;
}
