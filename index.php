<?php
$frontend_url = get_option( 'soames_frontend_url' );
if ( ! $frontend_url ) return;

$base = rtrim( $frontend_url, '/' );

if ( is_singular( 'post' ) && ! is_preview() ) {
    $path = parse_url( get_permalink(), PHP_URL_PATH );
    wp_redirect( $base . '/blog' . $path, 302 );
    exit;
} elseif ( is_page() && ! is_preview() ) {
    $path = parse_url( get_permalink(), PHP_URL_PATH );
    wp_redirect( $base . $path, 302 );
    exit;
} else {
    wp_redirect( $base . '/', 301 );
    exit;
}
