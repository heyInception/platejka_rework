<?php
/** Rendering contract for the foundational ACF-backed section templates. */

defined( 'ABSPATH' ) || exit;

$checks   = 0;
$failures = array();
$assert   = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};

$image_id = 0;
foreach ( get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => 20, 'fields' => 'ids' ) ) as $candidate ) {
	if ( wp_get_attachment_metadata( $candidate ) ) {
		$image_id = (int) $candidate;
		break;
	}
}
$assert( $image_id > 0, 'Template fixtures have a responsive image attachment.' );

$templates = array(
	'hero/hero-main.php'           => array( 'old' => 'Международные платежи', 'hook' => 'data-hero' ),
	'about/about.php'              => array( 'old' => 'Официальный платёжный агент', 'hook' => 'data-about' ),
	'compliance/compliance.php'    => array( 'old' => 'Финансовый комплаенс', 'hook' => 'data-compliance' ),
	'review-main/review-main.php'  => array( 'old' => 'Клиенты остаются', 'hook' => 'data-review' ),
	'work/work.php'                => array( 'old' => 'Какие документы нужны', 'hook' => 'class="work"' ),
	'with-us/with-us.php'          => array( 'old' => 'Особенности работы с нами', 'hook' => 'data-horizontal-slider' ),
	'destinations/destinations.php'=> array( 'old' => 'Доступные направления', 'hook' => 'data-destinations' ),
	'problems/problems.php'        => array( 'old' => 'Какие <span>проблемы', 'hook' => 'data-problems-slider' ),
	'serves/serves.php'            => array( 'old' => 'Для кого работает Платёжка', 'hook' => 'data-horizontal-slider' ),
	'cases/cases.php'              => array( 'old' => 'Более 2 000 отзывов', 'hook' => 'data-horizontal-slider' ),
	'table/table.php'              => array( 'old' => 'Почему работа с нами', 'hook' => '<table>' ),
	'shipments/shipments.php'      => array( 'old' => 'Предоставьте платежи за рубеж', 'hook' => 'data-shipments' ),
	'guarantees/guarantees.php'    => array( 'old' => 'Гарантии безопасности транзакций', 'hook' => 'data-guarantees' ),
	'documents/documents.php'      => array( 'old' => 'Документы для начала работы', 'hook' => 'data-documents-slider' ),
	'calculator/calculator.php'    => array( 'old' => 'Рассчитайте стоимость международного перевода', 'hook' => 'data-transfer-calculator' ),
	'faq/faq.php'                  => array( 'old' => 'Ответы на частые вопросы', 'hook' => 'data-faq' ),
	'call/call.php'                => array( 'old' => 'Оставьте заявку —', 'hook' => 'data-call' ),
);

foreach ( $templates as $relative => $expectation ) {
	$section_data = array(
		'eyebrow'    => 'Fixture eyebrow',
		'title'      => 'Fixture <title>',
		'title_accent' => 'Fixture accent',
		'description'=> '<p>Fixture body<script>alert(1)</script></p>',
		'image'      => $image_id,
		'primary_link' => array( 'url' => '#fixture', 'title' => 'Fixture action', 'target' => '' ),
		'items'      => array(
			array(
				'title'   => 'Fixture item',
				'text'    => '<p>Fixture item body</p>',
				'value'   => 'Fixture value',
				'caption' => 'Fixture caption',
				'image'   => $image_id,
				'link'    => array( 'url' => '#fixture-item', 'title' => 'Fixture item action', 'target' => '' ),
			),
		),
		'secondary_items' => array(
			array( 'title' => 'Fixture secondary', 'text' => '<p>Fixture secondary body</p>', 'value' => '5', 'caption' => 'Fixture secondary caption', 'image' => $image_id ),
		),
	);
	$section_instance = 'section-24-7';
	$section_anchor   = 'fixture-anchor';
	$mode             = 'main';
	ob_start();
	include get_theme_file_path( 'sections/' . $relative );
	$html = (string) ob_get_clean();

	$assert( str_contains( $html, 'id="fixture-anchor"' ), $relative . ' applies the public section anchor.' );
	$assert( str_contains( $html, 'Fixture &lt;title&gt;' ), $relative . ' escapes the resolved title.' );
	$assert( str_contains( $html, 'Fixture body' ) && ! str_contains( $html, '<script>' ), $relative . ' renders sanitized resolved WYSIWYG content.' );
	$assert( 1 === substr_count( $html, 'Fixture item body' ), $relative . ' renders the replacement collection exactly once.' );
	$assert( ! str_contains( $html, $expectation['old'] ), $relative . ' contains no former editorial headline fallback.' );
	$assert( str_contains( $html, $expectation['hook'] ), $relative . ' retains its stable JavaScript/DOM hook.' );
}

$seo_source = (string) file_get_contents( get_theme_file_path( 'sections/seo/seo.php' ) );
$assert( str_contains( $seo_source, "<?php if (is_page(2054)) : ?>" ) && str_contains( $seo_source, "<?php the_field('zagolovok_services'); ?>" ), 'SEO keeps the exact page-2054 title branch.' );
$assert( str_contains( $seo_source, '<?php the_title() ?>' ) && str_contains( $seo_source, '<?php the_content(); ?>' ), 'SEO keeps the existing title fallback and the_content().' );
$assert( ! str_contains( $seo_source, 'Оставьте заявку</h3>' ), 'SEO cards no longer embed their former editorial fallback.' );

WP_CLI::log( sprintf( 'Section templates: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Section template content integration passed.' );
