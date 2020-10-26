<?php
/**
 * The template for displaying archive pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package BusinessPress
 */

$can_edit = false;
$post_form = '';

if ( current_user_can( 'edit_posts' ) ) {
	acf_form_head();
	wp_deregister_style( 'wp-admin' );

	$can_edit = true;
}

$series       = get_queried_object();
$series_id    = $series->term_id;
$series_image = get_option( 'ss_podcasting_data_image_' . $series_id, 'no-image' );
$series_feed  = get_home_url() . '/feed/podcast/' . $series->slug . '/bpcast_token/' . bpcast_get_user_token() . '/';

$feed_podcast = preg_replace( '/^http.?:/', 'podcast:', $series_feed );
$feed_itunes  = preg_replace( '/^http.?:/', 'pcast:', $series_feed );
$feed_ovcast  = 'overcast://x-callback-url/add?url=' . $series_feed;
$feed_castro  = preg_replace( '/^http.?:/', 'podto:', $series_feed );

get_header();
?>

<section id="primary" class="content-area">
	<main id="main" class="site-main">

	<?php if ( have_posts() ) : ?>

		<header class="page-header">
			<div class="page-header-grid">
				<div class="series-icon">
					<img src="<?php echo $series_image; ?>">
				</div>
				<div class="seriees-title">
				<?php
					the_archive_title( '<h1 class="page-title-series">', '</h1>' );
					the_archive_description( '<div class="taxonomy-description-series">', '</div>' );
				?>
				</div>
			</div>
			<div class="subscribe">
			<?php if ( is_user_logged_in() ) : ?>
				<button onclick="location.href='<?php echo $feed_podcast; ?>'">Podcast</button>
				<button onclick="location.href='<?php echo $feed_itunes; ?>'">iTunes</button>
				<button onclick="location.href='<?php echo $feed_ovcast; ?>'">Overcast</button>
				<button onclick="location.href='<?php echo $feed_castro; ?>'">Castro</button>
				<br>
				<input type="text" value="<?php echo $series_feed; ?>" id="copy_feed" onclick="this.select();" readonly> <button onclick="copy_feed()">Copy feed</button>
				<script>
					function copy_feed() {
						/* Get the text field */
						var copyText = document.getElementById("copy_feed");

						/* Select the text field */
						copyText.select(); 
						copyText.setSelectionRange(0, 99999); /*For mobile devices*/

						/* Copy the text inside the text field */
						document.execCommand("copy");

						/* Alert the copied text */
						alert("コピーしました");
					}
				</script>
			<?php else : ?>
				<button onclick="location.href='#bp-login-form'">Podcast</button>
				<button onclick="location.href='#bp-login-form'">iTunes</button>
				<button onclick="location.href='#bp-login-form'">Overcast</button>
				<button onclick="location.href='#bp-login-form'">Castro</button>
				<br>
				<input type="text" value="<?php echo $series_feed; ?>" id="copy_feed" onclick="this.select();" readonly> <button onclick="copy_feed()">Copy feed</button>
				<script>
					function copy_feed() {
						/* Get the text field */
						location.href='#bp-login-form';
					}
				</script>
			<?php endif; ?>
			</div>
		</header><!-- .page-header -->
		<div class="loop-wrapper loop-wrapper-podcast">
			<?php if ( $can_edit ) : ?>
			<div class="series-buttons">
				<?php
				$setting = array(
					'post_id'            => 'new_post',
					'new_post'           => array(
						'post_type'   => 'podcast',
						'post_status' => 'draft',
						'tax_input'   => array( 'series' => $series_id ),
						'post_title'         => date( 'Y-m-d' ) . ' 新規エピソード',
					),
					'submit_value'       => 'エピソードを追加する',
					'return'             => admin_url( '/post.php?post=%post_id%&action=edit' ),
				);
				acf_form( $setting );
				?>
			</div>
			<?php endif; ?>
		<?php /* Start the Loop */ ?>
		<?php while ( have_posts() ) : the_post();
			get_template_part( 'template-parts/content', 'summary' );
		endwhile; ?>
		</div><!-- .loop-wrapper -->

		<?php
		the_posts_pagination( array(
			'prev_text' => esc_html__( '&laquo; Previous', 'businesspress' ),
			'next_text' => esc_html__( 'Next &raquo;', 'businesspress' ),
		) );
		?>

	<?php else : ?>

		<?php get_template_part( 'template-parts/content', 'none' ); ?>

	<?php endif; ?>

	</main><!-- #main -->
</section><!-- #primary -->
<?php get_footer(); ?>
