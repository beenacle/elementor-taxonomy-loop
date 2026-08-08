<?php
/**
 * Plugin bootstrap: requirement checks, i18n, asset and widget registration.
 *
 * @package Beenacle\ElementorTaxonomyLoop
 */

namespace Beenacle\ElementorTaxonomyLoop;

use Beenacle\ElementorTaxonomyLoop\Widgets\Taxonomy_Loop;
use Elementor\Elements_Manager;
use Elementor\Widgets_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main plugin controller.
 *
 * Follows Elementor's recommended add-on bootstrap: verify every requirement on
 * `plugins_loaded`, surface a single actionable admin notice when one is not met,
 * and only wire up the editor/frontend integration once the environment is sane.
 */
final class Plugin {

	/**
	 * Oldest Elementor release this add-on supports.
	 *
	 * 3.25 is the floor because that is where `has_widget_inner_wrapper()` — the
	 * hook this widget uses to opt into Elementor's optimized DOM — was introduced.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.25.0';

	/**
	 * Oldest Elementor Pro release this add-on supports.
	 *
	 * The widget renders Elementor Pro's Loop Grid, so Pro is a hard dependency.
	 */
	const MINIMUM_ELEMENTOR_PRO_VERSION = '3.25.0';

	/**
	 * Oldest PHP version this add-on supports.
	 */
	const MINIMUM_PHP_VERSION = '7.4';

	/**
	 * Editor panel category the widget is filed under.
	 */
	const WIDGET_CATEGORY = 'beenacle';

	/**
	 * Registered stylesheet handle.
	 *
	 * Kept stable since 1.x so sites that dequeue or override it keep working.
	 */
	const STYLE_HANDLE = 'taxonomy-loop-style';

	/**
	 * Registered lazy-loader script handle.
	 */
	const SCRIPT_HANDLE = 'elementor-taxonomy-loop-lazy';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Message explaining why the plugin is inert, empty when everything is fine.
	 *
	 * @var string
	 */
	private $requirements_notice = '';

	/**
	 * Retrieve — and on first call create — the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Wire up the earliest hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
	}

	/**
	 * Load the plugin translations.
	 *
	 * Runs on `init` so no translation function is called before WordPress is
	 * ready to resolve one.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'elementor-taxonomy-loop',
			false,
			dirname( plugin_basename( ELEMENTOR_TAXONOMY_LOOP_FILE ) ) . '/languages'
		);
	}

	/**
	 * Verify the environment and, when it checks out, register the integration.
	 *
	 * @return void
	 */
	public function on_plugins_loaded() {
		$this->requirements_notice = $this->check_requirements();

		if ( '' !== $this->requirements_notice ) {
			add_action( 'admin_notices', array( $this, 'print_requirements_notice' ) );

			return;
		}

		add_action( 'elementor/elements/categories_registered', array( $this, 'register_widget_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );

		Ajax_Handler::register();
	}

	/**
	 * Build the requirement failure message, if any.
	 *
	 * @return string Empty string when every requirement is met.
	 */
	private function check_requirements() {
		$plugin_name = esc_html__( 'Elementor Taxonomy Loop Widget', 'elementor-taxonomy-loop' );

		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			return $this->requirement_message( $plugin_name, 'PHP', self::MINIMUM_PHP_VERSION, true );
		}

		if ( ! did_action( 'elementor/loaded' ) ) {
			return $this->missing_dependency_message( $plugin_name, 'Elementor' );
		}

		if ( ! defined( 'ELEMENTOR_VERSION' ) || version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '<' ) ) {
			return $this->requirement_message( $plugin_name, 'Elementor', self::MINIMUM_ELEMENTOR_VERSION, false );
		}

		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) || ! class_exists( '\ElementorPro\Plugin' ) ) {
			return $this->missing_dependency_message( $plugin_name, 'Elementor Pro' );
		}

		if ( version_compare( ELEMENTOR_PRO_VERSION, self::MINIMUM_ELEMENTOR_PRO_VERSION, '<' ) ) {
			return $this->requirement_message( $plugin_name, 'Elementor Pro', self::MINIMUM_ELEMENTOR_PRO_VERSION, false );
		}

		return '';
	}

	/**
	 * Compose a "dependency is missing" message.
	 *
	 * @param string $plugin_name This plugin's display name.
	 * @param string $dependency  Display name of the missing dependency.
	 * @return string
	 */
	private function missing_dependency_message( $plugin_name, $dependency ) {
		return sprintf(
			/* translators: 1: this plugin's name, 2: the name of the required plugin. */
			esc_html__( '%1$s requires %2$s to be installed and activated.', 'elementor-taxonomy-loop' ),
			$plugin_name,
			$dependency
		);
	}

	/**
	 * Compose a "dependency is too old" message.
	 *
	 * @param string $plugin_name This plugin's display name.
	 * @param string $dependency  Display name of the outdated dependency.
	 * @param string $version     Minimum supported version of that dependency.
	 * @param bool   $is_platform Whether the dependency is a platform (PHP) rather than a plugin.
	 * @return string
	 */
	private function requirement_message( $plugin_name, $dependency, $version, $is_platform ) {
		if ( $is_platform ) {
			return sprintf(
				/* translators: 1: this plugin's name, 2: platform name, 3: minimum version. */
				esc_html__( '%1$s requires %2$s version %3$s or greater.', 'elementor-taxonomy-loop' ),
				$plugin_name,
				$dependency,
				$version
			);
		}

		return sprintf(
			/* translators: 1: this plugin's name, 2: the required plugin's name, 3: minimum version. */
			esc_html__( '%1$s requires %2$s version %3$s or greater. Please update %2$s.', 'elementor-taxonomy-loop' ),
			$plugin_name,
			$dependency,
			$version
		);
	}

	/**
	 * Print the requirement notice to users who can act on it.
	 *
	 * @return void
	 */
	public function print_requirements_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html( $this->requirements_notice )
		);
	}

	/**
	 * Add the add-on's own category to the editor panel.
	 *
	 * @param Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_widget_category( $elements_manager ) {
		$elements_manager->add_category(
			self::WIDGET_CATEGORY,
			array(
				'title' => esc_html__( 'Beenacle', 'elementor-taxonomy-loop' ),
				'icon'  => 'eicon-plug',
			)
		);
	}

	/**
	 * Register the widget with Elementor.
	 *
	 * @param Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		$widgets_manager->register( new Taxonomy_Loop() );
	}

	/**
	 * Register the widget stylesheet.
	 *
	 * Elementor enqueues it on demand through the widget's `get_style_depends()`,
	 * so pages without the widget never pay for it.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			self::STYLE_HANDLE,
			ELEMENTOR_TAXONOMY_LOOP_URL . 'assets/css/taxonomy-loop.css',
			array(),
			ELEMENTOR_TAXONOMY_LOOP_VERSION
		);
	}

	/**
	 * Register the lazy-loader script and its runtime configuration.
	 *
	 * @return void
	 */
	public function register_scripts() {
		wp_register_script(
			self::SCRIPT_HANDLE,
			ELEMENTOR_TAXONOMY_LOOP_URL . 'assets/js/taxonomy-loop-lazy.js',
			array(),
			ELEMENTOR_TAXONOMY_LOOP_VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'ElementorTaxonomyLoop',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => Ajax_Handler::ACTION,
				'i18n'    => array(
					'error' => esc_html__( 'Unable to load posts for this term.', 'elementor-taxonomy-loop' ),
				),
			)
		);
	}
}
