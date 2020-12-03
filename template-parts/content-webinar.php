<?php
/**
 * The template used for displaying single post.
 *
 * @package BusinessPress
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<div class="wp-block-bp-blocks-bp-subheader"><a href="<?php echo esc_url( get_post_type_archive_link( 'webinar' ) ); ?>">ウェビナー</a></div>
		<h1 class="entry-title"><?php the_title(); ?></h1>
		<?php businesspress_entry_meta(); ?>
		<?php if ( has_post_thumbnail() && ! get_theme_mod( 'businesspress_hide_featured_image_on_full_text' ) ) : ?>
		<div class="post-thumbnail"><?php the_post_thumbnail(); ?></div>
		<?php endif; ?>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content(); ?>
		<?php
		wp_link_pages(
			array(
				'before'   => '<div class="page-links">' . esc_html__( 'Pages:', 'businesspress' ),
				'after'    => '</div>',
				'pagelink' => '<span class="page-numbers">%</span>',
			)
		);
		?>
		<?php
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

		?>
		<div class="webinar-info">
			<h2>📣 ウェビナー情報</h2>
			<?php if ( $in_time ) : /* イベント期間内 */ ?>
				<?php if ( $available ) : /* 申し込み可能 */ ?>
					<?php if ( is_user_logged_in() ) :  /* ログイン状態 */ ?>
						<?php
						/* 参加者チェック */
						$cu = wp_get_current_user();

						list( $is_attendee, $ticket_id ) = has_ticket( $cu->ID, $tickets );

						if ( $is_attendee ) { /* 参加者の場合 */
							echo '<div class="attendee-message"><h3>🎉 申し込み済みです</h3>' . wp_kses_post( $fields['message'] ) . '</div>';
							echo '<div style="margin-bottom: 1.5em">';
							acf_form(
								array(
									'post_id'           => $ticket_id,
									'post_title'        => false,
									'post_content'      => false,
									'submit_value'      => 'キャンセルする',
									'html_after_fields' => '<input type="hidden" name="acf[delete_this_post]" value="1" />',
									'updated_message'   => '<p>処理完了！</p>',
									'fields'            => array( 'dummy' ),
								)
							);
							echo '</div>';
						} else { /* 参加者じゃない場合 */
							echo '<div style="margin-bottom: 1.5em">';
							acf_form(
								array(
									'post_id'           => 'new_post',
									'new_post'          => array(
										'post_type'   => 'webinar-ticket',
										'post_status' => 'publish',
										'post_title'  => $cu->user_lastname . ' ' . $cu->user_firstname . ' ' . $cu->user_email,
									),
									'post_title'        => false,
									'post_content'      => false,
									'submit_value'      => '参加する',
									'html_after_fields' => '<input type="hidden" name="acf[webinar]" value="' . get_the_ID() . '"/>',
									'updated_message'   => '<p>処理完了！</p>',
									'fields'            => array( 'dummy' ),
								)
							);
							echo '</div>';
						}
						?>
					<?php else : /* ログアウト状態 */ ?>
						<p>参加するには、<a href="#bp-login-form">会員ログイン</a>が必要です。</p>
					<?php endif; ?>
				<?php else : /* 申し込みできない状態 */ ?>
					<?php if ( ! $open ) : ?>
				<p>⛔️ 募集開始されていません。</p>
					<?php elseif ( ! $vacant ) : ?>
				<p>🈵 満席です。</p>
					<?php else : ?>
				<p>🙇‍♂️ ご参加いただけません</p>
					<?php endif; ?>
				<?php endif; ?>
			<?php else : /* イベント期間外 */ ?>
				<p>🙇‍♂️ イベントは、終了しました。</p>
			<?php endif; ?>
			<dl>
				<dt>📅 開催日時</dt>
				<dd><?php echo esc_html( gmdate( 'Y年 n月 d日 H:i', $start_time ) ); ?> - <?php echo esc_html( gmdate( 'H:i', $end_time ) ); ?> (日本時間)<br>
				<small><strong>締め切り <?php echo esc_html( $close_msg ); ?> (<?php echo esc_html( gmdate( 'Y年m月d日 H:i', $close_time ) ); ?>)</strong></small></dd>
			</dl>
			<dl>
				<dt>🔖 募集人数（申し込み数 / 募集人数）</dt>
				<dd><?php echo esc_html( $ticket_num ); ?> / <?php echo esc_html( $limit ); ?>人</dd>
			</dl>
		</div>

		<?php if ( current_user_can( 'edit_posts' ) ) : ?>
			<div style="margin-top:2em;margin-bottom:2em;padding:0 1em;border:2px dashed #ccc">
				<h2 style="margin-top:1em;margin-bottom:0.5em;">管理者用 : 参加者一覧</h2>
				<?php
				if ( count( $tickets ) ) {
					echo "<ul>\n";
					foreach ( $tickets as $ticket ) {
						$tu         = get_user_by( 'ID', $ticket->post_author );
						$list_title = $ticket->ID . ' : ' . $tu->user_lastname . ' ' . $tu->user_firstname . ' ' . $tu->user_email;
						echo '<li><a href="' . get_admin_url() . 'post.php?post=' . $ticket->ID . '&action=edit">' . $list_title . '</a></li>';
					}
					echo "</ul>\n";
				} else {
					echo '<p>参加者なし</p>';
				}
				?>
			</div>
		<?php endif; ?>
	</div><!-- .entry-content -->

	<?php if ( get_the_tags() ) : ?>
	<div class="tags-links">
		<?php the_tags( '', esc_html__( ', ', 'businesspress' ) ); ?>
	</div>
	<?php endif; /* End if $the_tags */ ?>

</article><!-- #post-## -->

<?php if ( ! get_theme_mod( 'businesspress_hide_post_nav' ) ) : ?>
	<?php businesspress_post_nav(); ?>
<?php endif; ?>
<p style="text-align:center"><a href="<?php echo esc_url( get_post_type_archive_link( 'webinar' ) ); ?>">ウェビナー一覧へ</a></p>

<?php if ( class_exists( 'Jetpack_RelatedPosts' ) ) : ?>
	<?php echo do_shortcode( '[jetpack-related-posts]' ); ?>
<?php endif; ?>
