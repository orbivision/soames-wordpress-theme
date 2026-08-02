<?php
/**
 * DEPRECATED (ORBI-58) — nothing here any more.
 *
 * The declarations that used to live here moved into the Soames plugin 1.0.0:
 *   - post-thumbnails  → declared by the plugin on after_setup_theme
 *   - page excerpts    → already in the plugin; this was a duplicate
 *   - custom-logo      → dropped, because nothing ever read it (Soames serves its logo
 *                        from its own soames_logo_id setting)
 *
 * Left in place, empty, so that activating this theme on an older install doesn't fatal —
 * not because anything is expected to be added back.
 */

defined( 'ABSPATH' ) || exit;
