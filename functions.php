<?php
/**
 * BusinessPress といてらライブラリ Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package businesspress-lib
 */

add_action( 'wp_enqueue_scripts', 'businesspress_parent_theme_enqueue_styles' );

/**
 * Enqueue scripts and styles.
 */
function businesspress_parent_theme_enqueue_styles() {
	wp_enqueue_style( 'businesspress-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'businesspress-lib-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'businesspress-style' )
	);

}

//enqueues our external font awesome stylesheet
function enqueue_our_required_stylesheets(){
	wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/5.4.0/css/font-awesome.min.css'); 
}
add_action('wp_enqueue_scripts','enqueue_our_required_stylesheets');


// required plugin checker
require_once 'includes/tgmpa.php';

// updater
require_once 'includes/plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/toiee-lab/bp-child-for-toieelib',
	__FILE__,
	'businesspress-lib'
);

// add-on for membership site
if( function_exists( 'ssp_beta_check' ) ) {

	require_once 'includes/membersite.php';

	require_once 'includes/ssp-extension.php';

	require_once 'includes/frontend.php';
}


// shortcode
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
			$terms = get_terms( 'series' );

			ob_start();
			?>
			<div class="loop-wrapper loop-wrapper-2column">
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

function bplib_category() {
	$post = get_post();

	echo '<div class="cat-links">';
	echo get_the_term_list( $post->ID, 'series', '<span class="category-sep">', '/', '</span>' );
//	the_category( '<span class="category-sep">/</span>' );
	echo '</div><!-- .cat-links -->';
}