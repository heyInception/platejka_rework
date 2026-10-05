<?php

namespace Platejka\Core\Acf;

defined( 'ABSPATH' ) || exit;

/** Register the canonical section-defaults options page. */
final class SectionDefaults {
	private static bool $registered = false;

	public static function register(): void {
		if ( self::$registered || ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		self::$registered = true;
		if ( did_action( 'acf/init' ) ) {
			self::addPage();
			return;
		}

		add_action( 'acf/init', array( self::class, 'addPage' ) );
	}

	public static function addPage(): void {
		acf_add_options_page(
			array(
				'page_title' => __( 'Сквозные секции', 'platejka-core' ),
				'menu_title' => __( 'Сквозные секции', 'platejka-core' ),
				'menu_slug'  => 'platejka-section-defaults',
				'capability' => 'edit_pages',
				'post_id'    => 'platejka_section_defaults',
				'redirect'   => false,
				'autoload'   => false,
			)
		);
	}
}
