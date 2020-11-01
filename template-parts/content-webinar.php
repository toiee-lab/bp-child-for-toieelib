<?php
/**
 * The template used for displaying single post.
 *
 * @package BusinessPress
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<div class="wp-block-bp-blocks-bp-subheader"><a href="<?php echo get_post_type_archive_link( 'webinar' ); ?>">ウェビナー</a></div>
		<h1 class="entry-title"><?php the_title(); ?></h1>
		<?php businesspress_entry_meta(); ?>
		<?php if ( has_post_thumbnail() && ! get_theme_mod( 'businesspress_hide_featured_image_on_full_text' ) ): ?>
		<div class="post-thumbnail"><?php the_post_thumbnail(); ?></div>
		<?php endif; ?>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content(); ?>
		<?php wp_link_pages( array(	'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'businesspress' ), 'after'  => '</div>', 'pagelink' => '<span class="page-numbers">%</span>',  ) ); ?>
		<?php
		$fields     = get_fields();
		$open       = $fields['open'];
		$in_time    = strtotime( $fields['time_start'] ) > time();
		$tickets     = get_posts(
			array(
				'post_type' => 'webinar-ticket',
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
		$close_msg  = '直前までOK';
		switch ( $fields['time_close'] ) {
			case '10min':
				$close_time -= 60 * 10;
				$close_msg   = '10分前まで';
				break;
			case '30min':
				$close_time -= 60 * 30;
				$close_msg   = '30分前まで';
				break;
			case '1d':
				$close_time  = strtotime( date( 'Y-m-d 23:59:59', $close_time ) );
				$close_time -= 24 * 60 * 60;
				$close_msg   = '前日まで';
				break;
			case '3d':
				$close_time  = strtotime( date( 'Y-m-d 23:59:59', $close_time ) );
				$close_time -= 3 * 24 * 60 * 60;
				$close_msg   = '3日前まで';
				break;
		}
		$can_reserve = $close_time > time();

		// vacant
		if ( $ticket_num < $fields['limit'] ) {
			$vacant = true;
		} else {
			$vacant = false;
		}

		$available = $open && $in_time && $vacant && $can_reserve;

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

		// 開いていない理由
		// (1)期限切れ、(2)募集していない、(3)満席
		//var_dump( $fields );

		?>
		<div class="webinar-info">
			<h2>📣 ウェビナー情報</h2>
			<?php if ( $available && ! $is_attendee ) : ?>
				<?php
				if ( is_user_logged_in() ) {
					$cu = wp_get_current_user();
					echo '<div style="margin-bottom: 1.5em">';
					acf_form(array(
						'post_id'       => 'new_post',
						'new_post'      => array(
							'post_type'   => 'webinar-ticket',
							'post_status' => 'publish',
							'post_title'    => $cu->user_lastname . ' ' . $cu->user_firstname . ' ' . $cu->user_email,

						),
						'post_title'    => false,
						'post_content'  => false,
						'submit_value'  => '参加する',
						'html_after_fields'  => '<input type="hidden" name="acf[webinar]" value="' . get_the_ID() . '"/>',
						'updated_message' => '<p>処理完了！</p>',
						'fields'             => array( 'dummy' ),
					));
					echo '</div>';
				} else {
					echo '<p>参加するには、会員ログインが必要です。</p>';
				}
				?>
			<?php else : ?>
				<?php
				if ( ! $open ) {
					echo '<p>現在、募集しておりません。</p>';
				}
				if ( ! $in_time ) {
					echo '<p>イベントは終了しました。</p>';
				}
				if ( ! $vacant ) {
					echo '<p>満席です。</p>';
				}
				?>
			<?php endif; ?>
			<?php if ( $is_attendee ) {
				if ( $in_time ) {
					echo '<div class="attendee-message"><h3>🎉 申し込み済みです</h3>' . $fields['message'] . '</div>';
					$cu = wp_get_current_user();
					echo '<div style="margin-bottom: 1.5em">';
					acf_form(array(
						'post_id'       => $ticket_id,
						'post_title'    => false,
						'post_content'  => false,
						'submit_value'  => 'キャンセルする',
						'html_after_fields'  => '<input type="hidden" name="acf[delete_this_post]" value="1" />',
						'updated_message' => '<p>処理完了！</p>',
						'fields'             => array( 'dummy' ),
					));
					echo '</div>';

				} else {
					echo '<p>ご参加、ありがとうございました！</p>';
				}
			}
			?>
			<dl>
				<dt>📅 開催日時</dt>
				<dd><?php echo date( 'Y年 n月 d日 H:i', strtotime( $fields['time_start'] ) ); ?> - <?php echo date( 'H:i', strtotime( $fields['time_end'] ) ); ?> (日本時間)<br>
				<small><strong>締め切り <?php echo $close_msg; ?> (<?php echo date( 'Y年m月d日 H:i', $close_time ); ?>)</strong></small></dd>
			</dl>
			<dl>
				<dt>🔖 募集人数（申し込み数 / 募集人数）</dt>
				<dd><?php echo $ticket_num; ?> / <?php echo $fields['limit']; ?>人</dd>
			</dl>
		</div>

		<?php if ( current_user_can( 'edit_posts' ) ) : ?>
			<div style="margin-top:2em;margin-bottom:2em;padding:0 1em;border:2px dashed #ccc">
				<h2 style="margin-top:1em;margin-bottom:0.5em;">管理者用 : 参加者一覧</h2>
				<?php
				if ( count( $tickets ) ) {
					echo "<ul>\n";
					foreach ( $tickets as $ticket ) {
						$tu = get_user_by( 'ID', $ticket->post_author );
						$list_title = $ticket->ID . ' : ' . $tu->user_lastname . ' ' . $tu->user_firstname . ' ' . $tu->user_email;
						echo '<li><a href="' . get_admin_url() .  'post.php?post=' . $ticket->ID . '&action=edit">' . $list_title . '</a></li>';
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
	<?php endif; // End if $the_tags ?>

</article><!-- #post-## -->

<?php if ( ! get_theme_mod( 'businesspress_hide_post_nav' ) ) : ?>
	<?php businesspress_post_nav(); ?>
<?php endif; ?>
<p style="text-align:center"><a href="<?php echo get_post_type_archive_link( 'webinar' ); ?>">ウェビナー一覧へ</a></p>

<?php if ( class_exists( 'Jetpack_RelatedPosts' ) ) : ?>
	<?php echo do_shortcode( '[jetpack-related-posts]' ); ?>
<?php endif; ?>