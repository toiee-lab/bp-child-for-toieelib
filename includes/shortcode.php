<?php

function bplib_shortcode( $atts ) {
	if ( isset( $atts['q'] ) ) {
		$q = $atts['q'];
	} else {
		$q = 'series';
	}

	$msg       = '';
	$tax_query = array();
	switch ( $q ) {
		case 'welcome' :
		case 'featured' :
			$tax_query = array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => $q,
				),
			);
		case 'latest' :
			$args = array(
				'post_type' => 'podcast',
				'tax_query' => $tax_query,
			);
			$the_query = new WP_Query( $args );

			if ( $the_query->have_posts() ) {
				remove_filter( 'has_post_thumbnail', 'bpcast_disable_thumbnail', 10 );
				ob_start();
				?>
				<div class="loop-wrapper loop-wrapper-3column">
				<?php
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					?>
					<div class="post-grid">
						<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
							<header class="entry-header">
								<?php if ( has_post_thumbnail() ) : ?>
								<div class="post-thumbnail">
									<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'businesspress-post-thumbnail-medium' ); ?></a>
								</div><!-- .post-thumbnail -->
								<?php endif; ?>
								<?php bplib_category(); ?>
								<h2 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
								<?php businesspress_entry_meta(); ?>
							</header><!-- .entry-header -->
							<div class="entry-summary">
								<p><?php echo businesspress_shorten_text( get_the_excerpt(), 160 ); ?></p>
							</div><!-- .entry-summary -->
						</article><!-- #post-## -->
					</div><!-- .post-grid -->					
					<?php
				}
				?>
				</div>
				<?php
				wp_reset_postdata();
				add_filter( 'has_post_thumbnail', 'bpcast_disable_thumbnail', 10, 3 );

				$msg = ob_get_contents();
				ob_end_clean();
			} else {
				$msg = '<p>投稿はありません</p>';
			}
			break;

		default:
			$terms        = get_terms( 'series' );

			$series_image    = get_option( 'ss_podcasting_data_image', 'no-image' );
			$series_url      = get_home_url() . '/podcast/';
			$arce_title      = get_option( 'ss_podcasting_data_title', 'Podcast' );
			$arc_description = get_option( 'ss_podcasting_data_subtitle', '' );

			ob_start();
			?>
			<div class="loop-wrapper loop-wrapper-2column">
				<div class="post-grid">
					<div class="page-header-grid">
						<div class="series-icon">
							<img src="<?php echo $series_image; ?>">
						</div>
						<div class="seriees-title">
							<h2 class="series-2column-title"><a href="<?php echo $series_url; ?>"><?php echo $arce_title; ?></a></h2>
							<div class="taxonomy-description-series"><?php echo $arc_description; ?></div>
						</div>
					</div>
				</div>

				<?php foreach ( $terms as $term ) : ?>
					<?php
						$series_id    = $term->term_id;
						$series_image = get_option( 'ss_podcasting_data_image_' . $series_id, 'no-image' );
					?>
				<div class="post-grid">
					<div class="page-header-grid">
						<div class="series-icon">
							<img src="<?php echo $series_image; ?>">
						</div>
						<div class="seriees-title">
							<h2 class="series-2column-title"><a href="<?php echo get_term_link($term); ?>"><?php echo $term->name; ?></a></h2>
							<div class="taxonomy-description-series"><?php echo $term->description; ?></div>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php

			$msg = ob_get_contents();
			ob_end_clean();
	}

	return $msg;
}
add_shortcode( 'bp-lib', 'bplib_shortcode' );


function businesspress_entry_meta() {
	// Hide for pages on Search.
	if ( 'post' != get_post_type() && 'podcast' != get_post_type() ) {
		return;
	}
	?>
	<div class="entry-meta">
		<span class="posted-on">
		<?php printf( '<a href="%1$s" rel="bookmark"><time class="entry-date published updated" datetime="%2$s">%3$s</time></a>',
				esc_url( get_permalink() ),
				esc_attr( get_the_date( 'c' ) ),
				esc_html( get_the_date() )
			); ?>
		</span>
		<span class="byline"><?php esc_html_e( 'by', 'businesspress' ); ?>
			<span class="author vcard">
				<a class="url fn n" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" title="<?php printf( esc_html__( 'View all posts by %s', 'businesspress' ), get_the_author() );?>"><?php echo get_the_author();?></a>
			</span>
		</span>
		<?php if ( ! post_password_required() && comments_open() ) : ?>
		<span class="comments-link"><span class="comments-sep"> / </span>
			<?php comments_popup_link( esc_html__( '0 Comment', 'businesspress' ), esc_html__( '1 Comment', 'businesspress' ), esc_html__( '% Comments', 'businesspress' ) ); ?>
		</span>
		<?php endif; ?>
	</div><!-- .entry-meta -->
	<?php
}


/**
 * Webinar の スライダーを表示するためのショートコード
 * w5 = webinarを最新から5つ取得する
 * s1 = series id が1を取得
 * p1 = post id が 1 を取得
 * 
 * 例) [bp-lib-webinar-slider content="w5,s1,s2,p1,p2,p3"]
 */
add_shortcode( 'bplib-webinar-slider', 'bplib_webinar_slider' );
function bplib_webinar_slider( $atts ) {
	if ( ! isset( $atts['content'] ) ) {
		return '';
	}

	$arr = explode( ',', $atts['content'] );
	if ( 0 === count( $arr ) ) {
		return '';
	}

	$arr = array_map( 'trim' , $arr );
	$els = array();

	foreach( $arr as $v ) {
		if( preg_match( '/^([a-z])(\d+)$/', $v, $matches ) ) {
			$type = $matches[1];
			$id   = $matches[2];

			switch ( $type ) {
				case 'w': //webinar
					$ps = get_posts(
						array(
							'post_type'      => 'webinar',
							'posts_per_page' => $id,
							'orderby'        => 'time_start',
							'meta_key'       => 'time_start',
						)
					);

					foreach( $ps as $p ) {
						$url = get_permalink( $p );
						$els[] = array(
							'type'  => 'webinar',
							'id'    => $p->ID,
							'title' => $p->post_title,
							'date'  => date( 'Y年m月d日 H:i', strtotime( get_field( 'time_start', $p->ID ) ) ),
							'bg'    => get_the_post_thumbnail_url( $p ),
							'url'   => $url,
							'lead'  => '<div class="home-header-button"><a href="' . $url . '" class="home-header-button-sub">参加する</a></div>',
						);
					}
					break;

				case 's': //series
					$t = get_term( $id, 'series' );
					if ( null != $t ) {
						$series_image = get_option( 'ss_podcasting_data_image_' . $id, 'no-image' );
						$els[] = array(
							'type'  => 'series',
							'id'    => $id,
							'title' => $t->name,
							'date'  => '',
							'bg'    => $series_image,
							'url'   => get_term_link( $t ),
							'lead'  => '',
						);
					}
				break;

				case 'p': //post
					$p = get_post( $id );
					if ( null != $p ) {
						$els[] = array(
							'type'  => $p->post_type,
							'id'    => $id,
							'title' => $p->post_title,
							'date'  => date( 'Y年m月d日 H:i', strtotime( $p->post_date ) ),
							'bg'    => get_the_post_thumbnail_url( $p ),
							'url'   => get_permalink( $p ),
							'lead'  => '',
						);
					}
				break;
			}
		}
	}

	ob_start();
?>
<div class="featured-post alignfull">
	<?php foreach( $els as $el ) : ?>
	<div class="slick-item">
		<div class="featured-entry" style="background-image:url('<?php echo esc_url( $el['bg'] ); ?>');">
			<div class="featured-entry-overlay">
				<div class="featured-entry-content">
					<div class="featured-entry-category"><?php echo esc_html( $el['type'] ); ?></div>
					<h2 class="featured-entry-title"><a href="<?php echo esc_url( $el['url'] ); ?>" rel="bookmark"><?php echo esc_html( $el['title'] ); ?></a></h2>
					<div class="featured-entry-date posted-on"><a href="<?php echo esc_url( $el['url'] ); ?>" rel="bookmark"><?php echo esc_html( $el['date'] ); ?></a></div>
					<div class="featured-lead"><?php echo $el['lead']; ?></div>
				</div><!-- .featured-entry-content -->
			</div><!-- .featured-entry-overlay -->
		</div><!-- .featured-entry -->
	</div><!-- .slick-item -->	
	<?php endforeach; ?>
</div>
<?php	
	$html = ob_get_contents();
	ob_end_clean();

	// bg_img, cat_name, title, date
	return $html;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script( 'slick', get_theme_file_uri( '/js/slick.js' ), array( 'jquery' ), '1.9.0', true );
		wp_enqueue_style( 'slick-style', get_theme_file_uri( '/css/slick.css' ), array(), '1.9.0' );
	}
);
