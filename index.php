<?php
/**
 * DEPRECATED (ORBI-58) — this theme no longer does anything.
 *
 * The front-end redirect that used to live here moved into the Soames plugin in version
 * 1.0.0, so Soames is one artifact instead of two. See includes/frontend-redirect.php there.
 *
 * This file is emptied rather than left as-is because running two copies of the same
 * redirect is not something anyone should have to reason about. In practice the plugin
 * already wins — it redirects on `template_redirect`, before WordPress loads any template —
 * but that is hook ordering deciding behaviour, which is a poor thing to depend on.
 *
 * The plugin's version also fixes two bugs present in the code deleted from here:
 *   - the blog base was hardcoded to `/blog` instead of following WordPress's "Posts page"
 *     setting, so posts pointed at a URL the front end never generates on any site whose
 *     posts page used a different slug;
 *   - Knowledge Base articles fell through to the catch-all and were sent to the front
 *     end's home page, losing the article entirely.
 *
 * You can safely switch to any other theme. Nothing renders either way, because the plugin
 * redirects before template loading.
 */

defined( 'ABSPATH' ) || exit;

// Intentionally renders nothing. Reaching this point means front-end redirection is switched
// off in Soames → Settings, and a blank page is the honest outcome: this theme has no
// templates and never had any.
