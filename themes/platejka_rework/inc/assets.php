<?php
function platejka_rework_assets()
{
	wp_enqueue_style('platejka_rework-header', get_stylesheet_directory_uri() . '/sections/header/header.css', array(), _S_VERSION );
	wp_enqueue_script('platejka_rework-header', get_template_directory_uri() . '/sections/header/header.js', array(), _S_VERSION);
	wp_enqueue_style('platejka_rework-hero', get_stylesheet_directory_uri() . '/sections/hero/hero.css', array(), _S_VERSION );
	wp_enqueue_script('platejka_rework-hero', get_template_directory_uri() . '/sections/hero/hero.js', array(), _S_VERSION);
}
add_action('wp_enqueue_scripts', 'platejka_rework_assets');