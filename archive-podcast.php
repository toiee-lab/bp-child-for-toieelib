<?php
/**
 * The template for displaying archive pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package BusinessPress
 */

get_header(); ?>

<section id="primary" class="content-area">
	<main id="main" class="site-main">

	<?php if ( have_posts() ) : ?>

		<header class="page-header">
			<h1>ポッドキャストでーす</h1>
		</header><!-- .page-header -->

		<div class="loop-wrapper">
		<?php /* Start the Loop */ ?>
		<?php while ( have_posts() ) : the_post();
			get_template_part( 'template-parts/content', get_theme_mod( 'businesspress_content_archive' ) );
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

<?php if ( '3-column' !== get_theme_mod( 'businesspress_content_archive' ) ): ?>
	<?php get_sidebar(); ?>
<?php endif; ?>
<?php get_footer(); ?>
