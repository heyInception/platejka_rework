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
foreach ( get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => 50, 'fields' => 'ids' ) ) as $candidate ) {
	if ( wp_get_attachment_metadata( $candidate ) && wp_get_attachment_image_srcset( $candidate, 'large' ) ) {
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
	'table/table.php'              => array( 'old' => 'Почему работа с нами', 'hook' => '<table aria-label=' ),
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

$section_instance = 'section-1873-1';
$section_anchor   = 'china-hero';
$section_data     = array(
	'background_image' => $image_id,
	'blur_image'       => $image_id,
	'badge'            => 'Fixture hero badge',
	'title'            => array( 'primary' => 'Fixture China', 'secondary' => 'Fixture legal entities' ),
	'subtitle'         => 'Fixture hero subtitle',
	'items'            => array( array( 'title' => '3%', 'text' => 'Fixture commission' ) ),
	'info'             => 'Fixture hero info',
	'experience'       => array( 'value' => '5 лет', 'label' => 'Fixture market', 'left_image' => $image_id, 'right_image' => $image_id ),
	'registry'         => array( 'title' => 'Fixture registry', 'image' => $image_id, 'link' => array( 'url' => '', 'title' => 'Fixture check', 'target' => '' ) ),
	'association'      => array( 'title' => "Fixture association\nline", 'image' => $image_id, 'link' => array( 'url' => '', 'title' => 'Fixture documents', 'target' => '' ) ),
);
ob_start();
include get_theme_file_path( 'sections/hero/hero.php' );
$internal_hero = (string) ob_get_clean();
$assert( str_contains( $internal_hero, 'id="china-hero"' ) && str_contains( $internal_hero, 'Fixture China' ) && str_contains( $internal_hero, 'Fixture hero subtitle' ), 'Internal hero renders resolved fixture content and its public anchor.' );
$assert( str_contains( $internal_hero, 'data-hero-panel' ) && str_contains( $internal_hero, 'data-hero-calculator' ) && str_contains( $internal_hero, 'hero__trust-card_registry' ), 'Internal hero preserves layout and behavior hooks.' );
$assert( str_contains( $internal_hero, 'id="section-1873-1-hero-amount"' ) && str_contains( $internal_hero, 'aria-labelledby="section-1873-1-contact-dialog-title"' ), 'Internal hero emits instance-safe form and dialog IDs.' );
$assert( str_contains( $internal_hero, 'loading="eager"' ) && str_contains( $internal_hero, 'fetchpriority="high"' ) && str_contains( $internal_hero, 'srcset="' ), 'Internal hero renders a responsive high-priority attachment.' );
$assert( ! str_contains( $internal_hero, 'href="#"' ) && ! str_contains( $internal_hero, '12.3' ) && ! str_contains( $internal_hero, 'Форма будет подключена' ), 'Internal hero removes demo links, rates, and fake dialog copy.' );

$section_instance = 'section-1873-6';
$section_anchor   = 'payment-protection';
$section_data     = array(
	'eyebrow'     => 'Fixture protection eyebrow',
	'title'       => 'Fixture protection title',
	'description' => 'Fixture protection description',
	'link'        => array( 'url' => '#documents', 'title' => 'Fixture protection link', 'target' => '' ),
	'items'       => array( array( 'image' => 999999999, 'title' => 'Fixture protection card', 'text' => 'Fixture protection text', 'detail' => 'Fixture detail' ) ),
);
ob_start();
include get_theme_file_path( 'sections/protection/protection.php' );
$internal_protection = (string) ob_get_clean();
$assert( str_contains( $internal_protection, 'id="payment-protection"' ) && str_contains( $internal_protection, 'Fixture protection title' ) && str_contains( $internal_protection, 'Fixture protection card' ), 'Protection renders resolved fixture content.' );
$assert( str_contains( $internal_protection, 'class="protection__cards"' ) && ! str_contains( $internal_protection, 'src=""' ) && ! str_contains( $internal_protection, 'item-1.png' ), 'Protection preserves its card layout and fails closed for missing media.' );

$section_instance = 'section-1873-7';
$section_anchor   = 'client-reviews';
$section_data     = array(
	'eyebrow'     => 'Fixture review eyebrow',
	'title'       => "Fixture reviews\nsecond line",
	'description' => '<p>Fixture review lead</p>',
	'labels'      => array(
		array( 'key' => 'tabs_label', 'label' => 'Fixture tabs' ),
		array( 'key' => 'video_tab', 'label' => 'Fixture video tab' ),
		array( 'key' => 'text_tab', 'label' => 'Fixture text tab' ),
		array( 'key' => 'video_region', 'label' => 'Fixture video region' ),
		array( 'key' => 'text_region', 'label' => 'Fixture text region' ),
		array( 'key' => 'watch', 'label' => 'Fixture watch' ),
		array( 'key' => 'prev_video', 'label' => 'Fixture previous video' ),
		array( 'key' => 'next_video', 'label' => 'Fixture next video' ),
		array( 'key' => 'prev_text', 'label' => 'Fixture previous text' ),
		array( 'key' => 'next_text', 'label' => 'Fixture next text' ),
		array( 'key' => 'close', 'label' => 'Fixture close' ),
		array( 'key' => 'loading', 'label' => 'Fixture loading' ),
		array( 'key' => 'empty', 'label' => 'Fixture empty' ),
		array( 'key' => 'error', 'label' => 'Fixture error' ),
	),
	'video_items' => array( array( 'title' => 'Fixture video', 'text' => 'Fixture role', 'image' => $image_id, 'file' => 0 ) ),
	'items'       => array( array( 'title' => 'Fixture author', 'text' => '<p>Fixture review text</p>', 'value' => '★★★★★', 'caption' => 'Fixture source', 'date' => '20260828', 'image' => $image_id ) ),
);
ob_start();
include get_theme_file_path( 'sections/review/review.php' );
$internal_review = (string) ob_get_clean();
$assert( str_contains( $internal_review, 'id="client-reviews"' ) && str_contains( $internal_review, 'Fixture reviews<br>second line' ) && str_contains( $internal_review, 'Fixture video' ) && str_contains( $internal_review, 'Fixture author' ), 'Internal review renders canonical review-main-shaped data.' );
$assert( str_contains( $internal_review, 'id="section-1873-7-review-video-tab"' ) && str_contains( $internal_review, 'data-review-dialog' ) && str_contains( $internal_review, 'loading="lazy"' ), 'Internal review preserves hooks, unique IDs, and lazy responsive media.' );
$assert( ! str_contains( $internal_review, 'theme://' ) && ! str_contains( $internal_review, 'Елена С.' ), 'Internal review contains no seed path or static review fallback.' );

$behavior_hooks = array(
	'about/about.php'             => array( 'data-about-dialog-open', 'data-about-dialog', 'data-about-dialog-close' ),
	'compliance/compliance.php'   => array( 'data-compliance-dialog-open', 'data-compliance-dialog', 'data-compliance-dialog-close' ),
	'review-main/review-main.php' => array( 'data-review-video-panel', 'data-review-text-panel', 'data-review-dialog', 'data-review-video' ),
	'problems/problems.php'       => array( 'data-problems-dialog-open', 'data-problems-dialog', 'data-problems-dialog-close' ),
);
foreach ( $behavior_hooks as $relative => $hooks ) {
	$source = (string) file_get_contents( get_theme_file_path( 'sections/' . $relative ) );
	foreach ( $hooks as $hook ) {
		$assert( str_contains( $source, $hook ), $relative . ' preserves behavior hook ' . $hook . '.' );
	}
}

$seo_source = (string) file_get_contents( get_theme_file_path( 'sections/seo/seo.php' ) );
$assert( str_contains( $seo_source, "<?php if (is_page(2054)) : ?>" ) && str_contains( $seo_source, "<?php the_field('zagolovok_services'); ?>" ), 'SEO keeps the exact page-2054 title branch.' );
$assert( str_contains( $seo_source, '<?php the_title() ?>' ) && str_contains( $seo_source, '<?php the_content(); ?>' ), 'SEO keeps the existing title fallback and the_content().' );
$assert( ! str_contains( $seo_source, 'Оставьте заявку</h3>' ), 'SEO cards no longer embed their former editorial fallback.' );

$calculator_source = (string) file_get_contents( get_theme_file_path( 'sections/calculator/calculator.php' ) );
$assert( str_contains( $calculator_source, 'data-calculator-select="country"' ) && str_contains( $calculator_source, 'data-search-enabled="true"' ), 'Calculator preserves searchable Select2 country hooks.' );
$assert( str_contains( $calculator_source, "'JP' => 'Япония'" ) && str_contains( $calculator_source, "'ZA' => 'Южно-Африканская Республика'" ), 'Calculator preserves the complete international destination list.' );

WP_CLI::log( sprintf( 'Section templates: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Section template content integration passed.' );
