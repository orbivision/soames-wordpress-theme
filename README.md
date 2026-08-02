# Soames Headless CMS — WordPress theme (retired)

> **This theme is retired as of Soames plugin 1.0.0 (ORBI-58). You do not need it.**
> Everything it did now lives in the
> [Soames plugin](https://github.com/orbivision/soames-wordpress-plugin). Install that, use
> whatever WordPress theme you like, and delete this one whenever convenient.

## What happened

The theme existed to do two things, and both moved into the plugin so that Soames is a single
artifact instead of two:

| Was here | Now |
|---|---|
| `index.php` — redirect front-end requests to the published Soames site | `includes/frontend-redirect.php` in the plugin, on `template_redirect` |
| `add_theme_support( 'post-thumbnails' )` | Declared by the plugin on `after_setup_theme` |
| `add_theme_support( 'custom-logo' )` | **Dropped.** Nothing read it — Soames serves its logo from its own `soames_logo_id` setting |
| `add_post_type_support( 'page', 'excerpt' )` | Already in the plugin; was duplicated here |

The plugin's redirect is also a **better** version than the one deleted from here. It fixes
two bugs this theme shipped with:

- The blog base was hardcoded to `/blog`, while the Astro front end derives it from
  WordPress's **Posts page** setting. On any site whose posts page used a different slug —
  `news`, `articles` — this theme sent visitors to a URL the front end never generates.
- Knowledge Base articles (`/docs/<slug>`) matched neither `is_singular('post')` nor
  `is_page()`, so they hit the catch-all and were redirected to the front end's **home page**,
  losing the article.

It also switches the catch-all from a 301 to a 302, since the destination is a
user-configurable setting and a hard-cached 301 would strand visitors if it ever changed.

## If you are upgrading

Nothing to do. Update the Soames plugin to 1.0.0 or newer and the redirect keeps working; the
plugin runs before template loading, so this theme is inert whether or not it stays active.
Switch to another theme whenever you like.

## Why this repo still exists

It is a tombstone. Deleting a repository that someone may have cloned or linked is worse than
leaving a clear explanation at the address they already have. The theme is not published
anywhere, has no release pipeline, and will not receive updates.

Licensed GPL-2.0-or-later.
