<?php
/**
 * Plugin Name:       Elementor Taxonomy Loop Widget
 * Plugin URI:        https://github.com/beenacle/elementor-taxonomy-loop
 * Description:       A powerful Elementor widget for displaying taxonomy terms with custom loop templates.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Beenacle
 * Author URI:        https://beenacle.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       elementor-taxonomy-loop
 * Domain Path:       /languages
 * Update URI:        https://github.com/beenacle/elementor-taxonomy-loop
 *
 * Requires Plugins: elementor, elementor-pro
 * Elementor tested up to: 3.25.0
 * Elementor Pro tested up to: 3.25.0
 */

if (!defined('ABSPATH')) {
  exit;
}

define('ELEMENTOR_TAXONOMY_LOOP_VERSION', '1.2.0');
define('ELEMENTOR_TAXONOMY_LOOP_FILE', __FILE__);
define('ELEMENTOR_TAXONOMY_LOOP_PATH', plugin_dir_path(__FILE__));
define('ELEMENTOR_TAXONOMY_LOOP_URL', plugins_url('/', __FILE__));

function elementor_taxonomy_loop_load_textdomain()
{
  load_plugin_textdomain(
    'elementor-taxonomy-loop',
    false,
    dirname(plugin_basename(ELEMENTOR_TAXONOMY_LOOP_FILE)) . '/languages'
  );
}
add_action('init', 'elementor_taxonomy_loop_load_textdomain');

function elementor_taxonomy_loop_missing_pro_notice()
{
  if (class_exists('\ElementorPro\Plugin')) {
    return;
  }
  printf(
    '<div class="notice notice-error"><p>%s</p></div>',
    esc_html__('Elementor Taxonomy Loop Widget requires Elementor Pro to be installed and active.', 'elementor-taxonomy-loop')
  );
}
add_action('admin_notices', 'elementor_taxonomy_loop_missing_pro_notice');

function elementor_taxonomy_loop_widgets($widgets_manager)
{
  if (!class_exists('\ElementorPro\Plugin')) {
    return;
  }

  $widget_file = ELEMENTOR_TAXONOMY_LOOP_PATH . 'widgets/taxonomy-loop.php';

  if (file_exists($widget_file)) {
    require_once($widget_file);
    $widgets_manager->register(new \Beenacle_Taxonomy_Loop());
  } elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Elementor Taxonomy Loop Widget: Widget file not found at ' . $widget_file);
  }
}
add_action('elementor/widgets/register', 'elementor_taxonomy_loop_widgets');

// Register styles for widgets. Elementor will enqueue them via get_style_depends().
function elementor_taxonomy_loop_widgets_styles()
{
  if (file_exists(ELEMENTOR_TAXONOMY_LOOP_PATH . 'assets/css/taxonomy-loop.css')) {
    wp_register_style(
      'taxonomy-loop-style',
      ELEMENTOR_TAXONOMY_LOOP_URL . 'assets/css/taxonomy-loop.css',
      [],
      ELEMENTOR_TAXONOMY_LOOP_VERSION
    );
  }
}
add_action('elementor/frontend/after_register_styles', 'elementor_taxonomy_loop_widgets_styles');

// Register the lazy-load script. Elementor will enqueue it via get_script_depends()
// only on pages that actually contain the widget.
function elementor_taxonomy_loop_widgets_scripts()
{
  if (file_exists(ELEMENTOR_TAXONOMY_LOOP_PATH . 'assets/js/taxonomy-loop-lazy.js')) {
    wp_register_script(
      'elementor-taxonomy-loop-lazy',
      ELEMENTOR_TAXONOMY_LOOP_URL . 'assets/js/taxonomy-loop-lazy.js',
      [],
      ELEMENTOR_TAXONOMY_LOOP_VERSION,
      true
    );
  }
}
add_action('elementor/frontend/after_register_scripts', 'elementor_taxonomy_loop_widgets_scripts');

// AJAX: render a single term's Loop Grid for lazy-loaded stubs.
function elementor_taxonomy_loop_ajax_render_term()
{
  if (!class_exists('\\Elementor\\Widget_Base') || !class_exists('\\ElementorPro\\Plugin')) {
    wp_send_json_error(['message' => 'Elementor Pro is not active.'], 500);
  }
  if (!class_exists('\\Beenacle_Taxonomy_Loop')) {
    $widget_file = ELEMENTOR_TAXONOMY_LOOP_PATH . 'widgets/taxonomy-loop.php';
    if (!file_exists($widget_file)) {
      wp_send_json_error(['message' => 'Widget unavailable.'], 500);
    }
    require_once($widget_file);
  }
  \Beenacle_Taxonomy_Loop::ajax_render_term();
}
add_action('wp_ajax_elementor_taxonomy_loop_render_term', 'elementor_taxonomy_loop_ajax_render_term');
add_action('wp_ajax_nopriv_elementor_taxonomy_loop_render_term', 'elementor_taxonomy_loop_ajax_render_term');
