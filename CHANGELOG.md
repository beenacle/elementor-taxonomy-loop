# Changelog

All notable changes to the Elementor Taxonomy Loop Widget are documented here.
This project follows [Semantic Versioning](https://semver.org/).

## 2.0.1

**Fixed**

* **2.0.0 broke the Elementor editor site-wide.** Opening *any* page in Elementor
  died with `Controls_Stack::sanitize_settings(): Argument #1 ($settings) must be of
  type array, null given`, and the preview pane showed only "The preview could not be
  loaded / error 500". 2.0.0's `get_script_depends()` read the widget's `lazy_load`
  setting to decide whether to ship the lazy loader. Elementor also calls that method
  on the widget's *registration* instance, which is constructed with no element data
  at all -- `Widgets_Manager::enqueue_widgets_scripts()` does exactly that on every
  editor preview load -- so the settings read hit `null` and fataled before any page
  content rendered. The widget now declares the script unconditionally on the
  registration instance, which is what the editor needs anyway: toggling **Lazy Load**
  in the panel cannot re-run `wp_enqueue_script()`, so the handle has to already be on
  the preview page. Rendered elements are unaffected -- pages with lazy loading off
  still ship no JavaScript.

  Only the editor was affected; published pages using the widget rendered normally
  throughout.

## 2.0.0

Compatibility release for current Elementor and Elementor Pro (tested up to 4.2.2),
plus a hardening pass on the lazy-load endpoint. **Read the breaking changes before
updating** — the widget's DOM changed.

**Breaking**

* **The widget's DOM gained a wrapper and can now lose one.** All of its styling moved
  onto a new `.e-taxonomy-loop` element that sits between Elementor's widget wrapper
  and the term sections, and the widget now honours Elementor's **Optimized Markup**
  setting the way core widgets do. Two things follow for custom CSS:
  * On sites with Optimized Markup enabled, `.elementor-widget-container` is no longer
    rendered for this widget. CSS naming it must move to `.e-taxonomy-loop`.
  * CSS using a direct-child combinator through the old structure (for example
    `.elementor-widget-container > .taxonomy-posts`) no longer matches, because
    `.e-taxonomy-loop` now sits in between.

  Every class the widget itself emits — `.taxonomy-posts`, `.term-content`,
  `.term-title`, `.posts-list`, `.divider`, `.not-found` — is unchanged.
* The widget now lives under its own **Beenacle** category in the editor panel
  instead of **General**. Existing widgets keep working; only the panel section moved.
* Configuration errors ("Invalid taxonomy selected", "Please select a valid loop
  template") render in the editor only. Visitors see nothing instead of a red error box.
* The lazy-load AJAX request now sends `post_id` / `element_id` / `term_id` and no
  nonce. Anything that hooked or proxied the old payload needs updating.

**Fixed**

* `.posts-list { margin-top: 10px }` shipped unscoped and applied to *any* element on
  the site with that class, not just this widget's.
* Lazy loading broke on cached pages. The nonce was baked into the cached HTML, went
  stale, and every deferred term failed with "Unable to load posts for this term".
  Since the endpoint only returns already-public markup and now derives every setting
  server-side, the nonce is gone and the failure mode with it.
* Lazy-rendered terms silently capped at 100 posts while eagerly-rendered terms did
  not, so **Posts Per Term = -1** produced inconsistent output within one widget. Both
  paths now honour the setting identically.
* A crafted AJAX request could render any `elementor_library` post as the loop
  template, including drafts and private templates. The endpoint no longer accepts a
  template ID — it reads the one saved on the widget.
* Only `\Exception` was caught around the Loop Grid render, so a PHP `\Error` from
  Elementor Pro took down the whole page. `\Throwable` is caught now, and a failed
  render can no longer leave an output buffer open.
* Lazily-injected Loop Grids could arrive unstyled when every eagerly-rendered term
  happened to be empty, because Elementor Pro's grid stylesheet had never been
  enqueued. The widget now preloads it when lazy loading is on.

**Added**

* **Max Terms** control, to cap how many terms a widget renders. The term query was
  previously unbounded, which is rough on taxonomies with hundreds of terms.
* **Title HTML Tag** control (`h1`–`h6`, `div`, `p`, `span`). The term title was
  hard-coded to `<h2>`, which forced a heading level on every layout.
* **Post Count** ordering for terms and **Last Modified** ordering for posts.
* Requirement checks for Elementor, Elementor Pro and PHP, each with a single
  actionable admin notice shown only to users who can act on it.
* Three filters for extending the widget without forking it:
  `elementor_taxonomy_loop_terms_args`, `elementor_taxonomy_loop_posts_args` and
  `elementor_taxonomy_loop_lazy_style_handles`.
* Plugin icons, so the update screen shows the plugin's own icon, and a
  `Tested up to` header so WordPress stops flagging it as untested.
* `languages/elementor-taxonomy-loop.pot` translation template, regenerated with
  `php bin/make-pot.php`.
* `CHANGELOG.md`, which the release workflow now uses for release notes.

**Changed**

* **Ready for Elementor's optimized DOM.** Elementor's developer documentation tells
  add-ons to stop relying on `.elementor-widget-container`, and core's
  `common-optimized` control stack is marked to become the only one. The 1.x widget
  hung its stylesheet and eight style controls off that element, which worked only
  because Elementor still defaults third-party widgets to rendering it. The widget now
  implements `has_widget_inner_wrapper()` exactly as core widgets do — tracking the
  site's Optimized Markup setting — and styles itself through `.e-taxonomy-loop`, which
  is present either way. Nothing looks different today; it just stops depending on an
  element Elementor is retiring.
* Rewritten as namespaced classes (`Beenacle\ElementorTaxonomyLoop\…`) behind a
  small autoloader, and the whole plugin now passes the `WordPress` PHP_CodeSniffer
  standard plus `PHPCompatibility` for PHP 7.4–8.4. CI enforces both.
* The lazy-loader script is only enqueued when **Lazy Load Terms** is on.
* Editor panel search now matches many more terms (category, tag, grid, archive,
  WooCommerce, product category, …).
* The Loop Skin control falls back to a plain template-ID field if Elementor Pro
  ever renames its template query control, instead of fatalling the editor.

## 1.2.2

* Unified GitHub-Releases self-updater, so installed sites see new versions in
  Dashboard → Updates like any other plugin.

## 1.2.1

* Localize the JS error string via `wp_localize_script`.
* `error_log()` on `get_terms()` returning `WP_Error` when `WP_DEBUG` is on.
* Housekeeping: `parse_term_ids` is now `private static`, comment typo fixed, unused
  `foreach` key dropped, dead fallback branches removed.

## 1.2.0

* Add lazy loading: when **Lazy Load Terms** is on, terms beyond the "Eager-Rendered
  Terms" count emit a stub that is filled in via AJAX when it scrolls into view.
  Default is off; default eager count is `2`.
* Ship a small vanilla JS loader that uses `IntersectionObserver` with a 200px
  `rootMargin` to fetch each term as it scrolls into view.
* Add a `wp_ajax_elementor_taxonomy_loop_render_term` endpoint (with `nopriv`
  variant) that re-validates every field against the same whitelists used in `render()`.
* Editor and preview modes skip lazy loading so the full layout is always visible
  while designing.
* Reuse the same render path for both eager and AJAX-rendered terms.

## 1.1.1

* Fetch post IDs per term with a bounded query so uneven distribution across terms
  can't leave later terms empty.
* Drop the dependency on Elementor Pro's internal `elementor-pro` style handle.
* Use Elementor's random-string helper for the synthetic Loop Grid element ID.
* Render a clear error when the selected taxonomy isn't registered for the selected
  post type, instead of silently returning no terms.
* README: correct minimum WordPress version and document current controls.

## 1.1.0

* Require Elementor Pro as a hard dependency; the widget only registers when Pro is
  active, with an admin notice when it isn't.
* Namespace-prefix the widget class to avoid global class collisions.
* Load the plugin text domain for self-hosted installs and ship a `/languages` directory.
* Gate debug `error_log()` calls behind `WP_DEBUG`.
* Rename the `show_empty` control to `hide_empty`; existing widget instances keep
  their saved toggle via a raw-data fallback.
* Whitelist `orderby` / `order` values before passing them to `WP_Query`.
* Show a "Please select a valid loop template" message when the loop skin is empty
  or invalid instead of rendering an empty container.
* Change the default `posts_per_term` from `-1` to `6` to avoid unbounded queries.
* Use semantic tokens for the divider alignment control instead of raw CSS fragments.
* Move the widget from Elementor's reserved `basic` category to `general`.

## 1.0.0

* Initial release.
