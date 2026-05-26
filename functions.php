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

    $frontend_url = get_option( 'soames_frontend_url' );
    if ( ! $frontend_url ) return;

    $post_id = isset( $_GET['p'] )       ? intval( $_GET['p'] )       : 0;
    $page_id = isset( $_GET['page_id'] ) ? intval( $_GET['page_id'] ) : 0;
    $id      = $post_id ?: $page_id;
    $type    = $page_id ? 'page' : 'post';

    if ( ! $id ) return;

    $token = bin2hex( random_bytes( 16 ) );
    set_transient( 'soames_preview_' . $token, compact( 'id', 'type' ), 5 * MINUTE_IN_SECONDS );

    wp_redirect( rtrim( $frontend_url, '/' ) . '/preview/?token=' . $token, 302 );
    exit;
}
add_action( 'template_redirect', 'soames_preview_redirect' );

add_action( 'rest_api_init', function () {
    register_rest_route( 'soames/v1', '/preview', [
        'methods'             => 'GET',
        'callback'            => 'soames_rest_preview',
        'permission_callback' => '__return_true',
    ] );
} );

function soames_rest_preview( WP_REST_Request $request ) {
    $token = sanitize_text_field( $request->get_param( 'token' ) );

    if ( ! $token ) {
        return new WP_Error( 'no_token', 'Preview token required.', [ 'status' => 400 ] );
    }

    $data = get_transient( 'soames_preview_' . $token );
    if ( ! $data ) {
        return new WP_Error( 'expired', 'Preview token expired or invalid. Click Preview again in WordPress admin.', [ 'status' => 403 ] );
    }

    $post = get_post( $data['id'] );
    if ( ! $post ) {
        return new WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
    }

    $featured_image = null;
    $thumbnail_id   = get_post_thumbnail_id( $post->ID );
    if ( $thumbnail_id ) {
        $src            = wp_get_attachment_image_src( $thumbnail_id, 'full' );
        $featured_image = [
            'sourceUrl' => $src ? $src[0] : null,
            'altText'   => (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
        ];
    }

    return [
        'type'          => $data['type'],
        'title'         => get_the_title( $post ),
        'content'       => apply_filters( 'the_content', $post->post_content ),
        'excerpt'       => apply_filters( 'the_excerpt', $post->post_excerpt ),
        'date'          => $post->post_date,
        'featuredImage' => $featured_image,
    ];
}

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
