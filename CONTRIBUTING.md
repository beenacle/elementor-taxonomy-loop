# Contributing

Thanks for taking the time. Issues and pull requests are both welcome.

## Local setup

The plugin ships without runtime dependencies — clone it straight into
`wp-content/plugins/elementor-taxonomy-loop/` on a site running Elementor and
Elementor Pro. The folder name matters: WordPress expects a stable slug, so don't
leave a commit hash on the end of it.

Development tooling is Composer-only and never ends up in the release zip:

```bash
composer install        # PHP_CodeSniffer + WordPress standards + PHPCompatibility
composer run-script cs  # check coding standards
composer run-script cbf # auto-fix what can be auto-fixed
composer run-script pot # regenerate languages/elementor-taxonomy-loop.pot
```

## What CI checks

`.github/workflows/lint.yml` runs on every push and pull request:

* `php -l` on every PHP file, across PHP 7.4 → 8.4.
* PHP_CodeSniffer against the `WordPress` standard plus `PHPCompatibility`
  (see `phpcs.xml.dist`).
* `node --check` on the shipped JavaScript.
* A check that `languages/elementor-taxonomy-loop.pot` matches the source. If you
  add, remove or reword a translatable string, run `composer run-script pot` and
  commit the result.

`includes/GitHubUpdater.php` is excluded from the coding-standards run on purpose:
it is shared byte-for-byte across the beenacle plugins apart from its namespace
line, so reformatting this copy would fork it from the others.

## Conventions

* Widget type name (`taxonomy_loop`) and control IDs are persisted inside saved
  Elementor documents. Renaming either breaks existing pages — don't.
* CSS class names the widget emits are a public contract; people write custom CSS
  against them. Add classes freely, rename them only in a major release.
* Never reference `.elementor-widget-container`. Elementor removes it when the
  "Optimized Markup" experiment is active, which is the default on new sites.
  Style through `.e-taxonomy-loop` instead.
* Configuration errors belong in the editor, not on the frontend. Visitors should
  never see a builder message.

## Releasing

`.github/workflows/release.yml` handles it. Bump `Version:` in
`elementor-taxonomy-loop.php`, add a matching `## X.Y.Z` section to `CHANGELOG.md`,
and push to `main` — the workflow tags `vX.Y.Z`, builds the zip and publishes the
release, which the in-plugin updater then offers to installed sites.

Two things to know about the changelog:

* Release notes are extracted from the `## X.Y.Z` section. The extractor stops at
  the next line starting with `##`, so **don't use `###` sub-headings inside a
  release section** — they truncate the notes. Use bold labels instead.
* Put `[skip release]` in a commit message to push without cutting a release.
