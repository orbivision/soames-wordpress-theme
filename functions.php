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

    $blog_hero = null;
    if ( $data['type'] === 'post' ) {
        $blog_page_id = get_option( 'page_for_posts' );
        if ( $blog_page_id ) {
            $blog_page     = get_post( $blog_page_id );
            $bp_thumb_id   = get_post_thumbnail_id( $blog_page_id );
            $bp_thumb_src  = $bp_thumb_id ? wp_get_attachment_image_src( $bp_thumb_id, 'full' ) : null;
            $blog_hero = [
                'title'          => $blog_page ? get_the_title( $blog_page ) : null,
                'excerpt'        => $blog_page ? $blog_page->post_excerpt : null,
                'guid'           => $bp_thumb_src ? $bp_thumb_src[0] : null,
                'overlayOpacity' => get_post_meta( $blog_page_id, 'soames_overlay_opacity', true ) ?: '0.6',
            ];
        }
    }

    return [
        'type'           => $data['type'],
        'title'          => get_the_title( $post ),
        'content'        => apply_filters( 'the_content', $post->post_content ),
        'excerpt'        => apply_filters( 'the_excerpt', $post->post_excerpt ),
        'date'           => get_the_date( 'F d, Y', $post ),
        'overlayOpacity' => get_post_meta( $post->ID, 'soames_overlay_opacity', true ) ?: '0.6',
        'featuredImage'  => $featured_image,
        'blogHero'       => $blog_hero,
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

// ORBI-10: Hero overlay opacity — post meta, admin metabox, WPGraphQL field

add_action( 'init', function () {
    $args = [
        'type'              => 'string',
        'single'            => true,
        'default'           => '0.6',
        'show_in_rest'      => true,
        'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
        'sanitize_callback' => 'sanitize_text_field',
    ];
    register_post_meta( 'page', 'soames_overlay_opacity', $args );
    register_post_meta( 'post', 'soames_overlay_opacity', $args );
} );

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'soames_overlay_opacity_box',
        'Hero Overlay Opacity',
        'soames_overlay_opacity_render_meta_box',
        [ 'page', 'post' ],
        'side',
        'default'
    );
} );

function soames_overlay_opacity_render_meta_box( $post ) {
    $value   = get_post_meta( $post->ID, 'soames_overlay_opacity', true ) ?: '0.6';
    $options = [ '0.2', '0.3', '0.4', '0.5', '0.6', '0.7' ];
    wp_nonce_field( 'soames_overlay_opacity_save', 'soames_overlay_opacity_nonce' );
    echo '<label for="soames_overlay_opacity" style="display:block;margin-bottom:6px">Overlay opacity</label>';
    echo '<select id="soames_overlay_opacity" name="soames_overlay_opacity" style="width:100%;box-sizing:border-box">';
    foreach ( $options as $opt ) {
        $selected = selected( $value, $opt, false );
        echo "<option value=\"{$opt}\" {$selected}>{$opt}</option>";
    }
    echo '</select>';
}

add_action( 'save_post', function ( $post_id ) {
    if (
        ! isset( $_POST['soames_overlay_opacity_nonce'] ) ||
        ! wp_verify_nonce( $_POST['soames_overlay_opacity_nonce'], 'soames_overlay_opacity_save' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) return;

    if ( isset( $_POST['soames_overlay_opacity'] ) ) {
        update_post_meta( $post_id, 'soames_overlay_opacity', sanitize_text_field( $_POST['soames_overlay_opacity'] ) );
    }
} );

add_action( 'graphql_register_types', function () {
    foreach ( [ 'Page', 'Post' ] as $type ) {
        register_graphql_field( $type, 'overlayOpacity', [
            'type'        => 'String',
            'description' => 'Hero header overlay opacity (0.2–0.7)',
            'resolve'     => fn( $post ) => get_post_meta( $post->databaseId, 'soames_overlay_opacity', true ) ?: '0.6',
        ] );
    }
} );

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
