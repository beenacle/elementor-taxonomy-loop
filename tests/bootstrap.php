<?php
/**
 * PHPUnit bootstrap for Elementor Taxonomy Loop Widget.
 *
 * - Pulls in Composer's autoloader (PHPUnit + Brain Monkey + Mockery).
 * - Loads minimal stubs for Elementor / Elementor Pro classes so the
 *   widget file can be required without the real plugins installed.
 * - Defines `ABSPATH` so the widget's guard at the top of the file
 *   doesn't exit.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('ABSPATH')) {
  define('ABSPATH', __DIR__ . '/');
}
if (!defined('WP_DEBUG')) {
  define('WP_DEBUG', false);
}
if (!defined('MINUTE_IN_SECONDS')) {
  define('MINUTE_IN_SECONDS', 60);
}

require_once __DIR__ . '/stubs/elementor.php';
require_once __DIR__ . '/stubs/elementor-pro.php';

// Load the production widget class under test. Brain Monkey handles
// the WP functions (get_posts, wp_cache_*, etc.) inside each test.
require_once __DIR__ . '/../widgets/taxonomy-loop.php';
