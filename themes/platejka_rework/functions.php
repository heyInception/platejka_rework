<?php

/**
 * platejka_rework functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package platejka_rework
 */

if (! defined('_S_VERSION')) {
	// Replace the version number of the theme on each release.
	define('_S_VERSION', '1.0.0');
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function platejka_rework_setup()
{
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on platejka_rework, use a find and replace
		* to change 'platejka_rework' to the name of your theme in all the template files.
		*/
	load_theme_textdomain('platejka_rework', get_template_directory() . '/languages');

	// Add default posts and comments RSS feed links to head.
	add_theme_support('automatic-feed-links');

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support('title-tag');

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support('post-thumbnails');

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__('Primary', 'platejka_rework'),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'platejka_rework_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support('customize-selective-refresh-widgets');

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action('after_setup_theme', 'platejka_rework_setup');

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function platejka_rework_content_width()
{
	$GLOBALS['content_width'] = apply_filters('platejka_rework_content_width', 640);
}
add_action('after_setup_theme', 'platejka_rework_content_width', 0);

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function platejka_rework_widgets_init()
{
	register_sidebar(
		array(
			'name'          => esc_html__('Sidebar', 'platejka_rework'),
			'id'            => 'sidebar-1',
			'description'   => esc_html__('Add widgets here.', 'platejka_rework'),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action('widgets_init', 'platejka_rework_widgets_init');

/**
 * Enqueue scripts and styles.
 */
function platejka_rework_scripts()
{
	wp_enqueue_style('platejka_rework-commoncss', get_stylesheet_directory_uri() . '/assets/common.css', array(), platejka_section_asset_version(get_theme_file_path('assets/common.css')));
	wp_enqueue_style('platejka_rework-style', get_stylesheet_uri(), array(), _S_VERSION);
	wp_enqueue_style('platejka_rework-dialog-overrides', get_template_directory_uri() . '/assets/dialog-overrides.css', array('platejka_rework-style'), _S_VERSION);
	wp_enqueue_style('platejka_rework-contact-modals', get_template_directory_uri() . '/assets/contact-modals.css', array('platejka_rework-commoncss'), platejka_section_asset_version(get_theme_file_path('assets/contact-modals.css')));
	wp_style_add_data('platejka_rework-style', 'rtl', 'replace');

	wp_enqueue_script('platejka_rework-common', get_template_directory_uri() . '/assets/common.js', array(), platejka_section_asset_version(get_theme_file_path('assets/common.js')), true);
	wp_enqueue_script('platejka_rework-window-opener', get_template_directory_uri() . '/assets/window-opener.js', array('platejka_rework-common'), _S_VERSION, true);
	wp_enqueue_script('platejka_rework-contact-modals', get_template_directory_uri() . '/assets/contact-modals.js', array('platejka_rework-common'), platejka_section_asset_version(get_theme_file_path('assets/contact-modals.js')), true);
	wp_enqueue_script('platejka_rework-location-gallery', get_template_directory_uri() . '/assets/location-gallery.js', array(), platejka_section_asset_version(get_theme_file_path('assets/location-gallery.js')), true);

	if (is_singular() && comments_open() && get_option('thread_comments')) {
		wp_enqueue_script('comment-reply');
	}
}
add_action('wp_enqueue_scripts', 'platejka_rework_scripts');

/** Guests do not need the WordPress admin icon font on the frontend. */
function platejka_rework_dequeue_guest_dashicons()
{
	if (! is_user_logged_in()) {
		wp_dequeue_style('dashicons');
	}
}
add_action('wp_enqueue_scripts', 'platejka_rework_dequeue_guest_dashicons', 100);

/** Page-builder data must be available before templates declare assets. */
require get_template_directory() . '/inc/section-builder.php';

/** Legal-document page routing and footer destinations. */
require get_template_directory() . '/inc/legal-pages.php';

/** Inherited ACF content and safe section-rendering helpers. */
require get_template_directory() . '/inc/section-content.php';

/** Global Contact Form 7 modals and ACF settings. */
require get_template_directory() . '/inc/contact-modals.php';
require get_template_directory() . '/inc/work-form.php';

/** Editable organization details and HTML microdata. */
require get_template_directory() . '/inc/structured-data.php';

/** Approved cross-block ACF layouts and editor labels. */
require get_template_directory() . '/inc/cross-block-schema.php';

/** Legacy cross-block composition and data bridge. */
require get_template_directory() . '/inc/cross-blocks.php';

/** Conditional section assets and rendering. */
require get_template_directory() . '/inc/assets.php';

/** Inline Marquiz placement and per-page controls. */
require get_template_directory() . '/inc/marquiz-section.php';

/**
 * Primary navigation walker.
 */
require get_template_directory() . '/inc/class-platejka-rework-nav-walker.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

require get_template_directory() . '/inc/blog.php';

require get_template_directory() . '/inc/blog-ajax.php';

require get_template_directory() . '/inc/yoast-breadcrumbs.php';


add_filter('wpcf7_autop_or_not', '__return_false');
