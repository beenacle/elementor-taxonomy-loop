<?php
/**
 * Plugin Name: Elementor Taxonomy Loop Widget
 * Description: A powerful Elementor widget for displaying taxonomy terms with custom loop templates.
 * Version:     1.0.0
 * Author:      Beenacle Technologies
 * Author URI:  https://beenacle.com
 * Text Domain: elementor-taxonomy-loop
 *
 * Requires Plugins: elementor
 * Elementor tested up to: 3.25.0
 * Elementor Pro tested up to: 3.25.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('ELEMENTOR_TAXONOMY_LOOP_VERSION', '1.0.0');
define('ELEMENTOR_TAXONOMY_LOOP_FILE', __FILE__);
define('ELEMENTOR_TAXONOMY_LOOP_PATH', plugin_dir_path(__FILE__));
define('ELEMENTOR_TAXONOMY_LOOP_URL', plugins_url('/', __FILE__));

//Register widgets....
function elementor_taxonomy_loop_widgets($widgets_manager)
{
    //Widget Files
    $widget_file = ELEMENTOR_TAXONOMY_LOOP_PATH . 'widgets/taxonomy-loop.php';

    if (file_exists($widget_file)) {
        require_once($widget_file);
        $widgets_manager->register(new \Taxonomy_Loop());
    } else {
        error_log('Elementor Taxonomy Loop Widget: Widget file not found at ' . $widget_file);
    }
}
add_action('elementor/widgets/register', 'elementor_taxonomy_loop_widgets');

//Register scripts for widgets....
function elementor_taxonomy_loop_widgets_scripts()
{
    $script_path = ELEMENTOR_TAXONOMY_LOOP_URL . 'assets/js/taxonomy-loop.js';
    if (file_exists(ELEMENTOR_TAXONOMY_LOOP_PATH . 'assets/js/taxonomy-loop.js')) {
        wp_register_script(
            'taxonomy-loop-script',
            $script_path,
            ['jquery', 'elementor-frontend'],
            ELEMENTOR_TAXONOMY_LOOP_VERSION,
            true
        );
        wp_enqueue_script('taxonomy-loop-script');
    }
}
add_action('elementor/frontend/after_enqueue_scripts', 'elementor_taxonomy_loop_widgets_scripts');

//Register styles for widgets....
function elementor_taxonomy_loop_widgets_styles()
{
    $style_path = ELEMENTOR_TAXONOMY_LOOP_URL . 'assets/css/taxonomy-loop.css';
    if (file_exists(ELEMENTOR_TAXONOMY_LOOP_PATH . 'assets/css/taxonomy-loop.css')) {
        wp_register_style(
            'taxonomy-loop-style',
            $style_path,
            [],
            ELEMENTOR_TAXONOMY_LOOP_VERSION
        );
        wp_enqueue_style('taxonomy-loop-style');
    }
}
add_action('elementor/frontend/before_enqueue_styles', 'elementor_taxonomy_loop_widgets_styles');
