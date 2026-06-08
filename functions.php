<?php
// Theme support declarations only.
// All Soames functionality (settings, preview, GraphQL, CORS) lives in the
// Soames plugin (soames-wordpress-plugin.php).
add_theme_support( 'custom-logo' );
add_action( 'after_setup_theme', function() {
	register_nav_menus( array(
		'header' => 'Header Menu',
		'footer' => 'Footer Menu',
	) );
} );
add_theme_support( 'post-thumbnails' );
add_post_type_support( 'page', 'excerpt' );
