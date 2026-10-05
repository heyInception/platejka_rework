<?php
/**
 * Walker for the primary navigation menu.
 *
 * @package platejka_rework
 */

/**
 * Builds the primary navigation markup expected by the header styles and scripts.
 */
class Platejka_Rework_Nav_Walker extends Walker_Nav_Menu {
	/**
	 * Submenu IDs, indexed by their parent depth.
	 *
	 * @var array<int, string>
	 */
	private $submenu_ids = array();

	/**
	 * Starts a submenu level.
	 *
	 * @param string   $output Used to append additional content.
	 * @param int      $depth  Depth of menu item. Used for padding.
	 * @param stdClass $args   An object of wp_nav_menu() arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$classes = array( 'list-reset', 'nav__submenu' );

		if ( 0 < $depth ) {
			$classes[] = 'nav__submenu--nested';
		}

		$submenu_id = isset( $this->submenu_ids[ $depth ] ) ? $this->submenu_ids[ $depth ] : '';
		$output    .= '<ul class="' . esc_attr( implode( ' ', $classes ) ) . '" id="' . esc_attr( $submenu_id ) . '">';
	}

	/**
	 * Starts the element output.
	 *
	 * @param string   $output Used to append additional content.
	 * @param WP_Post  $item   Menu item data object.
	 * @param int      $depth  Depth of menu item. Used for padding.
	 * @param stdClass $args   An object of wp_nav_menu() arguments.
	 * @param int      $id     Current item ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$has_children = $this->has_children;
		$item_classes = array();

		if ( 0 === $depth ) {
			$item_classes[] = 'nav__item';
		} elseif ( 1 === $depth && $has_children ) {
			$item_classes[] = 'nav__subitem';
			$item_classes[] = 'nav__subitem--has-children';
		}

		$output .= '<li' . ( $item_classes ? ' class="' . esc_attr( implode( ' ', $item_classes ) ) . '"' : '' ) . '>';

		$has_heading = 0 === $depth || $has_children;
		if ( $has_heading ) {
			$output .= '<div class="' . ( 0 === $depth ? 'nav__heading' : 'nav__subheading' ) . '">';
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );
		$atts  = array(
			'target' => ! empty( $item->target ) ? $item->target : '',
			'rel'    => ! empty( $item->xfn ) ? $item->xfn : '',
			'href'   => ! empty( $item->url ) ? $item->url : '',
			'class'  => 0 === $depth ? 'nav__link' : 'nav__sublink',
		);
		$atts  = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

		$output .= '<a' . $this->build_menu_attributes( $atts ) . '>' . esc_html( $title ) . '</a>';

		if ( $has_children ) {
			$submenu_id                   = 'submenu-' . absint( $item->ID );
			$this->submenu_ids[ $depth ] = $submenu_id;
			$label                        = sprintf(
				/* translators: %s: menu item title. */
				esc_html__( 'Открыть подменю «%s»', 'platejka_rework' ),
				$title
			);

			$output .= '<button class="btn-reset nav__toggle" type="button" aria-expanded="false" data-nav-toggle aria-controls="' . esc_attr( $submenu_id ) . '">';
			$output .= '<span class="visually-hidden">' . esc_html( $label ) . '</span>';
			$output .= '</button>';
		}

		if ( $has_heading ) {
			$output .= '</div>';
		}
	}

	/**
	 * Converts link attributes to escaped HTML attributes.
	 *
	 * @param array<string, string> $atts Link attributes.
	 * @return string
	 */
	private function build_menu_attributes( $atts ) {
		$attributes = '';

		foreach ( $atts as $attribute => $value ) {
			if ( false === $value || '' === $value || null === $value ) {
				continue;
			}

			$value       = 'href' === $attribute ? esc_url( $value ) : esc_attr( $value );
			$attributes .= ' ' . esc_attr( $attribute ) . '="' . $value . '"';
		}

		return $attributes;
	}
}
