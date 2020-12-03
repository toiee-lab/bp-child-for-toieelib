<?php
/**
 * フロントエンド関連の関数やフックの設定
 */

/**
 * Plyr.io のスタイルやスクリプトを読み込む
 */
add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_style( 'plyrio', get_theme_file_uri( 'assets/plyr.io/plyr.css' ) );
		wp_enqueue_script( 'plyrio', get_theme_file_uri( 'assets/plyr.io/plyr.js' ), array(), '3.5.3', true );
		wp_enqueue_script( 'plyrio-enable', get_theme_file_uri( 'assets/plyr-enable.js' ), array( 'plyrio' ), '1.0', true );
	}
);

/**
 * Podcastの場合、サイドバーを消す
 *
 * @param [type] $classes
 * @return void
 */
function bpcast_body_class_filter( $classes ) {
	if ( get_post_type() === 'podcast' ) {
		if ( $index = array_search( 'has-sidebar', $classes ) ) {
			unset( $classes[ $index ] );
		}
	}

	return $classes;
}
add_filter( 'body_class', 'bpcast_body_class_filter', 20 );

/**
 * Podcastのページで、シリーズへのパンくずリストのため
 *
 * @param [type] $prefix
 * @return void
 */
function bpcast_get_series() {
	$p     = get_post();
	$terms = get_the_terms( $p->ID, 'series' );

	$ret = array();
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$url   = get_term_link( $term, 'series' );
			$ret[] = '<a href="' . $url . '">' . $term->name . '</a>';
		}
	}

	return implode( ', ', $ret );
}

function bpcast_player( $content ) {
	if ( ! is_admin() && is_main_query() ) {
		if ( get_post_type() === 'podcast' || is_post_type_archive( 'podcast' ) ) {
			$user  = wp_get_current_user();
			$p     = get_post();
			$terms = get_the_terms( $p->ID, 'series' );

			$post_id = $p->ID;
			if ( ! current_user_can( 'wc_memberships_view_delayed_post_content', $post_id )
				|| ! current_user_can( 'wc_memberships_view_restricted_post_content', $post_id ) ) {
				return $content;
			}

			$enclosure_url = get_post_meta( $p->ID, 'audio_file', true );

			ob_start();
			$episode_type = get_post_meta( $p->ID, 'episode_type', true );
			if ( 'video' === $episode_type ) {
				?>
		<div class="kameradi-container-video">
				<?php
				if ( preg_match( '|https://player.vimeo.com/external/([0-9]+)|', $enclosure_url, $matches ) ) {
					$vid = $matches[1];
					?>
			<iframe src="https://player.vimeo.com/video/<?php echo esc_attr( $vid ); ?>?title=0&byline=0&portrait=0" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
					<?php
				} else {
					/* ビデオのサムネイルが出るので、デフォルトのプレイヤーを使う */
					echo do_shortcode( '[video src="' . $enclosure_url . '" /]' );
				}
				?>
		</div>
				<?php
			} elseif ( 'audio' === $episode_type ) {
				?>
		<div class="plyr-container-audio">
			<audio class="plyr-player" controls preload="metadata">
				<source src="<?php echo esc_url( $enclosure_url ); ?>" />
			</audio>
		</div>
				<?php
			}

			$player = ob_get_contents();
			ob_end_clean();

			return $player . $content;
		}
	}
	return $content;
}
add_action( 'the_content', 'bpcast_player' );
add_action( 'the_excerpt', 'bpcast_player' );


function bpcast_disable_thumbnail( $has_thumbnail, $post, $thumbnail_id ) {
	if ( ! is_admin() && is_main_query() ) {
		if ( 'podcast' === get_post_type( $post ) ) {
			return false;
		}
	}

	return $has_thumbnail;
}
add_filter( 'has_post_thumbnail', 'bpcast_disable_thumbnail', 10, 3 );


function sv_wc_memberships_add_post_lock_icon( $title, $post_id ) {

	if ( is_admin() ) {
		return $title;
	}

	// show the lock icon if the post is restricted, or access is delayed
	if ( ! current_user_can( 'wc_memberships_view_delayed_post_content', $post_id )
		|| ! current_user_can( 'wc_memberships_view_restricted_post_content', $post_id ) ) {

		$title = "<i class='fa fa-lock' aria-hidden='true'></i> {$title}";
	}

	return $title;
}
add_filter( 'the_title', 'sv_wc_memberships_add_post_lock_icon', 10, 2 );


function bplib_login_logout_css(){

	if ( is_user_logged_in() ) {
		?>
	<style id="bplib_login_logout">
		.logged_in_hide { display: none; }
	</style>
		<?php
	} else {
		?>
	<style id="bplib_login_logout">
		.logged_out_hide { display: none; }
	</style>
		<?php
	}
}
add_action( 'wp_head', 'bplib_login_logout_css' );


function jetpackme_allow_my_post_types( $allowed_post_types ) {
	$allowed_post_types[] = 'podcast';
	$allowed_post_types[] = 'page';
	
    return $allowed_post_types;
}
add_filter( 'rest_api_allowed_post_types', 'jetpackme_allow_my_post_types' );