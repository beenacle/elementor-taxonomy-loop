<?php
/**
 * Plugin Name:       Elementor Taxonomy Loop Widget
 * Plugin URI:        https://github.com/beenacle/elementor-taxonomy-loop
 * Description:       Group posts by taxonomy term in Elementor Pro. Every category, tag, brand or custom term gets its own heading and its own Loop Grid, rendered from a template you design in the Loop Builder.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * Author:            Beenacle
 * Author URI:        https://beenacle.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       elementor-taxonomy-loop
 * Domain Path:       /languages
 * Update URI:        https://github.com/beenacle/elementor-taxonomy-loop
 *
 * Requires Plugins:  elementor, elementor-pro
 * Elementor tested up to:     4.2.2
 * Elementor Pro tested up to: 4.2.2
 *
 * @package Beenacle\ElementorTaxonomyLoop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'ELEMENTOR_TAXONOMY_LOOP_VERSION', '2.0.0' );
define( 'ELEMENTOR_TAXONOMY_LOOP_FILE', __FILE__ );
define( 'ELEMENTOR_TAXONOMY_LOOP_PATH', plugin_dir_path( __FILE__ ) );
define( 'ELEMENTOR_TAXONOMY_LOOP_URL', plugins_url( '/', __FILE__ ) );

/**
 * Minimal PSR-4 style autoloader for the plugin namespace.
 *
 * Keeps the shipped zip dependency-free — there is no Composer vendor directory
 * to install or to keep in sync with the release artifact.
 *
 * @param string $class_name Fully qualified class name being loaded.
 * @return void
 */
function elementor_taxonomy_loop_autoload( $class_name ) {
	$prefix = 'Beenacle\\ElementorTaxonomyLoop\\';

	if ( 0 !== strpos( $class_name, $prefix ) ) {
		return;
	}

	$relative_class = substr( $class_name, strlen( $prefix ) );
	$path           = ELEMENTOR_TAXONOMY_LOOP_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( is_readable( $path ) ) {
		require_once $path;
	}
}
spl_autoload_register( 'elementor_taxonomy_loop_autoload' );

// GitHub-Releases self-updater (shared across beenacle plugins).
( new \Beenacle\ElementorTaxonomyLoop\GitHubUpdater( ELEMENTOR_TAXONOMY_LOOP_FILE, 'beenacle', 'elementor-taxonomy-loop' ) )->register();

\Beenacle\ElementorTaxonomyLoop\Plugin::instance();
