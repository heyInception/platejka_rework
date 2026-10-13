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
		if ( str_starts_with( $field, 'field_platejka_defaults_' ) && is_array( $value ) && ! array_is_list( $value ) ) {
			$value = array_reverse( $value, true );
		}
		if ( 'field_platejka_sections_v1' === $field && ! empty( $GLOBALS['platejka_cross_command_adds_empty_schema_fields'] ) && is_array( $value[0] ?? null ) ) {
			$value[0]['schema_default'] = '';
		}
		$GLOBALS['platejka_cross_command_fields'][ (string) $context ][ $field ] = $value;
		return empty( $GLOBALS['platejka_cross_command_update_returns_false'] );
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
if ( ! function_exists( 'acf_get_field' ) ) {
	function acf_get_field( string $key ) {
		return $GLOBALS['platejka_cross_command_acf_fields'][ $key ] ?? null;
	}
}
if ( ! function_exists( 'acf_update_value' ) ) {
	function acf_update_value( $value, $context, array $field ): bool {
		$GLOBALS['platejka_cross_command_acf_value_updates'][] = array( $value, $context, $field );
		$key = (string) ( $field['key'] ?? '' );
		$GLOBALS['platejka_cross_command_fields'][ (string) $context ][ $key ] = $value;
		return empty( $GLOBALS['platejka_cross_command_update_returns_false'] );
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
		$GLOBALS['platejka_cross_command_update_returns_false'] = false;
		$GLOBALS['platejka_cross_command_adds_empty_schema_fields'] = false;
		$GLOBALS['platejka_cross_command_acf_fields'] = array();
		$GLOBALS['platejka_cross_command_acf_value_updates'] = array();
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

	public function test_apply_creates_backup_writes_and_verifies_the_approved_manifest(): void {
		$this->assertCommandExists();
		$manifest = self::COMMAND::preview( $this->fixturePath() );
		$backup = sys_get_temp_dir() . '/cross-block-command-valid-backup.json';
		if ( is_file( $backup ) ) {
			unlink( $backup );
		}

		try {
			$result = self::COMMAND::applyManifest( $manifest, $backup );
			self::assertTrue( $result['verified'] );
			self::assertSame( count( $manifest['plan']['defaults'] ), $result['writes']['defaults'] );
			self::assertSame( count( $manifest['plan']['pages'] ), $result['writes']['pages'] );
			self::assertFileExists( $backup );
			self::assertNotEmpty( $GLOBALS['platejka_cross_command_updates'] );
		} finally {
			if ( is_file( $backup ) ) {
				unlink( $backup );
			}
		}
	}

	public function test_apply_uses_reread_verification_when_acf_returns_false_for_an_identical_value(): void {
		$this->assertCommandExists();
		$manifest = self::COMMAND::preview( $this->fixturePath() );
		$backup = sys_get_temp_dir() . '/cross-block-command-false-return-backup.json';
		if ( is_file( $backup ) ) {
			unlink( $backup );
		}
		$GLOBALS['platejka_cross_command_update_returns_false'] = true;

		try {
			$result = self::COMMAND::applyManifest( $manifest, $backup );
			self::assertTrue( $result['verified'] );
			self::assertSame( count( $manifest['plan']['pages'] ), $result['writes']['pages'] );
		} finally {
			if ( is_file( $backup ) ) {
				unlink( $backup );
			}
		}
	}

	public function test_apply_verification_accepts_empty_fields_materialized_by_acf_schema(): void {
		$this->assertCommandExists();
		$manifest = self::COMMAND::preview( $this->fixturePath() );
		$backup = sys_get_temp_dir() . '/cross-block-command-schema-default-backup.json';
		if ( is_file( $backup ) ) {
			unlink( $backup );
		}
		$GLOBALS['platejka_cross_command_adds_empty_schema_fields'] = true;

		try {
			$result = self::COMMAND::applyManifest( $manifest, $backup );
			self::assertTrue( $result['verified'] );
		} finally {
			if ( is_file( $backup ) ) {
				unlink( $backup );
			}
		}
	}

	public function test_verification_treats_a_materialized_empty_clone_as_null_but_rejects_content(): void {
		$this->assertCommandExists();
		$equivalent = new ReflectionMethod( self::COMMAND, 'equivalentValue' );
		$empty_clone = array(
			'cross_company_metrics' => array(
				'title' => null,
				'metrics' => false,
				'cta' => null,
			),
		);
		self::assertTrue( $equivalent->invoke( null, $empty_clone, null ) );
		$empty_clone['cross_company_metrics']['title'] = 'Локальный заголовок';
		self::assertFalse( $equivalent->invoke( null, $empty_clone, null ) );
	}

	public function test_apply_writes_clone_overrides_with_acf_field_keys(): void {
		$this->assertCommandExists();
		$GLOBALS['platejka_cross_command_acf_fields'] = array(
			'field_platejka_sections_v1' => array(
				'key' => 'field_platejka_sections_v1',
				'name' => 'platejka_sections',
				'type' => 'flexible_content',
				'layouts' => array(
					array(
						'name' => 'cross-swift-problems',
						'sub_fields' => array(
							array( 'key' => 'field_swift_enabled', 'name' => 'enabled', 'type' => 'true_false' ),
							array( 'key' => 'field_swift_anchor', 'name' => 'anchor', 'type' => 'text' ),
							array( 'key' => 'field_swift_overrides', 'name' => 'overrides', 'type' => 'clone', 'clone' => array( 'field_swift_group' ), 'display' => 'group' ),
						),
					),
				),
			),
			'field_swift_enabled' => array( 'key' => 'field_swift_enabled', 'name' => 'enabled', 'type' => 'true_false' ),
			'field_swift_anchor' => array( 'key' => 'field_swift_anchor', 'name' => 'anchor', 'type' => 'text' ),
			'field_swift_overrides' => array(
				'key' => 'field_swift_overrides',
				'name' => 'overrides',
				'_name' => 'overrides',
				'type' => 'clone',
				'clone' => array( 'field_swift_group' ),
				'display' => 'group',
				'sub_fields' => array( array( 'key' => 'field_swift_group', 'name' => 'cross_swift_problems', 'type' => 'group', 'sub_fields' => array( array( 'key' => 'field_swift_title', 'name' => 'title', 'type' => 'text' ) ) ) ),
			),
			'field_swift_group' => array( 'key' => 'field_swift_group', 'name' => 'cross_swift_problems', 'type' => 'group', 'sub_fields' => array( array( 'key' => 'field_swift_title', 'name' => 'title', 'type' => 'text' ) ) ),
			'field_swift_title' => array( 'key' => 'field_swift_title', 'name' => 'title', 'type' => 'text' ),
		);
		$manifest = self::COMMAND::preview( $this->fixturePath() );
		$backup = sys_get_temp_dir() . '/cross-block-command-field-key-backup.json';
		if ( is_file( $backup ) ) {
			unlink( $backup );
		}

		try {
			$result = self::COMMAND::applyManifest( $manifest, $backup );
			self::assertTrue( $result['verified'] );
			$acf_write = current( array_filter( $GLOBALS['platejka_cross_command_acf_value_updates'], static fn( array $write ): bool => 'field_platejka_sections_v1' === ( $write[2]['key'] ?? '' ) ) );
			self::assertNotFalse( $acf_write );
			$swift = current( array_filter( $acf_write[0], static fn( array $row ): bool => 'cross-swift-problems' === ( $row['acf_fc_layout'] ?? '' ) ) );
			self::assertArrayHasKey( 'field_swift_overrides', $swift );
			self::assertSame( 'Почему прямой SWIFT не работает', $swift['field_swift_overrides']['field_swift_group']['field_swift_title'] );
			$hydrated_layout = current( array_filter( $acf_write[2]['layouts'], static fn( array $layout ): bool => 'cross-swift-problems' === ( $layout['name'] ?? '' ) ) );
			$hydrated_clone = current( array_filter( $hydrated_layout['sub_fields'], static fn( array $field ): bool => 'overrides' === ( $field['name'] ?? '' ) ) );
			self::assertNotEmpty( $hydrated_clone['sub_fields'] ?? array(), 'Nested clone schema must be hydrated before ACF updates the flexible content value.' );
		} finally {
			if ( is_file( $backup ) ) {
				unlink( $backup );
			}
		}
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
