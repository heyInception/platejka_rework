<?php

use PHPUnit\Framework\TestCase;

$planner_file = dirname( __DIR__ ) . '/src/Migration/CrossBlockMigrationPlanner.php';
if ( is_file( $planner_file ) ) {
	require_once $planner_file;
}

final class CrossBlockMigrationPlannerTest extends TestCase {
	private const PLANNER = '\\Platejka\\Core\\Migration\\CrossBlockMigrationPlanner';

	public function test_extracts_only_enabled_approved_layouts_and_builds_nested_values(): void {
		$this->assertPlannerExists();
		$snapshot = array(
			'options' => array(
				'options_skvoznye_bloki'                       => serialize( array( 'wtt', 'swift_problems', 'destinations', 'external_ratings' ) ),
				'options_skvoznye_bloki_0_vklyuchit_blok'     => '1',
				'options_skvoznye_bloki_0_title'               => 'Обмен валют',
				'options_skvoznye_bloki_1_show_block'          => '1',
				'options_skvoznye_bloki_1_title'               => 'Почему SWIFT может не работать',
				'options_skvoznye_bloki_1_intro'               => 'Общий текст',
				'options_skvoznye_bloki_1_items'               => '2',
				'options_skvoznye_bloki_1_items_0_title'       => 'Банки',
				'options_skvoznye_bloki_1_items_0_text'        => 'Текст 1',
				'options_skvoznye_bloki_1_items_1_title'       => 'Комплаенс',
				'options_skvoznye_bloki_1_items_1_text'        => 'Текст 2',
				'options_skvoznye_bloki_2_vklyuchit_blok'     => '1',
				'options_skvoznye_bloki_2_destination_title'  => 'Не переносить',
				'options_skvoznye_bloki_3_show_block'          => '0',
				'options_skvoznye_bloki_3_title'               => 'Выключено',
			),
			'posts' => array(
				2182 => array( 'ID' => 2182, 'post_name' => 'perevody-v-evropu-dlya-yurlicz', 'post_status' => 'publish', 'post_type' => 'page' ),
				2183 => array( 'ID' => 2183, 'post_name' => 'draft-page', 'post_status' => 'draft', 'post_type' => 'page' ),
			),
			'postmeta' => array(
				2182 => array(
					'skvoznye_bloki'                       => serialize( array( 'swift_problems', 'destinations', 'reviews', 'guarantees', 'problems_solved' ) ),
					'skvoznye_bloki_0_show_block'          => '1',
					'skvoznye_bloki_0_title'               => 'Почему прямой SWIFT не работает',
					'skvoznye_bloki_0_intro'               => '',
					'skvoznye_bloki_0_items'               => '1',
					'skvoznye_bloki_0_items_0_title'       => 'Банк отправителя',
					'skvoznye_bloki_0_items_0_text'        => 'Причина',
					'skvoznye_bloki_1_vklyuchit_blok'     => '1',
					'skvoznye_bloki_2_vklyuchit_blok'     => '1',
					'skvoznye_bloki_3_show_block'          => '1',
					'skvoznye_bloki_3_title'               => 'Гарантии перевода',
					'skvoznye_bloki_3_intro'               => 'Отвечаем за результат',
					'skvoznye_bloki_3_items'               => '1',
					'skvoznye_bloki_3_items_0_title'       => 'Договор',
					'skvoznye_bloki_3_items_0_text'        => 'Фиксируем обязательства',
					'skvoznye_bloki_4_show_block'          => '1',
					'skvoznye_bloki_4_title'               => 'Что можно оплатить',
				),
				2183 => array( 'skvoznye_bloki' => serialize( array( 'audience' ) ), 'skvoznye_bloki_0_show_block' => '1' ),
			),
			'warnings' => array(),
		);

		$production = self::PLANNER::extractProduction( $snapshot );

		self::assertSame( array( 'cross-exchange', 'cross-swift-problems' ), array_keys( $production['defaults'] ) );
		self::assertArrayNotHasKey( 'cross-external-ratings', $production['defaults'] );
		self::assertSame( 'Банки', $production['defaults']['cross-swift-problems']['items'][0]['title'] );
		self::assertSame(
			array( 'cross-swift-problems', 'cross-guarantees', 'problems' ),
			array_keys( $production['pages']['perevody-v-evropu-dlya-yurlicz']['layouts'] )
		);
		self::assertSame( 'Отвечаем за результат', $production['pages']['perevody-v-evropu-dlya-yurlicz']['layouts']['cross-guarantees']['values']['intro'] );
		self::assertArrayNotHasKey( 'intro', $production['pages']['perevody-v-evropu-dlya-yurlicz']['layouts']['cross-swift-problems']['values'] );
		self::assertArrayNotHasKey( 'draft-page', $production['pages'] );
		self::assertContains( 'disabled_layout', array_column( $production['warnings'], 'type' ) );
	}

	public function test_merges_targets_before_faq_and_preserves_first_row_values(): void {
		$this->assertPlannerExists();
		$rows = array(
			array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
			array( 'acf_fc_layout' => 'cross-audience', 'enabled' => 0, 'anchor' => 'keep', 'overrides' => array( 'cross_audience' => array( 'title' => 'Local' ) ) ),
			array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
			array( 'acf_fc_layout' => 'cross-swift-problems', 'enabled' => 0, 'anchor' => 'swift' ),
			array( 'acf_fc_layout' => 'cross-audience', 'enabled' => 1, 'anchor' => 'duplicate' ),
			array( 'acf_fc_layout' => 'cross-company-metrics', 'enabled' => 0, 'anchor' => 'untargeted' ),
			array( 'acf_fc_layout' => 'call', 'enabled' => 1 ),
		);

		$result = self::PLANNER::mergeBuilderRows(
			$rows,
			array(
				'cross-swift-problems' => array( 'title' => 'SWIFT' ),
				'cross-audience'       => array( 'title' => 'Local' ),
				'cross-key-facts'      => array(),
				'documents'            => array( 'title' => 'Excluded' ),
			)
		);

		self::assertSame( array( 'hero', 'cross-audience', 'cross-swift-problems', 'cross-key-facts', 'cross-company-metrics', 'faq', 'call' ), array_column( $result['rows'], 'acf_fc_layout' ) );
		self::assertSame( 'keep', $result['rows'][1]['anchor'] );
		self::assertSame( 'Local', $result['rows'][1]['overrides']['cross_audience']['title'] );
		self::assertSame( 1, $result['rows'][1]['enabled'] );
		self::assertSame( array( 'cross-key-facts' ), $result['added'] );
		self::assertContains( 'cross-swift-problems', $result['moved_before_faq'] );
		self::assertContains( 'cross-company-metrics', $result['moved_before_faq'] );
		self::assertSame( 0, $result['rows'][4]['enabled'], 'Untargeted cross-block visibility must be preserved while relocating it.' );
		self::assertSame( array( 'cross-audience' ), $result['removed_duplicates'] );
		self::assertNotContains( 'documents', array_column( $result['rows'], 'acf_fc_layout' ) );
	}

	public function test_cross_guarantees_are_added_without_changing_standard_guarantees(): void {
		$this->assertPlannerExists();
		$rows = array(
			array(
				'acf_fc_layout' => 'guarantees',
				'enabled' => 0,
				'variant' => 'inherit',
				'overrides_default' => array( 'default' => array( 'title' => '' ) ),
			),
			array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
		);

		$result = self::PLANNER::mergeBuilderRows(
			$rows,
			array( 'cross-guarantees' => array( 'title' => 'Гарантии', 'intro' => 'Текст' ) )
		);

		self::assertSame( array( 'guarantees', 'cross-guarantees', 'faq' ), array_column( $result['rows'], 'acf_fc_layout' ) );
		self::assertSame( array( 'default' => array( 'title' => '' ) ), $result['rows'][0]['overrides_default'] );
		self::assertArrayNotHasKey( 'overrides', $result['rows'][0] );
		self::assertSame( 'inherit', $result['rows'][0]['variant'] );
		self::assertSame( 'Гарантии', $result['rows'][1]['overrides']['cross_guarantees']['title'] );
		self::assertSame( 'Текст', $result['rows'][1]['overrides']['cross_guarantees']['intro'] );
	}

	public function test_plan_splits_migrated_guarantees_into_standard_and_cross_rows(): void {
		$this->assertPlannerExists();
		$values = array(
			'title' => 'Гарантии и ответственность',
			'intro' => 'Отвечаем за результат',
			'items' => array( array( 'title' => 'Доставим точно', 'text' => 'Контролируем платёж', 'icon' => '' ) ),
		);
		$production = array(
			'defaults' => array(),
			'warnings' => array(),
			'pages' => array(
				'korea' => array(
					'layouts' => array( 'cross-guarantees' => array( 'source_layout' => 'guarantees', 'values' => $values ) ),
				),
			),
		);
		$local = array(
			'defaults' => array( 'cross-guarantees' => array() ),
			'attachments' => array(),
			'pages' => array(
				'korea' => array(
					'id' => 4309,
					'status' => 'publish',
					'builder_enabled' => true,
					'attachments' => array(),
					'rows' => array(
						array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
						array(
							'acf_fc_layout' => 'guarantees',
							'enabled' => 1,
							'variant' => 'inherit',
							'overrides_default' => array(
								'default' => array(
									'title' => 'Гарантии и ответственность',
									'description' => 'Отвечаем за результат',
									'items' => array( array( 'title' => 'Доставим точно', 'text' => 'Контролируем платёж', 'image' => '' ) ),
								),
							),
						),
						array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
					),
				),
			),
		);

		$plan = self::PLANNER::plan( $production, $local );
		$rows = $plan['pages'][0]['target_rows'];

		self::assertSame( array( 'hero', 'guarantees', 'cross-guarantees', 'faq' ), array_column( $rows, 'acf_fc_layout' ) );
		self::assertSame( array(), $rows[1]['overrides_default']['default'] );
		self::assertSame( 'Гарантии и ответственность', $rows[2]['overrides']['cross_guarantees']['title'] );
		self::assertSame( 'Доставим точно', $rows[2]['overrides']['cross_guarantees']['items'][0]['title'] );
	}

	public function test_plan_inherits_equal_values_preserves_conflicts_and_replaces_repeaters_wholesale(): void {
		$this->assertPlannerExists();
		$production = array(
			'defaults' => array(
				'cross-swift-problems' => array(
					'title' => 'Global title',
					'intro' => 'Global intro',
					'items' => array( array( 'title' => 'Global card', 'text' => 'Global text', 'icon' => '91' ) ),
				),
			),
			'pages' => array(
				'europe' => array(
					'production_id' => 2182,
					'layouts' => array(
						'cross-swift-problems' => array(
							'source_layout' => 'swift_problems',
							'values' => array(
								'title' => 'Page title',
								'intro' => 'Global intro',
								'items' => array( array( 'title' => 'Page card', 'text' => 'Page text', 'icon' => '91' ) ),
							),
						),
					),
				),
			),
			'warnings' => array(),
		);
		$local = array(
			'defaults' => array( 'cross-swift-problems' => array() ),
			'pages' => array(
				'europe' => array(
					'id' => 77,
					'status' => 'publish',
					'builder_enabled' => false,
					'rows' => array(
						array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
						array( 'acf_fc_layout' => 'cross-swift-problems', 'enabled' => 0, 'anchor' => '', 'overrides' => array( 'cross_swift_problems' => array( 'title' => 'Protected local title' ) ) ),
						array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
					),
					'attachments' => array(),
				),
			),
		);

		$plan = self::PLANNER::plan( $production, $local );

		self::assertSame( 'seed', $plan['defaults'][0]['action'] );
		self::assertSame( 'Protected local title', $plan['pages'][0]['target_rows'][1]['overrides']['cross_swift_problems']['title'] );
		self::assertArrayNotHasKey( 'intro', $plan['pages'][0]['target_rows'][1]['overrides']['cross_swift_problems'] );
		self::assertSame( 'Page card', $plan['pages'][0]['target_rows'][1]['overrides']['cross_swift_problems']['items'][0]['title'] );
		self::assertSame( '', $plan['pages'][0]['target_rows'][1]['overrides']['cross_swift_problems']['items'][0]['icon'] );
		self::assertTrue( $plan['pages'][0]['target_builder_enabled'] );
		self::assertContains( 'local_override_conflict', array_column( $plan['conflicts'], 'type' ) );
		self::assertContains( 'unresolved_attachment', array_column( $plan['warnings'], 'type' ) );
		self::assertNotSame( '', $plan['pages'][0]['fingerprint'] );
	}

	public function test_plan_is_idempotent_after_acf_materializes_empty_fields_and_scalar_types(): void {
		$this->assertPlannerExists();
		$production = array(
			'defaults' => array(),
			'warnings' => array(),
			'pages' => array(
				'china' => array(
					'production_id' => 1873,
					'layouts' => array(
						'problems' => array(
							'values' => array(
								'items' => array( array( 'title' => 'Карточка', 'text' => 'Текст', 'image' => '' ) ),
							),
						),
					),
				),
			),
		);
		$local = array(
			'defaults' => array(),
			'attachments' => array(),
			'pages' => array(
				'china' => array(
					'id' => 1873,
					'status' => 'publish',
					'builder_enabled' => true,
					'attachments' => array(),
					'rows' => array(
						array(
							'acf_fc_layout' => 'problems',
							'enabled' => '1',
							'anchor' => '',
							'overrides' => array(
								'items' => array( array( 'title' => 'Карточка', 'text' => 'Текст', 'image' => false, 'link' => null ) ),
								'problems' => array(
									'items' => array( array( 'title' => 'Карточка', 'text' => 'Текст', 'image' => false, 'link' => null ) ),
								),
							),
						),
						array( 'acf_fc_layout' => 'faq', 'enabled' => '1' ),
					),
				),
			),
		);

		$plan = self::PLANNER::plan( $production, $local );
		self::assertSame( array(), $plan['conflicts'] );
		self::assertSame( array(), $plan['pages'] );
		self::assertSame( 0, $plan['totals']['page_writes'] );
		$equivalent = new ReflectionMethod( self::PLANNER, 'equivalent' );
		self::assertFalse( $equivalent->invoke( null, array( 'title' => 'Есть данные' ), false ) );
	}

	public function test_plan_compares_page_values_with_existing_local_defaults_when_seed_is_skipped(): void {
		$this->assertPlannerExists();
		$production_values = array(
			'title' => 'Production title',
			'facts' => array( array( 'value' => '10', 'label' => 'Production fact' ) ),
		);
		$production = array(
			'defaults' => array( 'cross-key-facts' => $production_values ),
			'warnings' => array(),
			'pages' => array(
				'turkey' => array(
					'production_id' => 10,
					'layouts' => array(
						'cross-key-facts' => array( 'values' => $production_values ),
					),
				),
			),
		);
		$local = array(
			'defaults' => array(
				'cross-key-facts' => array(
					'title' => 'Existing local title',
					'facts' => array( array( 'value' => '20', 'label' => 'Local fact' ) ),
				),
			),
			'attachments' => array(),
			'pages' => array(
				'turkey' => array(
					'id' => 20,
					'status' => 'publish',
					'builder_enabled' => true,
					'attachments' => array(),
					'rows' => array(
						array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
						array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
					),
				),
			),
		);

		$plan = self::PLANNER::plan( $production, $local );

		self::assertSame( array(), $plan['defaults'] );
		self::assertSame( 1, $plan['totals']['page_writes'] );
		$override = $plan['pages'][0]['target_rows'][1]['overrides']['cross_key_facts'];
		self::assertSame( 'Production title', $override['title'] );
		self::assertSame( 'Production fact', $override['facts'][0]['label'] );
	}

	public function test_plan_resolves_page_media_before_comparing_with_effective_defaults(): void {
		$this->assertPlannerExists();
		$production_values = array(
			'items' => array( array( 'title' => 'Bank', 'text' => 'Text', 'icon' => '91' ) ),
		);
		$production = array(
			'defaults' => array( 'cross-swift-problems' => $production_values ),
			'warnings' => array(),
			'pages' => array(
				'europe' => array(
					'layouts' => array(
						'cross-swift-problems' => array( 'values' => $production_values ),
					),
				),
			),
		);
		$local = array(
			'defaults' => array(
				'cross-swift-problems' => array(
					'items' => array( array( 'title' => 'Bank', 'text' => 'Text', 'icon' => 191 ) ),
				),
			),
			'attachments' => array(),
			'pages' => array(
				'europe' => array(
					'id' => 77,
					'status' => 'publish',
					'builder_enabled' => true,
					'attachments' => array( 91 => 191 ),
					'rows' => array(
						array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
						array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
					),
				),
			),
		);

		$plan = self::PLANNER::plan( $production, $local );

		$override = $plan['pages'][0]['target_rows'][1]['overrides']['cross_swift_problems'];
		self::assertSame( array(), $override );
		self::assertSame( array(), $plan['warnings'] );
	}

	public function test_plan_rejects_missing_pages_and_empty_or_malformed_builders(): void {
		$this->assertPlannerExists();
		$production = array(
			'defaults' => array(),
			'pages' => array(
				'missing' => array( 'production_id' => 1, 'layouts' => array( 'cross-audience' => array( 'values' => array( 'title' => 'A' ) ) ) ),
				'empty'   => array( 'production_id' => 2, 'layouts' => array( 'cross-audience' => array( 'values' => array( 'title' => 'B' ) ) ) ),
				'broken'  => array( 'production_id' => 3, 'layouts' => array( 'cross-audience' => array( 'values' => array( 'title' => 'C' ) ) ) ),
			),
			'warnings' => array(),
		);
		$local = array(
			'defaults' => array(),
			'pages' => array(
				'empty'  => array( 'id' => 20, 'status' => 'publish', 'builder_enabled' => false, 'rows' => array() ),
				'broken' => array( 'id' => 30, 'status' => 'publish', 'builder_enabled' => true, 'rows' => array( 'not-a-row' ) ),
			),
		);

		$plan = self::PLANNER::plan( $production, $local );
		self::assertSame( array( 'missing_local_page', 'unsafe_builder', 'unsafe_builder' ), array_column( $plan['conflicts'], 'type' ) );
		self::assertSame( 0, $plan['totals']['page_writes'] );
	}

	private function assertPlannerExists(): void {
		self::assertTrue( class_exists( self::PLANNER ), 'CrossBlockMigrationPlanner has not been implemented yet.' );
	}
}
