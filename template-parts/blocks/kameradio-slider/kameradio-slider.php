<?php
/**
 * カメラジ・スライダーのレンダリング用のファイルです
 *
 * @package category
 */

define( 'KAMERADIO_SLIDE_EXCERPT_LENGTH', 85 );

$class_name = 'featured-post';
if ( ! empty( $block['className'] ) ) {
	$class_name .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
	$class_name .= ' align' . $block['align'];
} else {
	$class_name .= ' alignfull';
}

$els = array();
if ( have_rows( 'slider_elements' ) ) : 
	while ( have_rows( 'slider_elements' ) ) :
		the_row();

		/* 最新Webinar の取り出し */
		if ( 'latest_webinars' === get_row_layout() ) {
			$num      = get_sub_field( 'num' );
			$btn_text = get_sub_field( 'button_text' );

			$ps = get_posts(
				array(
					'post_type'      => 'webinar',
					'posts_per_page' => $num,
					'orderby'        => 'time_start',
					'meta_key'       => 'time_start',
				)
			);

			foreach ( $ps as $p ) {
				$url  = get_permalink( $p );
				$lead = mb_substr( get_the_excerpt( $p ), 0, KAMERADIO_SLIDE_EXCERPT_LENGTH );
				$lead = '<p>' . $lead . '</p>' . '<div class="home-header-button"><a href="' . $url . '" class="home-header-button-sub">' . $btn_text . '</a></div>';

				$els[] = array(
					'type'  => 'webinar',
					'id'    => $p->ID,
					'title' => $p->post_title,
					'date'  => get_the_date( '', $p->ID ) . 'から',
					'bg'    => get_the_post_thumbnail_url( $p ),
					'url'   => $url,
					'lead'  => $lead,
				);
			}
		}

		if ( 'series' === get_row_layout() ) {
			$t   = get_sub_field( 'series' );
			$sid = $t->term_id;
			$url = get_term_link( $t );

			$lead = get_sub_field( 'lead' );
			if ( '' === $lead ) {
				$lead = mb_substr( $t->description, 0, KAMERADIO_SLIDE_EXCERPT_LENGTH );
			}

			$btn_text = get_sub_field( 'button_text' );
			$lead     = '<p>' . $lead . '</p>'  . '<div class="home-header-button"><a href="' . $url . '" class="home-header-button-sub">' . $btn_text . '</a></div>';

			if ( null !== $t ) {
				$series_image = get_option( 'ss_podcasting_data_image_' . $sid, 'no-image' );
				$els[]        = array(
					'type'  => 'series',
					'id'    => $sid,
					'title' => $t->name,
					'date'  => '',
					'bg'    => $series_image,
					'url'   => $url,
					'lead'  => $lead,
				);
			}
		}

		if ( 'post' === get_row_layout() ) {
			$p   = get_sub_field( 'post_obj' );
			$url = get_permalink( $p );

			$p_title = get_sub_field( 'title' );
			if ( '' === $p_title ) {
				$p_title = $p->post_title;
			}

			$lead = get_sub_field( 'lead' );
			if ( '' === $lead ) {
				$lead = mb_substr( get_the_excerpt( $p ), 0, KAMERADIO_SLIDE_EXCERPT_LENGTH );
			}

			$btn_text = get_sub_field( 'button_text' );
			$lead     = '<p>' . $lead . '</p>'  . '<div class="home-header-button"><a href="' . $url . '" class="home-header-button-sub">' . $btn_text . '</a></div>';

			if ( null !== $p ) {
				$els[] = array(
					'type'  => $p->post_type,
					'id'    => $p->ID,
					'title' => $p_title,
					'date'  => get_the_date( '', $p->ID ),
					'bg'    => get_the_post_thumbnail_url( $p ),
					'url'   => $url,
					'lead'  => $lead,
				);
			}
		}
	endwhile;
endif;

if ( is_admin() ) {
	?>
<div style="height: 400px;overflow: auto;background-color:#eee;border:1px solid #ddd; padding:0 1rem;">
	<h2 style="margin: 0.5em 0;">カメラジ・スライダー</h2>
	<p style="color:red">ここでは、プレビューを確認することはできません。フロントエンドのプレビューで確かめてください。</p>
	<p>表示予定のデータは以下の通りです。</p>
	<ul>
		<?php foreach ( $els as $el ) : ?>
		<li><a href="<?php echo esc_url( $el['url'] ); ?>" target="_blank"><?php echo esc_html( $el['type'] ); ?> : <?php echo esc_html( $el['title'] ); ?></a></li>
		<?php endforeach; ?>
	</ul>
</div>
	<?php
} else {
	?>
<div class="<?php echo esc_attr( $class_name ); ?>">
	<?php foreach ( $els as $el ) : ?>
	<div class="slick-item">
		<div class="featured-entry" style="background-image:url('<?php echo esc_url( $el['bg'] ); ?>');">
			<div class="featured-entry-overlay">
				<div class="featured-entry-content">
					<div class="featured-entry-category"><?php echo esc_html( $el['type'] ); ?></div>
					<h2 class="featured-entry-title"><a href="<?php echo esc_url( $el['url'] ); ?>" rel="bookmark"><?php echo esc_html( $el['title'] ); ?></a></h2>
					<div class="featured-entry-date featured-entry-date-slider"><a href="<?php echo esc_url( $el['url'] ); ?>" rel="bookmark"><?php echo esc_html( $el['date'] ); ?></a></div>
					<div class="featured-lead"><?php echo wp_kses_post( $el['lead'] ); ?></div>
				</div><!-- .featured-entry-content -->
			</div><!-- .featured-entry-overlay -->
		</div><!-- .featured-entry -->
	</div><!-- .slick-item -->	
	<?php endforeach; ?>
</div>
	<?php
}
