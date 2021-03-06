<?php
/**
 * ブロックの記述をします
 *
 * @package block
 */

add_action( 'acf/init', 'kameradio_slider_acf_init_block_types' );
/**
 * カメラジ・スライダーブロックを登録します
 *
 * @return void
 */
function kameradio_slider_acf_init_block_types() {
	if ( function_exists( 'acf_register_block_type' ) ) {

		// register a testimonial block.
		acf_register_block_type(
			array(
				'name'            => 'kameradio_slider',
				'title'           => __('カメラジスライダー'),
				'description'     => __('ウェビナー、シリーズ、エピソード、ブログ、ページなどへのスライダーを作ります'),
				'render_template' => 'template-parts/blocks/kameradio-slider/kameradio-slider.php',
				'category'        => 'formatting',
				'icon'            => 'admin-comments',
				'keywords'        => array('slider', 'kame', 'kameradi', 'kameradio'),
			)
		);
	}
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script( 'slick', get_theme_file_uri( '/js/slick.js' ), array( 'jquery' ), '1.9.0', true );
		wp_enqueue_style( 'slick-style', get_theme_file_uri( '/css/slick.css' ), array(), '1.9.0' );
	}
);

add_action(
	'enqueue_block_assets',
	function() {
		wp_enqueue_style(
			'slick-style',
			get_theme_file_uri( '/css/slick.css' ),
			array(),
			'1.9.0'
		);
	}
);

add_action(
	'enqueue_block_editor_assets',
	function() {
		wp_enqueue_script(
			'slick',
			get_theme_file_uri( '/js/slick.js' ),
			array( 'wp-blocks', 'wp-element', 'wp-hooks', 'jquery' ),
			'1.9.0',
			true
		);
	}
);

if ( function_exists( 'acf_add_local_field_group' ) ) :

	acf_add_local_field_group( array(
		'key' => 'group_5fc5978be30ee',
		'title' => 'スライダー要素',
		'fields' => array(
			array(
				'key' => 'field_5fc59799aa8cd',
				'label' => 'スライダー要素',
				'name' => 'slider_elements',
				'type' => 'flexible_content',
				'instructions' => 'スライダーに使う様々な要素を指定してください。',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'layouts' => array(
					'layout_5fc597afb281a' => array(
						'key' => 'layout_5fc597afb281a',
						'name' => 'latest_webinars',
						'label' => '最新Webinar一覧',
						'display' => 'block',
						'sub_fields' => array(
							array(
								'key' => 'field_5fc597d9aa8ce',
								'label' => 'num',
								'name' => 'num',
								'type' => 'number',
								'instructions' => '表示するWebinarの数を指定',
								'required' => 1,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'default_value' => 5,
								'placeholder' => 5,
								'prepend' => '',
								'append' => '',
								'min' => '',
								'max' => '',
								'step' => '',
							),
							array(
								'key' => 'field_5fc59a83aa8d6',
								'label' => 'ボタンテキスト',
								'name' => 'button_text',
								'type' => 'text',
								'instructions' => '表示するボタンテキストです（全て共通）。',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'default_value' => '参加する',
								'placeholder' => '参加する',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
							),
						),
						'min' => '',
						'max' => '',
					),
					'layout_5fc59821aa8cf' => array(
						'key' => 'layout_5fc59821aa8cf',
						'name' => 'series',
						'label' => 'シリーズ',
						'display' => 'block',
						'sub_fields' => array(
							array(
								'key' => 'field_5fc59837aa8d0',
								'label' => 'シリーズ',
								'name' => 'series',
								'type' => 'taxonomy',
								'instructions' => '',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'taxonomy' => 'series',
								'field_type' => 'select',
								'allow_null' => 0,
								'add_term' => 0,
								'save_terms' => 0,
								'load_terms' => 0,
								'return_format' => 'object',
								'multiple' => 0,
							),
							array(
								'key' => 'field_5fc5988eaa8d1',
								'label' => 'リード',
								'name' => 'lead',
								'type' => 'text',
								'instructions' => 'シリーズ名の下に表示するリード文章を指定できます',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
							),
							array(
								'key' => 'field_5fc59ac5aa8d7',
								'label' => 'ボタンテキスト',
								'name' => 'button_text',
								'type' => 'text',
								'instructions' => '表示するボタンテキストです',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'default_value' => '詳細',
								'placeholder' => '詳細',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
							),
						),
						'min' => '',
						'max' => '',
					),
					'layout_5fc598c5aa8d2' => array(
						'key' => 'layout_5fc598c5aa8d2',
						'name' => 'post',
						'label' => '投稿(ブログ、ページ、ウェビナー、エピソード)',
						'display' => 'block',
						'sub_fields' => array(
							array(
								'key' => 'field_5fc598d2aa8d3',
								'label' => '投稿オブジェクト',
								'name' => 'post_obj',
								'type' => 'post_object',
								'instructions' => '表示するエピソード単体を選ぶことができます',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'post_type' => array(
									0 => 'podcast',
									1 => 'webinar',
									2 => 'post',
									3 => 'page',
								),
								'taxonomy' => '',
								'allow_null' => 0,
								'multiple' => 0,
								'return_format' => 'object',
								'ui' => 1,
							),
							array(
								'key' => 'field_5fc5999daa8d5',
								'label' => 'タイトル',
								'name' => 'title',
								'type' => 'text',
								'instructions' => 'スライダーに使うタイトルです。指定しなければ、投稿タイトルを使います。',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
							),
							array(
								'key' => 'field_5fc5995caa8d4',
								'label' => 'リード',
								'name' => 'lead',
								'type' => 'text',
								'instructions' => 'スライダーに使うリード文章(タイトルの下)です。指定しなければ、抜粋を使います。',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
							),
							array(
								'key' => 'field_5fc59b1daa8d8',
								'label' => 'ボタンテキスト',
								'name' => 'button_text',
								'type' => 'text',
								'instructions' => 'ボタンに表示するテキストです',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'default_value' => '今すぐ視聴する',
								'placeholder' => '今すぐ視聴する',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
							),
						),
						'min' => '',
						'max' => '',
					),
				),
				'button_label' => '行を追加',
				'min' => '',
				'max' => '',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'block',
					'operator' => '==',
					'value' => 'acf/kameradio-slider',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => '',
	));

endif;
