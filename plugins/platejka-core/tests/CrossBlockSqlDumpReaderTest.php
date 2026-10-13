<?php

use PHPUnit\Framework\TestCase;

$reader_file = dirname( __DIR__ ) . '/src/Migration/CrossBlockSqlDumpReader.php';
if ( is_file( $reader_file ) ) {
	require_once $reader_file;
}

final class CrossBlockSqlDumpReaderTest extends TestCase {
	private const READER = '\Platejka\Core\Migration\CrossBlockSqlDumpReader';

	public function test_reads_only_cross_block_values_and_page_mapping_rows(): void {
		$this->assertReaderExists();
		$class    = self::READER;
		$snapshot = ( new $class( $this->fixturePath() ) )->read();

		self::assertSame(
			array(
				2182 => array( 'ID' => 2182, 'post_name' => 'perevody-v-evropu-dlya-yurlicz', 'post_status' => 'publish', 'post_type' => 'page' ),
				2183 => array( 'ID' => 2183, 'post_name' => 'chernovik-cross-blocks', 'post_status' => 'draft', 'post_type' => 'page' ),
			),
			$snapshot['posts']
		);
		self::assertArrayNotHasKey( 2184, $snapshot['posts'] );
		self::assertArrayNotHasKey( 'siteurl', $snapshot['options'] );
		self::assertArrayNotHasKey( 'unrelated_meta', $snapshot['postmeta'][2182] );
		self::assertSame( 'Последний заголовок', $snapshot['options']['options_skvoznye_bloki_0_title'] );
		self::assertSame( 'Почему прямой SWIFT не работает', $snapshot['postmeta'][2182]['skvoznye_bloki_0_title'] );
		self::assertSame( 'a:2:{i:0;s:14:"swift_problems";i:1;s:8:"audience";}', $snapshot['postmeta'][2182]['skvoznye_bloki'] );
	}

	public function test_decodes_mysql_escapes_and_embedded_newlines_without_truncation(): void {
		$this->assertReaderExists();
		$class    = self::READER;
		$snapshot = ( new $class( $this->fixturePath() ) )->read();

		self::assertSame(
			"Строка с 'кавычкой', слешем C:\\Temp и переносом\nниже",
			$snapshot['options']['options_skvoznye_bloki_0_intro']
		);
		self::assertSame( "Первая\nвторая", $snapshot['options']['options_skvoznye_bloki_0_multiline'] );
	}

	public function test_last_tuple_wins_and_duplicate_or_malformed_rows_are_reported(): void {
		$this->assertReaderExists();
		$class    = self::READER;
		$snapshot = ( new $class( $this->fixturePath() ) )->read();
		$types    = array_column( $snapshot['warnings'], 'type' );

		self::assertContains( 'duplicate_option', $types );
		self::assertContains( 'duplicate_postmeta', $types );
		self::assertContains( 'malformed_tuple', $types );
	}

	public function test_rejects_an_unreadable_dump(): void {
		$this->assertReaderExists();
		$class = self::READER;

		$this->expectException( RuntimeException::class );
		( new $class( $this->fixturePath() . '.missing' ) )->read();
	}

	private function fixturePath(): string {
		return dirname( __DIR__, 3 ) . '/tests/fixtures/cross-block-production.sql';
	}

	private function assertReaderExists(): void {
		self::assertTrue( class_exists( self::READER ), 'CrossBlockSqlDumpReader has not been implemented yet.' );
	}
}
