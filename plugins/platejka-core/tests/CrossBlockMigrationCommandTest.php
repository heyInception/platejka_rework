<?php

use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value, int $flags = 0, int $depth = 512 ) {
		return json_encode( $value, $flags, $depth );
	}
}
if ( ! function_exists( 'get_page_by_path' ) ) {
	function get_page_by_path( string $slug, string $output = 'OBJECT', string $post_type = 'page' ) {
		unset( $output, $post_type );
		return $GLOBALS['platejka_cross_command_pages'][ $slug ] ?? null;
	}
}
if ( ! function_exists( 'get_field' ) ) {
	function get_field( string $field, $context = false, bool $format = true ) {
		unset( $format );
		return $GLOBALS['platejka_cross_command_fields'][ (string) $context ][ $field ] ?? array();
	}
}
if ( ! function_exists( 'update_field' ) ) {
	function update_field( string $field, $value, $context = false ): bool {
		$GLOBALS['platejka_cross_command_updates'][] = array( $field, $value, $context );
		$GLOBALS['platejka_cross_command_fields'][ (string) $context ][ $field ] = $value;
		return true;
	}
}
if ( ! function_exists( 'get_post_status' ) ) {
	function get_post_status( int $post_id ): string {
		return $GLOBALS['platejka_cross_command_status'][ $post_id ] ?? '';
	}
}
if ( ! function_exists( 'get_post_field' ) ) {
	function get_post_field( string $field, int $post_id ): string {
		return 'post_name' === $field ? ( $GLOBALS['platejka_cross_command_slugs'][ $post_id ] ?? '' ) : '';
	}
}

$reader = dirname( __DIR__ ) . '/src/Migration/CrossBlockSqlDumpReader.php';
$planner = dirname( __DIR__ ) . '/src/Migration/CrossBlockMigrationPlanner.php';
$command = dirname( __DIR__ ) . '/src/Cli/CrossBlockMigrationCommand.php';
foreach ( array( $reader, $planner, $command ) as $file ) {
	if ( is_file( $file ) ) {
		require_once $file;
	}
}

final class CrossBlockMigrationCommandTest extends TestCase {
	private const COMMAND = '\\Platejka\\Core\\Cli\\CrossBlockMigrationCommand';

	protected function setUp(): void {
		$GLOBALS['platejka_cross_command_updates'] = array();
		$GLOBALS['platejka_cross_command_pages'] = array(
			'perevody-v-evropu-dlya-yurlicz' => (object) array( 'ID' => 77, 'post_name' => 'perevody-v-evropu-dlya-yurlicz', 'post_status' => 'publish' ),
		);
		$GLOBALS['platejka_cross_command_status'] = array( 77 => 'publish' );
		$GLOBALS['platejka_cross_command_slugs'] = array( 77 => 'perevody-v-evropu-dlya-yurlicz' );
		$GLOBALS['platejka_cross_command_fields'] = array(
			'platejka_section_defaults' => array(),
			'77' => array(
				'field_platejka_page_builder_v1' => 0,
				'field_platejka_sections_v1' => array(
					array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
					array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
				),
			),
		);
	}

	public function test_preview_is_stable_and_performs_no_database_writes(): void {
		$this->assertCommandExists();
		$first = self::COMMAND::preview( $this->fixturePath() );
		$second = self::COMMAND::preview( $this->fixturePath() );

		self::assertSame( $first, $second );
		self::assertSame( 'cross-block-migration-v1', $first['schema'] );
		self::assertNotSame( '', $first['integrity'] );
		self::assertSame( array(), $GLOBALS['platejka_cross_command_updates'] );
		self::assertSame( 'dry-run', $first['mode'] );
	}

	public function test_preview_rejects_a_missing_sql_file(): void {
		$this->assertCommandExists();
		$this->expectException( RuntimeException::class );
		self::COMMAND::preview( $this->fixturePath() . '.missing' );
	}

	public function test_apply_rejects_missing_backup_and_tampered_manifest_before_writes(): void {
		$this->assertCommandExists();
		$manifest = self::COMMAND::preview( $this->fixturePath() );

		try {
			self::COMMAND::applyManifest( $manifest, '' );
			self::fail( 'A backup path is mandatory.' );
		} catch ( RuntimeException $error ) {
			self::assertStringContainsString( 'backup', strtolower( $error->getMessage() ) );
		}

		$manifest['plan']['totals']['page_writes']++;
		$this->expectException( RuntimeException::class );
		try {
			self::COMMAND::applyManifest( $manifest, sys_get_temp_dir() . '/cross-block-command-backup.json' );
		} finally {
			self::assertSame( array(), $GLOBALS['platejka_cross_command_updates'] );
		}
	}

	public function test_apply_rejects_stale_fingerprint_slug_mismatch_and_excluded_targets(): void {
		$this->assertCommandExists();
		$manifest = self::COMMAND::preview( $this->fixturePath() );
		$backup = sys_get_temp_dir() . '/cross-block-command-backup.json';

		$GLOBALS['platejka_cross_command_fields']['77']['field_platejka_sections_v1'][0]['enabled'] = 0;
		$this->assertRejected( $manifest, $backup, 'fingerprint' );

		$this->setUp();
		$manifest = self::COMMAND::preview( $this->fixturePath() );
		$GLOBALS['platejka_cross_command_slugs'][77] = 'different-slug';
		$this->assertRejected( $manifest, $backup, 'identity' );

		$this->setUp();
		$manifest = self::COMMAND::preview( $this->fixturePath() );
		$manifest['plan']['pages'][0]['target_rows'][] = array( 'acf_fc_layout' => 'documents', 'enabled' => 1 );
		$manifest = self::COMMAND::sealManifest( $manifest );
		$this->assertRejected( $manifest, $backup, 'excluded' );
		self::assertSame( array(), $GLOBALS['platejka_cross_command_updates'] );
	}

	private function assertRejected( array $manifest, string $backup, string $message ): void {
		try {
			self::COMMAND::applyManifest( $manifest, $backup );
			self::fail( 'Manifest should have been rejected.' );
		} catch ( RuntimeException $error ) {
			self::assertStringContainsString( $message, strtolower( $error->getMessage() ) );
		}
	}

	private function fixturePath(): string {
		return dirname( __DIR__, 3 ) . '/tests/fixtures/cross-block-production.sql';
	}

	private function assertCommandExists(): void {
		self::assertTrue( class_exists( self::COMMAND ), 'CrossBlockMigrationCommand has not been implemented yet.' );
	}
}
