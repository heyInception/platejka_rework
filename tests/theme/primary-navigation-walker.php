<?php
/**
 * WP-CLI smoke test for the primary navigation walker.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

if ( ! class_exists( 'Platejka_Rework_Nav_Walker' ) ) {
	WP_CLI::error( 'Primary navigation walker is not loaded.' );
}

$item = static function ( int $id, string $title ): object {
	return (object) array(
		'ID'     => $id,
		'title'  => $title,
		'target' => '',
		'xfn'    => '',
		'url'    => home_url( '/menu-item-' . $id . '/' ),
	);
};

$walker = new Platejka_Rework_Nav_Walker();
$output = '';

$walker->has_children = true;
$walker->start_el( $output, $item( 10, 'Валютные переводы' ), 0, (object) array() );
$walker->start_lvl( $output, 0 );
$walker->has_children = true;
$walker->start_el( $output, $item( 20, 'Трансграничные переводы' ), 1, (object) array() );
$walker->start_lvl( $output, 1 );
$walker->has_children = false;
$walker->start_el( $output, $item( 30, 'Переводы в Европу' ), 2, (object) array() );

$expected_fragments = array(
	'<li class="nav__item"><div class="nav__heading">',
	'class="nav__link"',
	'aria-controls="submenu-10"',
	'<ul class="list-reset nav__submenu" id="submenu-10">',
	'<li class="nav__subitem nav__subitem--has-children"><div class="nav__subheading">',
	'aria-controls="submenu-20"',
	'<ul class="list-reset nav__submenu nav__submenu--nested" id="submenu-20">',
	'class="nav__sublink"',
);

foreach ( $expected_fragments as $fragment ) {
	if ( false === strpos( $output, $fragment ) ) {
		WP_CLI::error( 'Missing navigation fragment: ' . $fragment );
	}
}

WP_CLI::success( 'Primary navigation walker markup is valid.' );
