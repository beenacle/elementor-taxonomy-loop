# Security policy

## Supported versions

Only the latest release receives security fixes. Update before reporting an issue
you found on an older version.

| Version | Supported |
| --- | --- |
| 2.x | Yes |
| 1.x | No |

## Reporting a vulnerability

Please **do not** open a public issue for a security problem.

Use GitHub's [private vulnerability reporting](https://github.com/beenacle/elementor-taxonomy-loop/security/advisories/new),
or email **security@beenacle.com**. Include the plugin version, WordPress and
Elementor Pro versions, and the steps to reproduce.

You can expect an acknowledgement within a few working days and an assessment of
impact and a fix timeline after that. Fixes ship as a normal release; the advisory
is published once sites have had a reasonable window to update.

## Scope notes

The lazy-load endpoint (`wp_ajax[_nopriv]_elementor_taxonomy_loop_render_term`) is
deliberately public and unauthenticated. It accepts only a document ID, an element
ID and a term ID; every setting that shapes its output is read server-side from the
widget's saved settings, and it will only render terms that widget already defers.
It therefore returns nothing a visitor could not obtain by scrolling the page. If
you find a way to make it return something else, that is in scope and we want to
hear about it.
