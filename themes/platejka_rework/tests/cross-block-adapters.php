<?php
/** Cross-block adapter regression checks. */

$integration = dirname( __DIR__ ) . '/inc/cross-blocks.php';
if ( is_file( $integration ) ) {
	require_once $integration;
}

function platejka_cross_adapter_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

platejka_cross_adapter_assert( function_exists( 'platejka_adapt_cross_block_data' ), 'Cross-block adapters are missing.' );

$destinations = platejka_adapt_cross_block_data(
	'destinations',
	array(
		'destination_title'    => 'Доступные направления',
		'destination_subtitle' => '<p>Работаем по всему миру</p>',
		'destination_items'    => array(
			array( 'image' => array( 'ID' => 41 ), 'name' => 'Китай', 'link' => array( 'url' => '/china/', 'title' => 'Китай' ) ),
		),
	)
);
platejka_cross_adapter_assert( 'Доступные направления' === $destinations['title'], 'Destinations title was not mapped.' );
platejka_cross_adapter_assert( '<p>Работаем по всему миру</p>' === $destinations['description'], 'Destinations subtitle was not mapped.' );
platejka_cross_adapter_assert( 41 === $destinations['items'][0]['image'] && 'Китай' === $destinations['items'][0]['title'], 'Destination items were not mapped.' );

$problems = platejka_adapt_cross_block_data(
	'problems_solved',
	array(
		'title' => 'Решаем задачи',
		'intro' => 'Без лишней бюрократии',
		'items' => array(
			array( 'icon' => array( 'id' => 51 ), 'title' => 'Оплата', 'text' => '<p>В срок</p>' ),
			array( 'title' => '', 'text' => '' ),
		),
	)
);
platejka_cross_adapter_assert( 'Без лишней бюрократии' === $problems['description'], 'Problems intro was not mapped.' );
platejka_cross_adapter_assert( 1 === count( $problems['items'] ) && 51 === $problems['items'][0]['image'], 'Problem cards were not normalized.' );

$documents = platejka_adapt_cross_block_data(
	'documents',
	array(
		'title' => 'Документы',
		'intro' => 'Для проверки',
		'documents' => array(
			array( 'title' => 'Лицензия', 'description' => 'PDF документ', 'file' => array( 'ID' => 61, 'url' => 'https://example.test/license.pdf', 'filesize' => 2048 ) ),
			array( 'title' => 'Пустой' ),
		),
	)
);
platejka_cross_adapter_assert( 1 === count( $documents['items'] ), 'Documents without files must be omitted.' );
platejka_cross_adapter_assert( 61 === $documents['items'][0]['file'] && 'PDF документ' === $documents['items'][0]['text'], 'Document file/content was not mapped.' );

$reviews = platejka_adapt_cross_block_data(
	'client_reviews',
	array(
		'title' => 'Отзывы клиентов',
		'reviews' => array(
			array( 'photo' => array( 'ID' => 71 ), 'name' => 'Анна', 'role' => 'ООО Ромашка', 'text' => 'Первый', 'rating' => 5, 'date' => '10.10.2026' ),
			array( 'name' => 'Иван', 'text' => 'Второй', 'rating' => 4 ),
		),
	)
);
platejka_cross_adapter_assert( array( 'Анна', 'Иван' ) === array_column( $reviews['items'], 'title' ), 'Review order/content changed.' );
platejka_cross_adapter_assert( '★★★★★' === $reviews['items'][0]['value'] && 71 === $reviews['items'][0]['image'], 'Review rating/photo was not mapped.' );

$guarantees = platejka_adapt_cross_block_data(
	'guarantees',
	array( 'title' => 'Гарантии', 'intro' => 'Ответственность', 'items' => array( array( 'icon' => 81, 'title' => 'Договор', 'text' => 'Закрепляем условия' ) ) )
);
platejka_cross_adapter_assert( 'Ответственность' === $guarantees['description'] && 81 === $guarantees['items'][0]['image'], 'Guarantees were not mapped.' );

$table = platejka_adapt_cross_block_data(
	'data_table',
	array(
		'title' => 'Сравнение',
		'intro' => 'Условия',
		'table_data' => array(
			'body' => array(
				array( array( 'c' => 'Срок' ), array( 'c' => '1 день' ), array( 'c' => '3 дня' ), array( 'c' => '5 дней' ) ),
				array( array( 'c' => 'Комиссия' ), array( 'c' => '1%' ), array( 'c' => '2%' ), array( 'c' => '3%' ) ),
			),
		),
	)
);
platejka_cross_adapter_assert( array( 'Срок', 'Комиссия' ) === array_column( $table['items'], 'title' ), 'Table row order/content changed.' );
platejka_cross_adapter_assert( '3 дня' === $table['items'][0]['partner_value'] && '5 дней' === $table['items'][0]['bank_value'], 'Table columns were not mapped.' );

$defaults = array( 'title' => 'Заголовок по умолчанию', 'description' => 'Описание', 'labels' => array( array( 'key' => 'submit', 'label' => 'Отправить' ) ) );
$empty    = platejka_adapt_cross_block_data( 'problems_solved', array( 'title' => '', 'intro' => '', 'items' => array() ) );
$merged   = platejka_merge_cross_block_data( $empty, $defaults );
platejka_cross_adapter_assert( $defaults === $merged, 'Empty cross-block values must not replace current defaults or labels.' );

foreach ( array( 'destinations', 'problems_solved', 'client_reviews', 'documents', 'guarantees', 'data_table', 'unknown' ) as $layout ) {
	platejka_cross_adapter_assert( is_array( platejka_adapt_cross_block_data( $layout, array( 'items' => 'invalid', 'table_data' => 'invalid' ) ) ), 'Malformed rows must safely return arrays.' );
}

WP_CLI::success( 'Cross-block adapter checks passed.' );
