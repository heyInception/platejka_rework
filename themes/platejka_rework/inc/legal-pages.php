<?php
/**
 * Legal-page routing shared by page and footer templates.
 *
 * @package platejka_rework
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the fixed pages that use the legal-document layout.
 *
 * @return array<int, string>
 */
function platejka_rework_legal_pages(): array {
	return array(
		3    => __( 'Политика конфиденциальности', 'platejka_rework' ),
		5170 => __( 'Политика использования cookie-файлов', 'platejka_rework' ),
		5114 => __( 'Пользовательское соглашение', 'platejka_rework' ),
		1469 => __( 'Карта сайта', 'platejka_rework' ),
	);
}

/** Determine whether a post uses the legal-document layout. */
function platejka_rework_is_legal_page( int $post_id ): bool {
	return isset( platejka_rework_legal_pages()[ $post_id ] );
}
