<?php
/**
 * Runtime ACF schema for approved legacy cross-blocks.
 *
 * @package platejka_rework
 */

defined( 'ABSPATH' ) || exit;

/** @param array<string,mixed> $extra @return array<string,mixed> */
function platejka_cross_block_field( string $scope, string $name, string $label, string $type, array $extra = array() ): array {
	$field = array(
		'key'               => 'field_platejka_cb_' . $scope . '_' . $name,
		'label'             => $label,
		'name'              => $name,
		'_name'             => $name,
		'aria-label'        => '',
		'type'              => $type,
		'instructions'      => '',
		'required'          => 0,
		'conditional_logic' => 0,
		'wrapper'           => array( 'width' => '', 'class' => '', 'id' => '' ),
	);

	return array_replace_recursive( $field, $extra );
}

/** @param array<int,array<string,mixed>> $sub_fields @param array<string,mixed> $extra @return array<string,mixed> */
function platejka_cross_block_repeater( string $scope, string $name, string $label, array $sub_fields, array $extra = array() ): array {
	return platejka_cross_block_field(
		$scope,
		$name,
		$label,
		'repeater',
		array_replace_recursive(
			array(
				'layout'        => 'table',
				'min'           => 0,
				'max'           => 0,
				'collapsed'     => '',
				'button_label'  => 'Добавить',
				'rows_per_page' => 20,
				'sub_fields'    => $sub_fields,
			),
			$extra
		)
	);
}

/** @return array<int,array<string,mixed>> */
function platejka_cross_block_card_fields( string $scope ): array {
	return array(
		platejka_cross_block_field( $scope, 'show_block', 'Показывать блок', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
		platejka_cross_block_field( $scope, 'title', 'Заголовок', 'text' ),
		platejka_cross_block_field( $scope, 'intro', 'Вводный текст', 'wysiwyg', array( 'tabs' => 'all', 'toolbar' => 'basic', 'media_upload' => 0, 'delay' => 0 ) ),
		platejka_cross_block_repeater(
			$scope,
			'items',
			'Карточки',
			array(
				platejka_cross_block_field( $scope . '_item', 'icon', 'Иконка', 'image', array( 'return_format' => 'array', 'preview_size' => 'thumbnail', 'library' => 'all' ) ),
				platejka_cross_block_field( $scope . '_item', 'title', 'Заголовок', 'text' ),
				platejka_cross_block_field( $scope . '_item', 'text', 'Текст', 'wysiwyg', array( 'tabs' => 'all', 'toolbar' => 'basic', 'media_upload' => 0, 'delay' => 0 ) ),
			),
			array( 'button_label' => 'Добавить карточку' )
		),
		platejka_cross_block_field( $scope, 'cta', 'Кнопка', 'link', array( 'return_format' => 'array' ) ),
	);
}

/** @param array<int,array<string,mixed>> $sub_fields @return array<string,mixed> */
function platejka_cross_block_layout( string $name, string $label, array $sub_fields ): array {
	return array(
		'key'        => 'layout_platejka_cb_' . $name,
		'label'      => $label,
		'name'       => $name,
		'display'    => 'block',
		'sub_fields' => $sub_fields,
		'min'        => '',
		'max'        => '',
	);
}

/** @return array<int,array<string,mixed>> */
function platejka_cross_block_destination_fields(): array {
	return array(
		platejka_cross_block_field( 'destinations', 'destination_title', 'Заголовок блока', 'text' ),
		platejka_cross_block_field( 'destinations', 'destination_subtitle', 'Подзаголовок блока', 'textarea', array( 'rows' => 3, 'new_lines' => 'wpautop' ) ),
		platejka_cross_block_repeater(
			'destinations',
			'destination_items',
			'Направления',
			array(
				platejka_cross_block_field( 'destinations_item', 'image', 'Изображение / флаг', 'image', array( 'return_format' => 'array', 'preview_size' => 'thumbnail', 'library' => 'all' ) ),
				platejka_cross_block_field( 'destinations_item', 'name', 'Название', 'text', array( 'required' => 1 ) ),
				platejka_cross_block_field( 'destinations_item', 'link', 'Ссылка', 'link', array( 'return_format' => 'array' ) ),
			),
			array( 'button_label' => 'Добавить направление' )
		),
	);
}

/** @return array<int,array<string,mixed>> */
function platejka_cross_block_layout_definitions(): array {
	$metrics = platejka_cross_block_repeater(
		'company_metrics',
		'metrics',
		'Показатели',
		array(
			platejka_cross_block_field( 'company_metrics_item', 'prefix', 'Префикс', 'text' ),
			platejka_cross_block_field( 'company_metrics_item', 'value', 'Числовое значение', 'number', array( 'required' => 1, 'step' => 'any' ) ),
			platejka_cross_block_field( 'company_metrics_item', 'suffix', 'Суффикс', 'text' ),
			platejka_cross_block_field( 'company_metrics_item', 'label', 'Подпись', 'text', array( 'required' => 1 ) ),
		),
		array( 'min' => 3, 'max' => 5, 'button_label' => 'Добавить показатель' )
	);

	$reviews = platejka_cross_block_repeater(
		'client_reviews',
		'reviews',
		'Отзывы',
		array(
			platejka_cross_block_field( 'client_reviews_item', 'photo', 'Фото', 'image', array( 'return_format' => 'array', 'preview_size' => 'thumbnail', 'library' => 'all' ) ),
			platejka_cross_block_field( 'client_reviews_item', 'name', 'Имя', 'text', array( 'required' => 1 ) ),
			platejka_cross_block_field( 'client_reviews_item', 'role', 'Должность / компания', 'text' ),
			platejka_cross_block_field( 'client_reviews_item', 'text', 'Текст отзыва', 'textarea', array( 'required' => 1, 'rows' => 5, 'new_lines' => 'wpautop' ) ),
			platejka_cross_block_field( 'client_reviews_item', 'rating', 'Оценка', 'number', array( 'min' => 1, 'max' => 5, 'step' => 1, 'default_value' => 5 ) ),
			platejka_cross_block_field( 'client_reviews_item', 'date', 'Дата', 'date_picker', array( 'display_format' => 'd.m.Y', 'return_format' => 'd.m.Y', 'first_day' => 1 ) ),
			platejka_cross_block_field( 'client_reviews_item', 'source', 'Ссылка на источник', 'url' ),
		),
		array( 'button_label' => 'Добавить отзыв' )
	);

	$platforms = platejka_cross_block_repeater(
		'external_ratings',
		'platforms',
		'Площадки',
		array(
			platejka_cross_block_field( 'external_ratings_item', 'logo', 'Логотип', 'image', array( 'return_format' => 'array', 'preview_size' => 'thumbnail', 'library' => 'all' ) ),
			platejka_cross_block_field( 'external_ratings_item', 'name', 'Название', 'text', array( 'required' => 1 ) ),
			platejka_cross_block_field( 'external_ratings_item', 'rating', 'Рейтинг', 'number', array( 'min' => 0, 'max' => 5, 'step' => 0.1 ) ),
			platejka_cross_block_field( 'external_ratings_item', 'reviews_count', 'Количество отзывов', 'number', array( 'min' => 0, 'step' => 1 ) ),
			platejka_cross_block_field( 'external_ratings_item', 'link', 'Ссылка', 'url', array( 'required' => 1 ) ),
		),
		array( 'button_label' => 'Добавить площадку' )
	);

	$documents = platejka_cross_block_repeater(
		'documents',
		'documents',
		'Документы',
		array(
			platejka_cross_block_field( 'documents_item', 'title', 'Название', 'text', array( 'required' => 1 ) ),
			platejka_cross_block_field( 'documents_item', 'description', 'Описание', 'textarea', array( 'rows' => 3, 'new_lines' => 'wpautop' ) ),
			platejka_cross_block_field( 'documents_item', 'file', 'PDF-файл', 'file', array( 'required' => 1, 'return_format' => 'array', 'library' => 'all', 'mime_types' => 'pdf' ) ),
		),
		array( 'button_label' => 'Добавить документ' )
	);

	$facts = platejka_cross_block_repeater(
		'key_facts',
		'facts',
		'Условия',
		array(
			platejka_cross_block_field( 'key_facts_item', 'label', 'Название', 'text', array( 'required' => 1 ) ),
			platejka_cross_block_field( 'key_facts_item', 'value', 'Значение', 'text', array( 'required' => 1 ) ),
		),
		array( 'min' => 4, 'max' => 4, 'button_label' => 'Добавить условие' )
	);

	$guarantees = platejka_cross_block_card_fields( 'guarantees' );
	array_splice(
		$guarantees,
		1,
		0,
		array(
			platejka_cross_block_field(
				'guarantees',
				'style',
				'Вариант оформления',
				'select',
				array(
					'choices'       => array( 'cards' => 'Карточки', 'shield' => 'Акцентный блок со щитом' ),
					'default_value' => 'cards',
					'return_format' => 'value',
					'allow_null'    => 0,
					'multiple'      => 0,
					'ui'            => 0,
					'ajax'          => 0,
				),
			),
		)
	);

	return array(
		platejka_cross_block_layout(
			'company_metrics',
			'Показатели компании',
			array(
				platejka_cross_block_field( 'company_metrics', 'show_block', 'Показывать блок', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				platejka_cross_block_field( 'company_metrics', 'title', 'Заголовок', 'text' ),
				$metrics,
				platejka_cross_block_field( 'company_metrics', 'cta', 'Кнопка', 'link', array( 'return_format' => 'array' ) ),
			)
		),
		platejka_cross_block_layout( 'problems_solved', 'Какие проблемы мы решаем', platejka_cross_block_card_fields( 'problems_solved' ) ),
		platejka_cross_block_layout(
			'client_reviews',
			'Отзывы клиентов',
			array(
				platejka_cross_block_field( 'client_reviews', 'show_block', 'Показывать блок', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				platejka_cross_block_field( 'client_reviews', 'title', 'Заголовок', 'text', array( 'default_value' => 'Отзывы клиентов' ) ),
				$reviews,
				platejka_cross_block_field( 'client_reviews', 'cta', 'Кнопка', 'link', array( 'return_format' => 'array' ) ),
			)
		),
		platejka_cross_block_layout(
			'external_ratings',
			'Рейтинг на внешних площадках',
			array(
				platejka_cross_block_field( 'external_ratings', 'show_block', 'Показывать блок', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				platejka_cross_block_field( 'external_ratings', 'title', 'Заголовок', 'text', array( 'default_value' => 'Отзывы о нашем сервисе' ) ),
				platejka_cross_block_field( 'external_ratings', 'overall_rating', 'Общий рейтинг', 'number', array( 'min' => 0, 'max' => 5, 'step' => 0.1 ) ),
				platejka_cross_block_field( 'external_ratings', 'reviews_count', 'Общее количество отзывов', 'number', array( 'min' => 0, 'step' => 1 ) ),
				$platforms,
				platejka_cross_block_field( 'external_ratings', 'cta', 'Кнопка', 'link', array( 'return_format' => 'array' ) ),
			)
		),
		platejka_cross_block_layout(
			'documents',
			'Скачиваемые документы',
			array(
				platejka_cross_block_field( 'documents', 'show_block', 'Показывать блок', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				platejka_cross_block_field( 'documents', 'title', 'Заголовок', 'text', array( 'default_value' => 'Документы' ) ),
				platejka_cross_block_field( 'documents', 'intro', 'Вводный текст', 'wysiwyg', array( 'tabs' => 'all', 'toolbar' => 'basic', 'media_upload' => 0, 'delay' => 0 ) ),
				$documents,
				platejka_cross_block_field( 'documents', 'cta', 'Кнопка', 'link', array( 'return_format' => 'array' ) ),
			)
		),
		platejka_cross_block_layout(
			'key_facts',
			'Конкретные цифры и условия',
			array(
				platejka_cross_block_field( 'key_facts', 'show_block', 'Показывать блок', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				platejka_cross_block_field( 'key_facts', 'title', 'Заголовок', 'text' ),
				$facts,
				platejka_cross_block_field( 'key_facts', 'cta', 'Кнопка', 'link', array( 'return_format' => 'array' ) ),
			)
		),
		platejka_cross_block_layout( 'transfer_goals', 'Частые цели переводов', platejka_cross_block_card_fields( 'transfer_goals' ) ),
		platejka_cross_block_layout( 'swift_problems', 'Почему прямой SWIFT не работает', platejka_cross_block_card_fields( 'swift_problems' ) ),
		platejka_cross_block_layout( 'guarantees', 'Гарантии / ответственность', $guarantees ),
		platejka_cross_block_layout( 'audience', 'Кому подходит', platejka_cross_block_card_fields( 'audience' ) ),
		platejka_cross_block_layout(
			'data_table',
			'Таблица',
			array(
				platejka_cross_block_field( 'data_table', 'show_block', 'Показывать блок', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				platejka_cross_block_field( 'data_table', 'title', 'Заголовок', 'text' ),
				platejka_cross_block_field( 'data_table', 'intro', 'Вводный текст', 'wysiwyg', array( 'tabs' => 'all', 'toolbar' => 'basic', 'media_upload' => 0, 'delay' => 0 ) ),
				platejka_cross_block_field( 'data_table', 'table_data', 'Таблица', 'table', array( 'use_header' => 0, 'use_caption' => 1 ) ),
			)
		),
	);
}

/** @param array<string,mixed> $field @return array<string,mixed> */
function platejka_extend_cross_blocks_field( array $field ): array {
	$approved = platejka_cross_block_map();
	$layouts  = array();

	foreach ( is_array( $field['layouts'] ?? null ) ? $field['layouts'] : array() as $layout ) {
		if ( ! is_array( $layout ) || ! isset( $approved[ $layout['name'] ?? '' ] ) ) {
			continue;
		}

		$name = (string) $layout['name'];
		if ( 'destinations' === $name ) {
			$layout['sub_fields'] = is_array( $layout['sub_fields'] ?? null ) ? $layout['sub_fields'] : array();
			$names                = array_column( $layout['sub_fields'], 'name' );
			foreach ( platejka_cross_block_destination_fields() as $sub_field ) {
				if ( ! in_array( $sub_field['name'], $names, true ) ) {
					$layout['sub_fields'][] = $sub_field;
				}
			}
		}
		$layouts[ $name ] = $layout;
	}

	foreach ( platejka_cross_block_layout_definitions() as $layout ) {
		$layouts[ $layout['name'] ] = $layout;
	}

	$ordered = array();
	foreach ( array_keys( $approved ) as $name ) {
		if ( ! isset( $layouts[ $name ] ) ) {
			continue;
		}
		$layout          = $layouts[ $name ];
		$label           = preg_replace( '/^Кросс-блок\s+—\s+/u', '', (string) ( $layout['label'] ?? $name ) );
		$layout['label'] = 'Кросс-блок — ' . $label;
		$ordered[ $layout['key'] ] = $layout;
	}

	$field['layouts'] = $ordered;
	return $field;
}
add_filter( 'acf/load_field/key=field_67924d166072f', 'platejka_extend_cross_blocks_field' );

/**
 * Builder-only controls for a cross-block whose content stays in the legacy field.
 *
 * @return array<string,mixed>
 */
function platejka_cross_block_builder_layout( string $slug, string $label ): array {
	$key_scope = str_replace( '-', '_', $slug );

	return array(
		'key'        => 'layout_platejka_' . $key_scope . '_v1',
		'name'       => $slug,
		'label'      => 'Кросс-блок — ' . $label,
		'display'    => 'block',
		'sub_fields' => array(
			array(
				'key'           => 'field_platejka_row_' . $key_scope . '_enabled_v1',
				'label'         => 'Показывать секцию',
				'name'          => 'enabled',
				'type'          => 'true_false',
				'default_value' => 0,
				'ui'            => 1,
			),
			array(
				'key'         => 'field_platejka_row_' . $key_scope . '_anchor_v1',
				'label'       => 'Якорь секции',
				'name'        => 'anchor',
				'type'        => 'text',
				'placeholder' => 'bez-reshetki',
			),
		),
		'min'        => '',
		'max'        => '',
	);
}

/** @return array<int,array<string,mixed>> */
function platejka_cross_block_builder_layouts(): array {
	return array(
		platejka_cross_block_builder_layout( 'cross-exchange', 'Обмен валют' ),
		platejka_cross_block_builder_layout( 'cross-company-metrics', 'Показатели компании' ),
		platejka_cross_block_builder_layout( 'cross-external-ratings', 'Рейтинги на внешних площадках' ),
		platejka_cross_block_builder_layout( 'cross-key-facts', 'Конкретные цифры и условия' ),
		platejka_cross_block_builder_layout( 'cross-transfer-goals', 'Частые цели переводов' ),
		platejka_cross_block_builder_layout( 'cross-swift-problems', 'Почему прямой SWIFT не работает' ),
		platejka_cross_block_builder_layout( 'cross-audience', 'Кому подходит' ),
	);
}

/** @param array<string,mixed> $field @return array<string,mixed> */
function platejka_extend_section_builder_with_cross_blocks( array $field ): array {
	$field['layouts'] = is_array( $field['layouts'] ?? null ) ? $field['layouts'] : array();
	$existing         = array();
	foreach ( $field['layouts'] as $layout ) {
		if ( is_array( $layout ) && isset( $layout['name'] ) ) {
			$existing[] = (string) $layout['name'];
		}
	}

	foreach ( platejka_cross_block_builder_layouts() as $layout ) {
		if ( ! in_array( $layout['name'], $existing, true ) ) {
			$field['layouts'][ $layout['key'] ] = $layout;
		}
	}

	return $field;
}
add_filter( 'acf/load_field/key=field_platejka_sections_v1', 'platejka_extend_section_builder_with_cross_blocks' );

/**
 * Keep legacy definitions available to ACF value formatting without showing
 * a second flexible-content editor alongside the page builder.
 *
 * @param array<string,mixed> $field ACF field.
 * @return false
 */
function platejka_hide_legacy_cross_blocks_field( array $field ) {
	return false;
}
add_filter( 'acf/prepare_field/key=field_67924d166072f', 'platejka_hide_legacy_cross_blocks_field' );
