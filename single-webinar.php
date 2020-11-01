<?php
/**
 * The template for displaying all single posts.
 *
 * @package BusinessPress
 */
if ( is_user_logged_in() ) {
	acf_form_head();
}
get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">

	<?php while ( have_posts() ) : the_post(); ?>
		<?php get_template_part( 'template-parts/content', 'webinar' ); ?>
		<?php
			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;
		?>
	<?php endwhile; // End of the loop. ?>

	</main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>