<?php
/** Process section. */

$title_raw = is_scalar( $section_data['title'] ?? null ) ? (string) $section_data['title'] : '';
$title     = trim( wp_strip_all_tags( $title_raw ) );
$items = array();

foreach ( is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array() as $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}

	$item_title = trim( wp_strip_all_tags( is_scalar( $item['title'] ?? null ) ? (string) $item['title'] : '' ) );
	$item_text  = is_scalar( $item['text'] ?? null ) ? (string) $item['text'] : '';
	if ( '' === $item_title || '' === trim( wp_strip_all_tags( $item_text ) ) ) {
		continue;
	}

	$items[] = array(
		'title' => $item_title,
		'text'  => $item_text,
	);
}

if ( '' === $title || array() === $items ) {
	return;
}

$root_id     = $section_anchor ?: $section_instance;
$title_id    = platejka_section_dom_id( $section_instance, 'title' );
$description = is_scalar( $section_data['description'] ?? null ) ? (string) $section_data['description'] : '';
?>
<section class="process" id="<?php echo esc_attr( $root_id ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="container process__container">
		<h2 class="process__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $title_raw ); ?></h2>
		<ol class="process__items">
			<?php foreach ( $items as $item ) : ?>
				<li class="process__item">
					<div class="process__item-heading">
						<span class="process__number" aria-hidden="true"></span>
						<span class="process__connector" aria-hidden="true"></span>
						<div class="h3"><?php echo esc_html( $item['title'] ); ?></div>
					</div>
					<div class="process__content"><?php echo wp_kses_post( $item['text'] ); ?></div>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php if ( '' !== trim( wp_strip_all_tags( $description ) ) ) : ?>
			<div class="process__description"><?php echo wp_kses_post( $description ); ?></div>
		<?php endif; ?>
	</div>
</section>
