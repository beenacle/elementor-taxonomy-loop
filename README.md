# Elementor Taxonomy Loop — group posts by category, tag or any taxonomy term

[![Latest release](https://img.shields.io/github/v/release/beenacle/elementor-taxonomy-loop?label=release)](https://github.com/beenacle/elementor-taxonomy-loop/releases)
[![License: GPL v2+](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%20–%207.0-21759b.svg)](https://wordpress.org/)
[![Elementor Pro](https://img.shields.io/badge/Elementor%20Pro-3.25%20–%204.2-92003b.svg)](https://elementor.com/pro/)
[![PHP](https://img.shields.io/badge/PHP-7.4%20–%208.4-777bb4.svg)](https://www.php.net/)

**A free Elementor Pro add-on that renders one Loop Grid per taxonomy term.** Point it
at a post type and a taxonomy, pick a Loop Builder template, and every category, tag,
brand, or custom term gets its own heading followed by a grid of that term's posts —
on a single page, from a single widget.

It is the "posts grouped by category" layout that Elementor Pro's Loop Grid cannot
produce on its own, without stacking one hand-configured Loop Grid per category and
re-doing the work every time someone adds a term.

---

## Contents

- [What it renders](#what-it-renders)
- [How this differs from Elementor Pro's Loop Grid](#how-this-differs-from-elementor-pros-loop-grid)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [What people build with it](#what-people-build-with-it)
- [Controls](#controls)
- [Rendered markup](#rendered-markup)
- [Styling and CSS hooks](#styling-and-css-hooks)
- [Performance and lazy loading](#performance-and-lazy-loading)
- [Developer reference](#developer-reference)
- [FAQ](#faq)
- [Troubleshooting](#troubleshooting)
- [Compatibility](#compatibility)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [License](#license)

## What it renders

One widget, dropped once, produces a page like this — and keeps producing it as terms
come and go:

```
┌─ Taxonomy Loop widget ──────────────────────────────────────────┐
│                                                                 │
│  Espresso Drinks                        ← term name (h1–h6)     │
│  ────────────                           ← optional divider      │
│  ┌────────┐ ┌────────┐ ┌────────┐       ← Loop Grid, your       │
│  │ post   │ │ post   │ │ post   │         Loop Builder template │
│  └────────┘ └────────┘ └────────┘                               │
│                                                                 │
│  Cold Brew                                                      │
│  ─────────                                                      │
│  ┌────────┐ ┌────────┐ ┌────────┐                               │
│  │ post   │ │ post   │ │ post   │                               │
│  └────────┘ └────────┘ └────────┘                               │
│                                                                 │
│  … one section per term, ordered and filtered as you configure  │
└─────────────────────────────────────────────────────────────────┘
```

Each grid is a real Elementor Pro Loop Grid rendered from a template you design in the
Loop Builder, so your card layout, dynamic tags and styling all work exactly as they do
anywhere else.

## How this differs from Elementor Pro's Loop Grid

Elementor Pro 3.21 added a **taxonomy query** to the Loop Grid, and it is frequently
mistaken for this. The two do opposite things:

| | Elementor Pro Loop Grid | Elementor Taxonomy Loop |
| --- | --- | --- |
| **Loop items are** | Posts, *or* taxonomy terms | Posts, grouped under a heading per term |
| **Taxonomy query renders** | A grid **of** categories | A grid **per** category |
| **Grids per widget** | One | One per term, generated automatically |
| **New term added** | Nothing changes, or one more card | A new section appears with its posts |
| **Per-term heading** | Only as a loop item | Yes, with its own HTML tag and styling |
| **To group by category today** | Stack N Loop Grids and filter each by hand | Drop one widget |

If you want a page that shows a card for each category, use Elementor Pro's taxonomy
query. If you want a page that shows *each category's posts under that category's name*,
that's this plugin.

## Requirements

| | Minimum | Tested up to |
| --- | --- | --- |
| WordPress | 6.0 | 7.0 |
| PHP | 7.4 | 8.4 |
| Elementor | 3.25 | 4.2.2 |
| Elementor Pro | 3.25 | 4.2.2 |

Elementor Pro is a hard dependency — the widget renders Pro's Loop Grid, so it only
registers when Pro is active. If a requirement is missing you get one clear admin
notice explaining which.

## Installation

### From a GitHub release (recommended)

1. Download `elementor-taxonomy-loop.zip` from the [Releases](https://github.com/beenacle/elementor-taxonomy-loop/releases) page.
2. In WordPress admin go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Click **Install Now**, then **Activate**.

### From source

Clone into `wp-content/plugins/elementor-taxonomy-loop/`. The folder name matters —
WordPress expects a stable slug, so don't leave a commit hash on the end of it.

```bash
git clone https://github.com/beenacle/elementor-taxonomy-loop.git \
  wp-content/plugins/elementor-taxonomy-loop
```

### Updates

The plugin updates itself from GitHub Releases. New versions appear under
**Dashboard → Updates** and install like any other plugin — no extra update manager,
no account, no license key.

## Usage

1. Edit a page with Elementor.
2. Search the widget panel for **Taxonomy Loop** (under the **Beenacle** category) and
   drop it on the canvas.
3. Pick your **Post Type** and **Taxonomy**. They have to be paired — `category` works
   with `post`, `product_cat` with `product`, and so on.
4. Choose a **Loop Skin**: an existing Loop Builder template, or create one inline with
   the **+** button and design your card.
5. Tune ordering, filtering and styling in the panel.

That's it — the widget queries the terms itself, so new categories show up without
anyone touching the page again.

## What people build with it

- **Browse-by-category landing pages** — every blog category with its latest posts
  underneath, on one page.
- **Shop by brand or product category** — one WooCommerce product grid per
  `product_cat` or brand term, using your own product card template.
- **Directory and listing sites** — group listings by city, region, service type, or
  any custom taxonomy on a custom post type.
- **Documentation and knowledge-base indexes** — every docs section with its articles.
- **Course or resource libraries** — group lessons, downloads, or case studies by topic.
- **Team, portfolio, or event pages** — grouped by department, project type, or track.

Anything with a taxonomy works: built-in categories and tags, WooCommerce product
categories, and custom taxonomies on custom post types.

## Controls

### Content

| Control | What it does |
| --- | --- |
| **Select Post Type** | Source post type — any public post type, including custom ones. |
| **Select Taxonomy** | Taxonomy to group by. Must be registered for the selected post type. |
| **Select Loop Skin** | Loop Builder template used for each term's posts. Create or edit one inline. |
| **Hide Empty Terms** | Skip terms that have no posts. |
| **Show Divider** | Render an `<hr class="divider">` under each term title. |
| **Include Terms (IDs)** | Comma- or space-separated term IDs to include. When set, Exclude is ignored (WordPress behaviour). |
| **Exclude Terms (IDs)** | Comma- or space-separated term IDs to skip. |
| **Max Terms** | Cap how many terms render. `0` means all — avoid that on taxonomies with hundreds of terms. |
| **Order Terms By** | Name, ID, slug, menu order, post count, or include order. |
| **Order Direction** | Ascending or descending, applied to terms. |
| **Order Posts By** | Date, last modified, title, ID, menu order, or random. |
| **Post Order Direction** | Ascending or descending, applied to posts inside each term. |
| **Posts Per Term** | Max posts per term. Default `6`; `-1` for all. |
| **Lazy Load Terms** | Off by default. On: only the first *N* terms render server-side, the rest load as they scroll into view. |
| **Eager-Rendered Terms** | How many terms render on first paint. Default `2`, minimum `1`. |
| **Title HTML Tag** | `h1`–`h6`, `div`, `p` or `span` for the term title. Default `h2`. |
| **Title Prefix / Suffix** | Plain text wrapped around each term name. |

### Style

- **Items Settings** — gap between terms, content gap, border, border radius and padding
  for each `.taxonomy-posts` section.
- **Term Title** — border, radius, padding, typography, colour and alignment for the
  title block.
- **Loop Controls** — columns per breakpoint, column gap, row gap, equal-height cards,
  and typography/colour for the "No posts found" state.
- **Divider Style** — width, height, colour, top spacing, radius and alignment. Only
  shown when **Show Divider** is on.

## Rendered markup

```html
<div class="elementor-widget elementor-widget-taxonomy_loop">
  <div class="e-taxonomy-loop">
    <div class="taxonomy-posts taxonomy-posts-{TERM_ID}">
      <div class="term-content">
        <h2 class="term-title">{prefix}{term name}{suffix}</h2>
        <hr class="divider" />          <!-- only when Show Divider is on -->
      </div>
      <div class="posts-list">
        <!-- Elementor Pro Loop Grid for this term's posts -->
      </div>
    </div>
    <!-- …one .taxonomy-posts block per term -->
  </div>
</div>
```

## Styling and CSS hooks

Every style control writes to these classes, and so can your own CSS:

| Class | Element |
| --- | --- |
| `.e-taxonomy-loop` | Widget wrapper. The flex column that spaces the terms apart. |
| `.taxonomy-posts` | One term's section. Also carries `.taxonomy-posts-{TERM_ID}`. |
| `.term-content` | The title block. |
| `.term-title` | The term heading itself. |
| `.divider` | The optional `<hr>`. |
| `.posts-list` | Wrapper around the term's Loop Grid. |
| `.not-found` | "No posts found for this term." |

> **Upgrading from 1.x:** `.e-taxonomy-loop` is new, and it sits between Elementor's
> widget wrapper and the term sections. The widget also stopped hanging its styles off
> `.elementor-widget-container`, which Elementor's developer docs ask add-ons not to
> rely on; with the **Optimized Markup** experiment enabled that element is no longer
> rendered for this widget at all. Repoint custom CSS from
> `.elementor-widget-taxonomy_loop .elementor-widget-container` to
> `.elementor-widget-taxonomy_loop .e-taxonomy-loop`, and swap any direct-child
> combinator that ran through the old structure for a descendant selector. Every other
> class above is unchanged.

## Performance and lazy loading

The widget runs one bounded query per term, capped by **Posts Per Term**, fetching post
IDs only. That keeps each query cheap and lets WordPress's object cache short-circuit
repeat renders. Setting **Posts Per Term** to `-1` removes the cap — avoid it on terms
with very large post counts.

**Max Terms** is the other lever. A taxonomy with 400 terms means 400 queries and 400
Loop Grids if you let it; cap the widget at what the page actually needs.

### Lazy loading

Turn **Lazy Load Terms** on and first paint only queries and renders the first *N* terms
(default `2`). The rest emit a placeholder that the bundled loader swaps for real content
when it scrolls into view — one request per term, with a 200px pre-load margin, using
`IntersectionObserver`.

Set **Eager-Rendered Terms** to cover your above-the-fold area and everything below
loads on demand, which keeps LCP down on long category pages. The editor and preview
always render everything eagerly so you can design the full layout.

The lazy request sends only the document ID, the widget's element ID and the term ID.
Post type, taxonomy, template, ordering, post count and columns are all re-read
server-side from the widget's saved settings, and the endpoint will only render terms
that widget actually defers. That means it stays correct behind full-page caching, where
a cached nonce would have gone stale and broken every deferred term.

## Developer reference

Three filters, no forking required.

```php
// Narrow, extend, or re-sort the term query.
add_filter( 'elementor_taxonomy_loop_terms_args', function ( $args, $widget ) {
	$args['parent'] = 0; // Top-level terms only.
	return $args;
}, 10, 2 );

// Shape the per-term post query — meta queries, post status, sticky handling…
add_filter( 'elementor_taxonomy_loop_posts_args', function ( $args, $term_id, $widget ) {
	$args['meta_key'] = 'featured';
	return $args;
}, 10, 3 );

// Style handles preloaded so lazily-injected Loop Grids arrive styled.
add_filter( 'elementor_taxonomy_loop_lazy_style_handles', function ( $handles ) {
	$handles[] = 'my-loop-card-styles';
	return $handles;
}, 10, 1 );
```

Both query filters reset `fields` afterwards — the widget needs whole term objects and
bare post IDs — so that one argument is not yours to change. Everything else is.

Other integration points:

- Widget type name: `taxonomy_loop` (stable — saved documents depend on it).
- Stylesheet handle: `taxonomy-loop-style`. Script handle: `elementor-taxonomy-loop-lazy`.
- AJAX action: `elementor_taxonomy_loop_render_term` (`wp_ajax_` and `wp_ajax_nopriv_`).
- Namespace: `Beenacle\ElementorTaxonomyLoop\` autoloaded from `includes/`.

## FAQ

**Does this work without Elementor Pro?**
No. The widget renders Elementor Pro's Loop Grid using a Loop Builder template, and both
are Pro features. It won't register without Pro active.

**Does it work with custom post types and custom taxonomies?**
Yes — any public post type, and any taxonomy registered for it. That is the main use case.

**Does it work with WooCommerce products?**
Yes. Choose `product` as the post type and `product_cat` (or a brand taxonomy) as the
taxonomy, and design your product card in the Loop Builder.

**Can I show a different template per category?**
Not from one widget — one Loop Skin applies to every term. Use two widgets with
**Include Terms** set differently if you need two card designs on one page.

**How do I show only some categories?**
Put their term IDs in **Include Terms**, or exclude the ones you don't want. Term IDs are
visible in the URL when you edit a term in the WordPress admin.

**Why is my category missing?**
Most often **Hide Empty Terms** is on and the term has no posts of the selected post
type, or **Max Terms** is cutting the list short. Check the taxonomy is actually
registered for that post type too.

**Can the term title link to the category archive?**
Not yet with a built-in control. The title is plain text so it stays safe to style; wrap
it yourself with CSS/JS or open a [feature request](https://github.com/beenacle/elementor-taxonomy-loop/issues/new/choose).

**Does it support pagination?**
No. Each term shows up to **Posts Per Term** posts. Grouped-by-term pagination has no
sensible single meaning — link to the term archive for the full list.

**Is it translation-ready?**
Yes. Text domain `elementor-taxonomy-loop`, with a POT file in `languages/`.

**Does it slow pages down?**
It runs one query per rendered term, so cost scales with term count. Use **Max Terms**
and **Lazy Load Terms** on large taxonomies — see
[Performance and lazy loading](#performance-and-lazy-loading).

## Troubleshooting

**"This plugin cannot be activated because required plugins are missing or inactive."**
WordPress 6.5+ enforces the `Requires Plugins` header, and this plugin declares
`elementor` and `elementor-pro`. Both must be installed *and* active, in folders named
exactly `elementor` and `elementor-pro`. Uploading a zip named
`elementor-pro-3.31.zip` creates a folder called `elementor-pro-3.31`, which does not
satisfy the dependency — rename the folder to `elementor-pro`.

**Styling broke after updating to 2.x.**
Custom CSS aimed at `.elementor-widget-container` inside this widget no longer matches.
See [Styling and CSS hooks](#styling-and-css-hooks).

**"Unable to load posts for this term" on lazy-loaded terms.**
The document has to be published for the public endpoint to render from it, and the term
has to still be part of the widget's deferred set. If you changed **Include/Exclude
Terms** or **Max Terms**, clear your page cache so visitors get fresh markup.

**Lazy-loaded grids look unstyled.**
Elementor Pro's grid stylesheet loads with the first grid on the page. The widget
preloads it when lazy loading is on; if a custom card template pulls in its own
stylesheet, add its handle via `elementor_taxonomy_loop_lazy_style_handles`.

**I can't find the widget in the panel.**
It moved to its own **Beenacle** category in 2.0.0. Searching "taxonomy", "category" or
"loop" in the widget panel finds it either way.

## Compatibility

- **Optimized Markup** — supported. The widget implements `has_widget_inner_wrapper()`
  the same way Elementor's own widgets do, so its wrapper follows the site setting and
  stays in step with the common controls Elementor pairs with it. None of the widget's
  own styling depends on that element either way.
- **Full-page caching** — supported, including for lazy loading.
- **Elementor Pro Loop Builder templates** — any template, with dynamic tags.
- **Editor V4 / Atomic elements** — the widget is a classic Elementor widget and renders
  alongside V4 content.

## Contributing

Bug reports and pull requests are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md) for
setup, coding standards and the release process. Security issues go through
[SECURITY.md](SECURITY.md), not the public issue tracker.

## Changelog

See [CHANGELOG.md](CHANGELOG.md). Every release also carries its notes on the
[Releases](https://github.com/beenacle/elementor-taxonomy-loop/releases) page.

## License

GPL v2 or later — see [LICENSE](LICENSE).

## Credits

Built and maintained by [Beenacle](https://beenacle.com). For site-specific help or
custom work, [get in touch](https://beenacle.com/contact-us/).
