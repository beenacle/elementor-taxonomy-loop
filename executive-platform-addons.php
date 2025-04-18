<?php

/**
 * Plugin Name: Executive Platform Addons
 * Description: Customized elementor widgets developed specifically for Executive Platform.
 * Version:     1.0.0
 * Author:      Beenacle Technologies - Mohsin Shakeel
 * Text Domain: executive-elementor-addons
 *
 * Requires Plugins: elementor
 * Elementor tested up to: 3.25.0
 * Elementor Pro tested up to: 3.25.0
 */

//Register widgets....

function executive_platform_elementor_widgets($widgets_manager)
{
  //Widget Files
  require_once(__DIR__ . '/widgets/ep-taxanomy-loop.php');


  //Widget Registrations
  $widgets_manager->register(new \Ep_Taxanomy_Loop());
}
add_action('elementor/widgets/register', 'executive_platform_elementor_widgets');


//Register scripts and styles for widgets....

function executive_platform_widgets_dependencies()
{
  /* Scripts */
  // wp_register_script('ep-taxanomy-loop-script', plugins_url('assets/js/ep-taxanomy-loop.js', __FILE__), ['jquery', 'elementor-frontend'], '1.0.0', true);
  // ✅ Enqueue the Script
  // wp_enqueue_script('ep-taxanomy-loop-script');

  /* Styles */
  wp_register_style('ep-taxanomy-loop-style', plugins_url('assets/css/ep-taxanomy-loop.css', __FILE__));
  // ✅ Enqueue the style
  wp_enqueue_style('ep-taxanomy-loop-style');
}
add_action('elementor/frontend/before_enqueue_styles', 'executive_platform_widgets_dependencies');
