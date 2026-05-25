<?php
add_theme_support( 'custom-logo' );
add_theme_support( 'menus' );
add_theme_support( 'post-thumbnails' );
add_post_type_support( 'page', 'excerpt' );

function soames_admin_menu() {
    add_menu_page(
        'Soames Settings',
        'Soames',
        'manage_options',
        'soames-settings',
        'soames_settings_page',
        'dashicons-admin-site-alt3',
        60
    );
}
add_action( 'admin_menu', 'soames_admin_menu' );

function soames_sanitize_frontend_url( $url ) {
    $url = esc_url_raw( trim( $url ) );
    if ( $url && ! wp_http_validate_url( $url ) ) {
        add_settings_error(
            'soames_frontend_url',
            'invalid_url',
            'Please enter a valid URL including http:// or https://.',
            'error'
        );
        return get_option( 'soames_frontend_url' );
    }
    return $url;
}

function soames_register_settings() {
    register_setting( 'soames_options', 'soames_frontend_url', [
        'type'              => 'string',
        'sanitize_callback' => 'soames_sanitize_frontend_url',
        'default'           => '',
    ] );
}
add_action( 'admin_init', 'soames_register_settings' );

function soames_preview_redirect() {
    if ( ! isset( $_GET['preview'] ) || $_GET['preview'] !== 'true' ) return;
    if ( ! isset( $_GET['gatsby_preview'] ) ) return;

    $frontend_url = get_option( 'soames_frontend_url' );
    if ( ! $frontend_url ) return;

    $post_id  = isset( $_GET['p'] ) ? intval( $_GET['p'] ) : 0;
    $page_id  = isset( $_GET['page_id'] ) ? intval( $_GET['page_id'] ) : 0;
    $base_url = rtrim( $frontend_url, '/' );

    if ( $post_id ) {
        wp_redirect( $base_url . '/preview/?id=' . $post_id . '&type=post', 302 );
        exit;
    } elseif ( $page_id ) {
        wp_redirect( $base_url . '/preview/?id=' . $page_id . '&type=page', 302 );
        exit;
    }
}
add_action( 'template_redirect', 'soames_preview_redirect' );

function soames_graphql_cors_headers( $headers ) {
    $frontend_url = get_option( 'soames_frontend_url' );
    if ( ! $frontend_url ) return $headers;

    $origin          = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
    $frontend_origin = rtrim( $frontend_url, '/' );

    if ( $origin === $frontend_origin ) {
        $headers['Access-Control-Allow-Origin']      = $frontend_origin;
        $headers['Access-Control-Allow-Credentials'] = 'true';
    }

    return $headers;
}
add_filter( 'graphql_response_headers_to_send', 'soames_graphql_cors_headers' );

function soames_graphql_cors_preflight() {
    if ( $_SERVER['REQUEST_METHOD'] !== 'OPTIONS' ) return;

    $frontend_url = get_option( 'soames_frontend_url' );
    if ( ! $frontend_url ) return;

    $origin          = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
    $frontend_origin = rtrim( $frontend_url, '/' );

    if ( $origin !== $frontend_origin ) return;

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    if ( strpos( $request_uri, 'graphql' ) === false ) return;

    header( 'Access-Control-Allow-Origin: ' . $frontend_origin );
    header( 'Access-Control-Allow-Credentials: true' );
    header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS' );
    header( 'Access-Control-Allow-Headers: Content-Type' );
    status_header( 204 );
    exit;
}
add_action( 'init', 'soames_graphql_cors_preflight' );

function soames_settings_page() {
    if ( isset( $_GET['settings-updated'] ) ) {
        $errors = get_settings_errors( 'soames_frontend_url' );
        if ( empty( $errors ) ) {
            add_settings_error( 'soames_messages', 'soames_saved', 'Settings saved.', 'updated' );
        }
    }
    ?>
    <div class="wrap">
        <h1>Soames Settings</h1>
        <?php
        settings_errors( 'soames_messages' );
        settings_errors( 'soames_frontend_url' );
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'soames_options' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="soames_frontend_url">Frontend Site URL</label>
                    </th>
                    <td>
                        <input
                            type="url"
                            id="soames_frontend_url"
                            name="soames_frontend_url"
                            value="<?php echo esc_attr( get_option( 'soames_frontend_url' ) ); ?>"
                            class="regular-text"
                            placeholder="https://example.com"
                        />
                        <p class="description">
                            Direct visits to this WordPress installation will be redirected to this URL.
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
