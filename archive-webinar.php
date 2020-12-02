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
			<h1 class="page-title-webinar">ウェビナー一覧</h1>
		</header><!-- .page-header -->

		<div class="loop-wrapper"><table>
			<tr>
				<th>📅 開催日時</th>
				<th>📝 タイトル</th>
				<th>🎟 募集人数</th>
			</tr>
		<?php /* Start the Loop */ ?>
		<?php while ( have_posts() ) : the_post();

			$fields     = get_fields();
			$open       = $fields['open'];
			$in_time    = strtotime( $fields['time_start'] ) > time();
			$tickets     = get_posts(
				array(
					'post_type' => 'webinar-ticket',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => 'webinar',
							'value' => get_the_ID(),
						),
					),
				)
			);
			$ticket_num = count( $tickets );

			// reserve close time
			$close_time = strtotime( $fields['time_start'] );
			$close_msg  = '申し込みは、直前までOK';
			switch ( $fields['time_close'] ) {
				case '0':
					$close_time = strtotime( $fields['time_end'] );
					$close_msg  = '途中参加OK';
					break;
				case '10min':
					$close_time -= 60 * 10;
					$close_msg   = '申し込みは、10分前まで';
					break;
				case '30min':
					$close_time -= 60 * 30;
					$close_msg   = '申し込みは、30分前まで';
					break;
				case '1d':
					$close_time  = strtotime( date( 'Y-m-d 23:59:59', $close_time ) );
					$close_time -= 24 * 60 * 60;
					$close_msg   = '申し込みは、前日まで';
					break;
				case '3d':
					$close_time  = strtotime( date( 'Y-m-d 23:59:59', $close_time ) );
					$close_time -= 3 * 24 * 60 * 60;
					$close_msg   = '申し込みは、3日前まで';
					break;
			}
			$can_reserve = $close_time > time();

			// vacant
			$limit = $fields['limit'];
			if ( $ticket_num < $limit ) {
				$vacant = true;
			} else {
				$vacant = false;
			}

			$available       = $open && $in_time && $vacant && $can_reserve;
			$available_class = $available ? 'webinar-available' : 'webinar-disable';

			$current_user_id = ( wp_get_current_user() )->ID;
			$is_attendee     = false;
			$ticket_id       = false;
			foreach ( $tickets as $ticket ) {
				if ( $ticket->post_author == $current_user_id ) {
					$is_attendee = true;
					$ticket_id   = $ticket->ID;
					break;
				}
			}

			$status = '';
			if ( $can_reserve ) {
				if( $open ) {
					if ( $is_attendee ) {
						$status = '申し込み済';
					} else {
						if ( $vacant ) {
							$status = '募集中';
						} else {
							$status = '満席';
						}
					}
				} else {
					$status = '閉鎖中';
				}
			} else {
				$status = '終了しました';
			}
		?>
			<tr class="<?php echo $available_class; ?>">
				<td><?php echo date_i18n( 'n月 d日(D)', strtotime( $fields['time_start'] ) ); ?><br>
				<?php echo date( 'H:i', strtotime( $fields['time_start'] ) ); ?> - <?php echo date( 'H:i', strtotime( $fields['time_end'] ) ); ?><br>
				<small><?php echo $close_msg; ?></small></td>
				<td><a href="<?php the_permalink(); ?>"><?php the_title( '<strong>', '</strong>' ); ?></a><br>
				</td>
				<td>
					<?php echo $ticket_num; ?> / <?php echo $limit; ?>人<br>
					<?php echo $status; ?>
				</td>
			</tr>
		<?php endwhile; ?>
		</table></div><!-- .loop-wrapper -->

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
