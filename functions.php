<?php
// Theme support declarations only.
// All Soames functionality (settings, preview, GraphQL, CORS) lives in the
// Soames plugin (soames-wordpress-plugin.php).
add_theme_support( 'custom-logo' );
add_theme_support( 'menus' );
add_theme_support( 'post-thumbnails' );
add_post_type_support( 'page', 'excerpt' );
