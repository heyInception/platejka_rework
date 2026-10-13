<?php
/** Read-only integration coverage for the cross-block migration preview. */

use Platejka\Core\Cli\CrossBlockMigrationCommand;

if ( ! class_exists( CrossBlockMigrationCommand::class ) ) {
	WP_CLI::error( 'CrossBlockMigrationCommand is not registered.' );
}

global $wpdb;
$sql_path = WP_CONTENT_DIR . '/a0856023_app_wordpress_20.sql';
$hash_state = static function () use ( $wpdb ): string {
	$options = $wpdb->get_results(
		"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'options_cross_%' OR option_name LIKE '_options_cross_%' OR option_name IN ('options_problems', '_options_problems', 'options_guarantees', '_options_guarantees', 'options_table', '_options_table') OR option_name LIKE 'options_problems_%' OR option_name LIKE '_options_problems_%' OR option_name LIKE 'options_guarantees_%' OR option_name LIKE '_options_guarantees_%' OR option_name LIKE 'options_table_%' OR option_name LIKE '_options_table_%' ORDER BY option_name",
		ARRAY_A
	);
	$postmeta = $wpdb->get_results(
		"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ('platejka_page_builder', '_platejka_page_builder', 'platejka_sections', '_platejka_sections') OR meta_key LIKE 'platejka_sections_%' OR meta_key LIKE '_platejka_sections_%' ORDER BY post_id, meta_key, meta_id",
		ARRAY_A
	);
	return hash( 'sha256', wp_json_encode( array( $options, $postmeta ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
};

$before = $hash_state();
$first  = CrossBlockMigrationCommand::preview( $sql_path );
$second = CrossBlockMigrationCommand::preview( $sql_path );
$after  = $hash_state();

if ( $before !== $after ) {
	WP_CLI::error( 'Dry-run changed relevant database state.' );
}
if ( $first !== $second ) {
	WP_CLI::error( 'Dry-run manifest is not deterministic.' );
}
if ( 'cross-block-migration-v1' !== ( $first['schema'] ?? '' ) || 'dry-run' !== ( $first['mode'] ?? '' ) ) {
	WP_CLI::error( 'Dry-run manifest envelope is invalid.' );
}
if ( 7 !== count( $first['production']['defaults'] ?? array() ) ) {
	WP_CLI::error( 'Expected all seven dedicated production defaults.' );
}
if ( empty( $first['integrity'] ) ) {
	WP_CLI::error( 'Dry-run manifest is not sealed.' );
}

WP_CLI::success(
	sprintf(
		'Cross-block dry-run is read-only and deterministic: %d default writes, %d page writes, %d conflicts, %d warnings.',
		(int) ( $first['plan']['totals']['default_writes'] ?? 0 ),
		(int) ( $first['plan']['totals']['page_writes'] ?? 0 ),
		(int) ( $first['plan']['totals']['conflicts'] ?? 0 ),
		(int) ( $first['plan']['totals']['warnings'] ?? 0 )
	)
);
