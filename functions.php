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
