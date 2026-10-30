<?php
/** Inline Marquiz placement for marketing pages. */

defined( 'ABSPATH' ) || exit;

function platejka_marquiz_register_fields(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$positions = array();
	for ( $number = 1; $number <= 30; ++$number ) {
		$positions[ (string) $number ] = sprintf( 'После секции № %d', $number );
	}

	acf_add_local_field_group( array(
		'key'      => 'group_platejka_marquiz_placement_v1',
		'title'    => 'Размещение квиза Marquiz',
		'fields'   => array(
			array(
				'key'           => 'field_platejka_marquiz_mode_v1',
				'label'         => 'Показ квиза',
				'name'          => 'platejka_marquiz_mode',
				'type'          => 'select',
				'choices'       => array( 'auto' => 'Автоматически после третьей секции', 'hidden' => 'Скрыть', 'custom' => 'Выбрать позицию' ),
				'default_value' => 'auto',
				'ui'            => 1,
			),
			array(
				'key'               => 'field_platejka_marquiz_after_v1',
				'label'             => 'Позиция квиза',
				'name'              => 'platejka_marquiz_after',
				'type'              => 'select',
				'choices'           => $positions,
				'default_value'     => '3',
				'instructions'      => 'Секции считаются сверху вниз, начиная с Hero. Если секций меньше выбранного числа, квиз появится после последней.',
				'ui'                => 1,
				'conditional_logic' => array( array( array( 'field' => 'field_platejka_marquiz_mode_v1', 'operator' => '==', 'value' => 'custom' ) ) ),
			),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'page' ) ) ),
		'position' => 'acf_after_title',
		'active'   => true,
	) );
}
add_action( 'acf/init', 'platejka_marquiz_register_fields' );

function platejka_marquiz_section_position( int $post_id, int $section_count ): int {
	if ( $section_count < 1 || platejka_rework_is_legal_page( $post_id ) ) {
		return 0;
	}

	$mode = function_exists( 'get_field' ) ? get_field( 'platejka_marquiz_mode', $post_id ) : 'auto';
	if ( 'hidden' === $mode ) {
		return 0;
	}

	$after = 'custom' === $mode && function_exists( 'get_field' ) ? (int) get_field( 'platejka_marquiz_after', $post_id ) : 3;
	return min( max( 1, $after ), $section_count );
}

function platejka_render_marquiz_section(): void {
	?>
	<section class="platejka-marquiz" aria-label="Расчёт транзакции">
		<div class="container"><div data-marquiz-id="689b95fd327d1700199c7e16"></div></div>
	</section>
	<script>
		(function(t, p) {
			window.Marquiz ? Marquiz.add([t, p]) : document.addEventListener('marquizLoaded', function() {
				Marquiz.add([t, p]);
			});
		})('Inline', {
			id: '689b95fd327d1700199c7e16',
			buttonText: 'Получить расчет транзакции',
			bgColor: '#00a95f',
			textColor: '#ffffff',
			rounded: true,
			shadow: 'rgba(0, 169, 95, 0.5)',
			blicked: true,
			fixed: false,
			buttonOnMobile: true,
			disableOnMobile: false,
			fullWidth: false
		});
	</script>
	<?php
}

function platejka_marquiz_enqueue_style(): void {
	if ( ! is_page() || is_page_template( array( 'page-about.php', 'page-services.php' ) ) || platejka_rework_is_legal_page( get_queried_object_id() ) ) {
		return;
	}
	$path = get_theme_file_path( 'assets/marquiz.css' );
	wp_enqueue_style( 'platejka-marquiz', get_theme_file_uri( 'assets/marquiz.css' ), array( 'platejka_rework-commoncss' ), platejka_section_asset_version( $path ) );
}
add_action( 'wp_enqueue_scripts', 'platejka_marquiz_enqueue_style' );

function platejka_render_page_sections_with_marquiz( array $sections, int $post_id ): void {
	$position = platejka_marquiz_section_position( $post_id, count( $sections ) );
	foreach ( $sections as $index => $section ) {
		platejka_render_section( $section );
		if ( $position === $index + 1 ) {
			platejka_render_marquiz_section();
		}
	}
}
