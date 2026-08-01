# Soames Headless CMS — WordPress theme

Companion theme for a WordPress install acting as a headless CMS behind the
[Soames Astro front end](https://github.com/orbivision/soames-astro-theme).

It is four files and renders nothing:

- **`index.php`** — redirects every front-end request to the site configured in the Soames
  plugin's `soames_frontend_url` setting. Posts go to `/blog/<path>`, pages to `/<path>`
  (302), everything else 301s to the site root. Previews are deliberately exempt — they have
  to render inside WordPress.
- **`functions.php`** — `custom-logo`, `post-thumbnails`, and page excerpt support. These
  have to be theme-side; everything else moved into the plugin in ORBI-12.
- **`style.css`** — the theme header only. There are no styles, because nothing renders.
- **`screenshot.png`** — the admin thumbnail.

## Requires

The [Soames plugin](https://github.com/orbivision/soames-wordpress-plugin), which holds the
real functionality — settings, blocks, the Knowledge Base post type, preview support, and the
WPGraphQL extensions. The theme alone does nothing: without `soames_frontend_url`, which the
plugin owns, `index.php` returns without redirecting.

## Status: expected to be absorbed into the plugin (ORBI-57)

**Don't invest in this repo.** The intent is to move `index.php`'s redirect and the
`add_theme_support()` calls into the Soames plugin — the way
[Faust.js](https://wordpress.org/plugins/faustwp/) does it — so users install one artifact
instead of two. That's a behavior change on every install, so it gets its own project rather
than riding along with the versioning work.

While it's in this state:

- **No release pipeline, deliberately.** The plugin gets tag-triggered GitHub Releases with a
  built zip; this theme doesn't, because it's likely to be deleted. Install it by cloning or
  with GitHub's "Download ZIP" — there's no build step and no dev-only files, so the source
  tree *is* the theme.
- **Versioned only so the header stops lying.** `0.9.0` matches the plugin's restart. The
  header previously read `1.0` and described a Gatsby integration superseded back in ORBI-25.
- **It will not go to the wordpress.org theme directory.** Theme review requires displaying
  content through the standard template files; a theme whose whole job is redirecting can't
  satisfy that, and reworking it to pass would mean undoing the point of it. The plugin
  directory has no such problem — that's where Faust.js lives.

## Version

`0.9.0`, pairing with Soames plugin `>= 0.9.0`. Nothing enforces the pairing.
