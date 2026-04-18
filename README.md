# Elementor Taxonomy Loop Widget

An Elementor Pro add-on that renders a grouped loop: each taxonomy term is rendered as its own section, followed by a Loop Grid of that term's posts using a template you design in the Loop Builder.

Useful for pages like "Browse by category", "Shop by brand", or any layout where posts need to be presented bucketed by term instead of as a single flat list.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Elementor 3.25+
- Elementor Pro 3.25+ (hard dependency — the widget only registers when Pro is active)

## Installation

### From a GitHub release (recommended)

1. Download the latest `elementor-taxonomy-loop.zip` from the [Releases](https://github.com/beenacle/elementor-taxonomy-loop/releases) page.
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Click **Install Now**, then **Activate**.

### From source

Clone or download `main` into `wp-content/plugins/elementor-taxonomy-loop/` (the folder name must not include a commit hash — WordPress expects a stable slug).

### Updates

This plugin is not listed on wordpress.org, so core won't surface update notifications. Use [Git Updater](https://git-updater.com/) for auto-updates from GitHub, or re-upload each release manually.

## Usage

1. Open a page in the Elementor editor.
2. Search for **Taxonomy Loop** in the widget panel and drop it onto the canvas.
3. Pick the **Post Type**, **Taxonomy**, and a **Loop Skin** (create one inline if you don't have a template yet).
4. Adjust filtering, ordering, and styling in the panel. The widget renders each term as `<div class="taxonomy-posts"> … <div class="posts-list">…Loop Grid…</div> </div>`.

## Controls

### Content

| Control | Description |
| --- | --- |
| **Select Post Type** | Source post type (any public post type). |
| **Select Taxonomy** | Taxonomy used to group posts. Must be registered for the selected post type — an error renders if the pair is invalid. |
| **Select Loop Skin** | Loop Builder template used to render each term's posts. Can create/edit templates inline. |
| **Hide Empty Terms** | Hide terms with no matching posts. |
| **Show Divider** | Render an `<hr class="divider">` beneath each term title. |
| **Include Terms (IDs)** | Comma/space-separated term IDs to include. |
| **Exclude Terms (IDs)** | Comma/space-separated term IDs to exclude. |
| **Order Terms By** | `name`, `id`, `slug`, `menu_order`, or `include`. |
| **Order Direction** | Ascending or descending (applies to terms). |
| **Order Posts By** | `date`, `title`, `ID`, `menu_order`, or `rand`. |
| **Post Order Direction** | Ascending or descending (applies to posts within each term). |
| **Posts Per Term** | Max posts per term (default `6`, `-1` for unlimited). |
| **Lazy Load Terms** | Off by default. When on, only the first _N_ terms render server-side; the rest load via AJAX when they scroll into view. Editor/preview always renders everything eagerly. |
| **Eager-Rendered Terms** | Number of terms rendered on first paint (default `2`, minimum `1`). Only shown when **Lazy Load Terms** is on. |
| **Title Prefix / Suffix** | Plain-text strings wrapped around each term name in the rendered `<h2>`. |

### Style

- **Items Settings** — category gap, content gap, border, border radius, padding for the `.taxonomy-posts` wrapper.
- **Category Styling** — border, border radius, padding, typography, color, and alignment for the term title block (`.term-content` / `.term-title`).
- **Loop Controls** — per-breakpoint columns, column gap, row gap, equal-height toggle, and typography/color for the "No posts found" fallback.
- **Divider Style** — width, height, color, top spacing, border radius, and alignment (only shown when **Show Divider** is on).

## Rendered markup

Each term produces:

```html
<div class="taxonomy-posts taxonomy-posts-{TERM_ID}">
  <div class="term-content">
    <h2 class="term-title">{prefix}{term name}{suffix}</h2>
    <hr class="divider" />               <!-- only if Show Divider is on -->
  </div>
  <div class="posts-list">
    <!-- Elementor Loop Grid for this term's posts -->
  </div>
</div>
```

## Performance

The widget runs one bounded `WP_Query` per term (capped by **Posts Per Term**), letting WordPress's object cache short-circuit repeat renders. Setting **Posts Per Term** to `-1` removes that cap — avoid it on terms with very large post counts.

### Lazy loading

Lazy loading is opt-in — flip **Lazy Load Terms** on to enable it. First paint then only queries and renders the first _N_ terms (default `2`). Remaining terms emit a stub that the bundled loader swaps for real content when it scrolls into view — one `admin-ajax.php` request per term, with a 200px pre-load margin.

Setting **Eager-Rendered Terms** to a value that covers your above-the-fold area keeps LCP fast; everything beyond loads on demand.

## Support

For support, feature requests, or bug reports, please visit [beenacle.com/contact-us](https://beenacle.com/contact-us/) or open an issue on this repo.

## Changelog

### 1.2.1
* Localize the JS error string via `wp_localize_script`.
* `error_log()` on `get_terms()` returning `WP_Error` when `WP_DEBUG` is on.
* Housekeeping: `parse_term_ids` is now `private static`, comment typo fixed, unused `foreach` key dropped, dead fallback branches removed (Elementor's `Utils::generate_random_string()` and `IntersectionObserver` are both universally available on supported versions / browsers).

### 1.2.0
* Add lazy loading: when **Lazy Load Terms** is on, terms beyond the "Eager-Rendered Terms" count emit a stub that's filled in via AJAX when it scrolls into view. Default is **off** (opt-in) to avoid surprising existing installs; default eager count is `2`.
* Ship a small vanilla JS loader (`assets/js/taxonomy-loop-lazy.js`) that uses `IntersectionObserver` with a 200px rootMargin to fetch each term as it scrolls into view. Browsers without `IntersectionObserver` fall back to loading every stub immediately instead of never.
* Add a new `wp_ajax_elementor_taxonomy_loop_render_term` endpoint (with `nopriv` variant) that re-validates every field against the same whitelists used in `render()` (post type / taxonomy / `is_object_in_taxonomy()` / template = `elementor_library` / orderby / order / columns clamped 1–12 / posts per term capped at 100).
* Editor and preview modes skip lazy loading so the full layout is always visible while designing.
* Reuse the same render path for both eager and AJAX-rendered terms via a single `render_loop_grid_html()` helper — no drift between the two paths.

### 1.1.1
* Fetch post IDs per term with a bounded query so uneven distribution across terms can't leave later terms empty.
* Drop dependency on Elementor Pro's internal `elementor-pro` style handle from `get_style_depends()`.
* Use Elementor's random-string helper for the synthetic Loop Grid element ID instead of a predictable `loop-grid-{term_id}` string.
* Render a clear error when the selected taxonomy isn't registered for the selected post type, instead of silently returning no terms.
* README: correct minimum WordPress version (6.0) and document current controls, markup, and performance notes.

### 1.1.0
* Require Elementor Pro as a hard dependency; widget only registers when Pro is active, with an admin notice when it isn't.
* Namespace-prefix the widget class (`Beenacle_Taxonomy_Loop`) to avoid global class collisions.
* Load the plugin text domain for self-hosted installs and ship a `/languages` directory.
* Gate debug `error_log()` calls behind `WP_DEBUG`.
* Replace the per-term query loop with two consolidated queries (superseded in 1.1.1).
* Rename the `show_empty` control to `hide_empty`; existing widget instances keep their saved toggle via a raw-data fallback.
* Whitelist `orderby` / `order` values before passing to `WP_Query`.
* Show a "Please select a valid loop template" message when the loop skin is empty or invalid instead of rendering an empty container.
* Change the default `posts_per_term` from `-1` to `6` to avoid unbounded queries on new widgets.
* Use semantic tokens (`left`/`center`/`right`) for the divider alignment control instead of raw CSS fragments.
* Move the widget from Elementor's reserved `basic` category to `general`.
* Normalize indentation and use `esc_html__()` throughout the widget file.
* Update author name to Beenacle.

### 1.0.0
* Initial release

## License

GPL v2 or later — see [GNU GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## Credits

Developed by [Beenacle](https://beenacle.com).
