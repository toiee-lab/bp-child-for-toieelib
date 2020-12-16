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
	wp_enqueue_style( 'businesspress-style', get_template_directory_uri() . '/style.css', array(), '2.0.3' );
	wp_enqueue_style( 'businesspress-lib-style', get_stylesheet_directory_uri() . '/style.css', array( 'businesspress-style' ), '2.1.2' );
	wp_enqueue_script( 'businesspress-lib-functions', get_stylesheet_directory_uri() . '/assets/functions.js', array( 'jquery' ), '20201204', true );
}

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_dequeue_script( 'fitvids' );
		wp_dequeue_script( 'businesspress-functions' );
	},
	20
);

/**
 * Font awesome を読み込むための設定
 *
 * @return void
 */
function enqueue_our_required_stylesheets() {
	wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/5.4.0/css/font-awesome.min.css' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_our_required_stylesheets' );


/* required plugin checker */
require_once 'includes/tgmpa.php';

/* updater */
require_once 'includes/plugin-update-checker/plugin-update-checker.php';
$my_update_checker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/toiee-lab/bp-child-for-toieelib',
	__FILE__,
	'businesspress-lib'
);

/* add-on for membership site */
if ( function_exists( 'ssp_beta_check' ) ) {

	require_once 'includes/membersite.php';

	require_once 'includes/ssp-extension.php';

	require_once 'includes/frontend.php';
}

require_once 'includes/woocommerce-settings.php';

require_once 'includes/shortcode.php';

require_once 'includes/webinar.php';

require_once 'includes/options-page.php';

require_once 'includes/helpscout-beacon.php';

require_once 'includes/mailerlite.php';

require_once 'includes/trial.php';

require_once 'includes/block-slider.php';

/**
 * シリーズ名を表示するためのテンプレートタグ
 *
 * @return void
 */
function bplib_category() {
	$post = get_post();

	echo '<div class="cat-links">';
	echo get_the_term_list( $post->ID, 'series', '<span class="category-sep">', '/', '</span>' );
	/* the_category( '<span class="category-sep">/</span>' ); */
	echo '</div><!-- .cat-links -->';
}
