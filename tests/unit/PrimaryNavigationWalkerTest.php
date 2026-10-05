<?php

use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'Walker_Nav_Menu' ) ) {
	class Walker_Nav_Menu {
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook_name, mixed $value, mixed ...$args ): mixed {
		return $value;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( mixed $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( mixed $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( mixed $value ): string {
		return filter_var( (string) $value, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( mixed $value ): int {
		return abs( (int) $value );
	}
}

require_once PLATEJKA_TEST_ROOT . '/themes/platejka_rework/inc/class-platejka-rework-nav-walker.php';

final class PrimaryNavigationWalkerTest extends TestCase {
	public function test_it_builds_the_expected_three_level_navigation_markup(): void {
		$walker = new Platejka_Rework_Nav_Walker();
		$output = '';

		$walker->has_children = true;
		$walker->start_el( $output, $this->item( 10, 'Валютные переводы' ), 0, (object) array() );
		$walker->start_lvl( $output, 0 );
		$walker->has_children = true;
		$walker->start_el( $output, $this->item( 20, 'Трансграничные переводы' ), 1, (object) array() );
		$walker->start_lvl( $output, 1 );
		$walker->has_children = false;
		$walker->start_el( $output, $this->item( 30, 'Переводы в Европу' ), 2, (object) array() );

		$this->assertStringContainsString( '<li class="nav__item"><div class="nav__heading">', $output );
		$this->assertStringContainsString( 'class="nav__link"', $output );
		$this->assertStringContainsString( 'aria-controls="submenu-10"', $output );
		$this->assertStringContainsString( '<ul class="list-reset nav__submenu" id="submenu-10">', $output );
		$this->assertStringContainsString( '<li class="nav__subitem nav__subitem--has-children"><div class="nav__subheading">', $output );
		$this->assertStringContainsString( 'aria-controls="submenu-20"', $output );
		$this->assertStringContainsString( '<ul class="list-reset nav__submenu nav__submenu--nested" id="submenu-20">', $output );
		$this->assertStringContainsString( '<li><a href="https://example.com/30" class="nav__sublink">Переводы в Европу</a>', $output );
	}

	private function item( int $id, string $title ): object {
		return (object) array(
			'ID'     => $id,
			'title'  => $title,
			'target' => '',
			'xfn'    => '',
			'url'    => 'https://example.com/' . $id,
		);
	}
}
