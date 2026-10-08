<?php

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', PLATEJKA_TEST_ROOT . DIRECTORY_SEPARATOR );
}

$command_file = PLATEJKA_TEST_ROOT . '/plugins/platejka-core/src/Cli/FaqMigrationCommand.php';
if ( is_file( $command_file ) ) {
	require_once $command_file;
}

final class FaqMigrationCommandTest extends TestCase {
	private const COMMAND = '\\Platejka\\Core\\Cli\\FaqMigrationCommand';

	public function test_merges_columns_in_order_and_ignores_fully_empty_rows(): void {
		$this->assertCommandExists();

		$result = self::COMMAND::mergeLegacyRows(
			array(
				array( 'column1_btn' => 'Q1', 'column1_text' => 'A1' ),
				array( 'column1_btn' => ' ', 'column1_text' => '' ),
				array( 'column1_btn' => 'Q2', 'column1_text' => '' ),
			),
			array(
				array( 'column2_btn' => 'Q3', 'column2_text' => 'A3' ),
			)
		);

		self::assertSame(
			array(
				array( 'title' => 'Q1', 'text' => 'A1' ),
				array( 'title' => 'Q2', 'text' => '' ),
				array( 'title' => 'Q3', 'text' => 'A3' ),
			),
			$result['items']
		);
		self::assertCount( 1, $result['warnings'] );
		self::assertSame( 'repeater_column_1', $result['warnings'][0]['column'] );
		self::assertSame( 3, $result['warnings'][0]['row'] );
	}

	public function test_replaces_every_existing_faq_and_preserves_other_settings(): void {
		$this->assertCommandExists();

		$rows = array(
			array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
			array(
				'acf_fc_layout' => 'faq',
				'enabled'       => 0,
				'anchor'        => 'questions',
				'overrides'     => array(
					'faq' => array(
						'title'        => 'Local title',
						'description'  => 'Local description',
						'primary_link' => array( 'url' => '/help' ),
						'items'        => array( array( 'title' => 'Old', 'text' => 'Old answer' ) ),
					),
				),
			),
			array( 'acf_fc_layout' => 'faq', 'enabled' => 0, 'custom' => 'keep' ),
		);
		$items = array( array( 'title' => 'New', 'text' => 'New answer' ) );

		$result = self::COMMAND::replaceFaqRows( $rows, $items );

		self::assertSame( 2, $result['faq_rows'] );
		self::assertFalse( $result['created'] );
		self::assertSame( 1, $result['rows'][1]['enabled'] );
		self::assertSame( 'questions', $result['rows'][1]['anchor'] );
		self::assertSame( 'Local title', $result['rows'][1]['overrides']['faq']['title'] );
		self::assertSame( array( 'url' => '/help' ), $result['rows'][1]['overrides']['faq']['primary_link'] );
		self::assertSame( $items, $result['rows'][1]['overrides']['faq']['items'] );
		self::assertSame( 'keep', $result['rows'][2]['custom'] );
		self::assertSame( $items, $result['rows'][2]['overrides']['faq']['items'] );
	}

	public function test_creates_faq_immediately_before_call(): void {
		$this->assertCommandExists();

		$result = self::COMMAND::replaceFaqRows(
			array(
				array( 'acf_fc_layout' => 'hero' ),
				array( 'acf_fc_layout' => 'call' ),
				array( 'acf_fc_layout' => 'footer-note' ),
			),
			array( array( 'title' => 'Q', 'text' => 'A' ) )
		);

		self::assertTrue( $result['created'] );
		self::assertSame( array( 'hero', 'faq', 'call', 'footer-note' ), array_column( $result['rows'], 'acf_fc_layout' ) );
		self::assertSame( 1, $result['rows'][1]['enabled'] );
		self::assertSame( '', $result['rows'][1]['anchor'] );
		self::assertSame( 'Q', $result['rows'][1]['overrides']['faq']['items'][0]['title'] );
	}

	public function test_normalizes_raw_acf_field_keys_without_formatting_content(): void {
		$this->assertCommandExists();

		$fields = array(
			'field_enabled'   => array( 'name' => 'enabled', 'type' => 'true_false' ),
			'field_overrides' => array( 'name' => 'overrides', 'type' => 'clone' ),
			'field_faq'       => array( 'name' => 'faq', 'type' => 'group' ),
			'field_items'     => array( 'name' => 'items', 'type' => 'repeater' ),
			'field_title'     => array( 'name' => 'title', 'type' => 'text' ),
			'field_text'      => array( 'name' => 'text', 'type' => 'wysiwyg' ),
		);
		$raw = array(
			array(
				'acf_fc_layout'  => 'faq',
				'field_enabled'  => '1',
				'field_overrides'=> array(
					'field_faq' => array(
						'field_items' => array(
							array( 'field_title' => 'Q', 'field_text' => 'Raw - text' ),
						),
					),
				),
			),
		);

		$result = self::COMMAND::normalizeRawValue(
			$raw,
			static fn( string $key ): ?array => $fields[ $key ] ?? null
		);

		self::assertSame( 1, $result[0]['enabled'] );
		self::assertSame( 'Raw - text', $result[0]['overrides']['faq']['items'][0]['text'] );
	}

	private function assertCommandExists(): void {
		self::assertTrue( class_exists( self::COMMAND ), 'FaqMigrationCommand has not been implemented yet.' );
	}
}
