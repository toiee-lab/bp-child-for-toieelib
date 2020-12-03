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
		<?php while ( have_posts() ) : ?>
			<?php
			the_post();

			$fields = get_fields();

			/* 空席チェック */
			$tickets    = get_tickets( get_the_ID() );
			$ticket_num = count( $tickets );
			$limit      = $fields['limit'];
			$vacant     = $ticket_num < $limit;

			/* 締め切りのための計算 */
			$now_time   = time();
			$start_time = strtotime( $fields['time_start'] );
			$end_time   = strtotime( $fields['time_end'] );

			list( $can_reserve, $close_time, $close_msg ) = can_reserve( $now_time, $fields['time_close'], $start_time, $end_time );

			/* 募集されていて、開催期間中で、空席があって、予約可能 */
			$open      = $fields['open'];
			$in_time   = $now_time < $end_time;
			$available = $open && $in_time && $vacant && $can_reserve;

			$ticket_status = '';
			if ( $can_reserve ) {
				if ( $open ) {
					if ( $is_attendee ) {
						$ticket_status = '申し込み済';
					} else {
						if ( $vacant ) {
							$ticket_status = '募集中';
						} else {
							$ticket_status = '満席';
						}
					}
				} else {
					$ticket_status = '閉鎖中';
				}
			} else {
				$ticket_status = '終了しました';
			}

			$available_class = $available ? '' : 'webinar-disable';
			?>
			<tr class="<?php echo esc_attr( $available_class ); ?>">
				<td>
					<?php echo esc_html( date_i18n( 'n月 d日(D)', $start_time ) ); ?><br>
					<?php echo esc_html( gmdate( 'H:i', $start_time ) . ' - ' . gmdate( 'H:i', $end_time ) ); ?><br>
					<small><?php echo esc_html( $close_msg ); ?></small></td>
				<td><a href="<?php the_permalink(); ?>"><?php the_title( '<strong>', '</strong>' ); ?></a><br>
				</td>
				<td>
					<?php echo esc_html( $ticket_num ); ?> / <?php echo esc_html( $limit ); ?>人<br>
					<?php echo esc_html( $ticket_status ); ?>
				</td>
			</tr>
		<?php endwhile; ?>
		</table></div><!-- .loop-wrapper -->

		<?php
		the_posts_pagination(
			array(
				'prev_text' => esc_html__( '&laquo; Previous', 'businesspress' ),
				'next_text' => esc_html__( 'Next &raquo;', 'businesspress' ),
			)
		);
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
