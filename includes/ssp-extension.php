<?php
/**
 * Seriously Simple Podcast を拡張する
 *
 * @package category
 */

/* Slug が日本語にされてしまうことを抑制する */
add_filter(
	'ssp_archive_slug',
	function () {
		return 'podcast';
	}
);

/**
 * Podcast feed の場合、表示数を大幅に増やす（300）
 */
add_filter(
	'ssp_feed_number_of_posts',
	function ( $num ) {
		return 300;
	}
);

/**
 * アクセス制限をするために、別のfeedテンプレートを読み込む
 */
// add_filter(
// 	'ssp_feed_template_file',
// 	function ( $template_file ) {
// 		$template_file = get_stylesheet_directory() . '/feed-podcast.php';
// 		return $template_file;
// 	},
// 	1,
// 	1
// );



/**
 * フィード詳細画面から、シリーズの画面へ移動するためのリンクを追加
 *
 * @param [type] $settings
 * @return void
 */
function bpcast_ssp_setting_fields( $settings ) {

	if ( ! array_key_exists( 'feed-series', $_GET ) ) {
		return $settings;
	}

	$series_slug = $_GET['feed-series'];
	$term        = get_term_by( 'slug', $series_slug, 'series' );

	if ( $term == false ) {
		$series_url      = '#';
		$series_edit_url = '#';
	} else {
		$series_url      = get_term_link( $term, 'series' );
		$series_edit_url = get_edit_term_link( $term, 'series' );
	}

	array_unshift(
		$settings['feed-details']['fields'],
		array(
			'id'          => 'podcast_info',
			'label'       => __( 'リンク集', 'seriously-simple-podcasting' ),
			'description' => '<a href="' . $series_url . '">シリーズページ</a> : <a href="' . $series_edit_url . '">シリーズ編集ページ</a>',
			'type'        => 'none',
			'default'     => '',
			'placeholder' => __( '100,200,...', 'seriously-simple-podcasting' ),
			'callback'    => '',
			'class'       => 'regular-text',
		)
	);

	return $settings;
}
add_filter( 'ssp_settings_fields', 'bpcast_ssp_setting_fields', 10, 1 );


/**
 * シリーズの編集管理画面を便利にする
 *
 * @param [type] $term
 * @return void
 */
function bpcast_add_detail_url( $term ) {
	$url        = get_admin_url() . 'edit.php?post_type=podcast&page=podcast_settings&tab=feed-details&feed-series=' . $term->slug;
	$enc_url    = htmlentities( $url );
	$series_url = get_term_link( $term, 'series' );
	?>

	<tr class="form-field term-meta-text-wrap">
		<th scope="row"><label for="term-meta-text"><?php _e( '便利なリンク集', 'text_domain' ); ?></label></th>
		<td>
			<a href="<?php echo $enc_url; ?>">フィード設定</a> : <a href="<?php echo $series_url; ?>">視聴ページ</a>
		</td>
	</tr>
	<tr class="form-field term-meta-text-wrap">
		<th scope="row"><hr></th>
		<td><hr></td>
	</tr>
	<?php
}
add_action( 'series_edit_form_fields', 'bpcast_add_detail_url' );


function bpcast_change_order( $query ) {

	if ( is_admin() || ! $query->is_main_query() ) {
		return null;
	}

	if ( is_tax( 'series' ) ) {
		$tax = $query->queried_object;
		if ( null === $tax ) {
			return null;
		}

		$series_id   = $tax->term_id;
		$itunes_type = get_option( 'ss_podcasting_consume_order' . ( $series_id > 0 ? '_' . $series_id : null ) );

		if ( 'serial' === $itunes_type ) {
			$query->set( 'order', 'ASC' );
		}
		return;
	}
}
add_action( 'pre_get_posts', 'bpcast_change_order' );
