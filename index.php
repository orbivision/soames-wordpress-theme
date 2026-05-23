<?php
$frontend_url = get_option( 'soames_frontend_url' );
if ( $frontend_url ) {
    wp_redirect( $frontend_url, 301 );
    exit;
}
