<?php
/** Services navigation section. */

$service_menus = array(
	array(
		'title' => __( 'Международные платежи и расчеты', 'platejka_rework' ),
		'slug'  => 'mezhdunarodnye-platezhi-i-raschety',
		'wide'  => true,
	),
	array(
		'title' => __( 'Валютные переводы', 'platejka_rework' ),
		'slug'  => 'valyutnye-perevody',
		'wide'  => true,
	),
	array(
		'title' => __( 'Платежи в Китай', 'platejka_rework' ),
		'slug'  => 'platezhi-v-kitaj',
		'wide'  => false,
	),
	array(
		'title' => __( 'Платежи в Европу', 'platejka_rework' ),
		'slug'  => 'platezhi-v-evropu',
		'wide'  => false,
	),
	array(
		'title' => __( 'Аутсорсинг и управление ВЭД', 'platejka_rework' ),
		'slug'  => 'autsorsing-i-upravlenie-ved',
		'wide'  => false,
	),
);
?>

<section class="services-section" aria-labelledby="services-section-title">
	<div class="container">
		<h1 class="services-section__title" id="services-section-title"><?php the_title(); ?></h1>

		<div class="services-section__items">
			<?php foreach ( $service_menus as $index => $service_menu ) : ?>
				<article class="services-section__item<?php echo $service_menu['wide'] ? ' services-section__item--wide' : ''; ?>">
					<h2 class="services-section__head"><?php echo esc_html( $service_menu['title'] ); ?></h2>
					<nav aria-label="<?php echo esc_attr( $service_menu['title'] ); ?>">
						<?php
						wp_nav_menu(
							array(
								'menu'        => $service_menu['slug'],
								'menu_id'     => 'services-menu-' . ( $index + 1 ),
								'menu_class'  => 'services-section__list',
								'container'   => false,
								'depth'       => 1,
								'fallback_cb' => false,
							)
						);
						?>
					</nav>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
