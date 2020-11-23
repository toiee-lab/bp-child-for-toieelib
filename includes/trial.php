<?php
/**
 * トライアルメンバーのときに、メッセージを表示する
 */
if( function_exists('acf_add_options_page') &&  function_exists( 'wc_memberships_get_user_active_memberships' ) ) :

	function tlib_user_is_trial() {
		$user_id     =  get_current_user_id();
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
		if ( $mem_ids[ $trial ] ) {
			return true;
			echo "Beacon('show-message', '{$trial_msgid}' );\n";
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

endif;
