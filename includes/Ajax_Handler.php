<?php
/**
 * AJAX endpoint that fills in lazily-rendered terms.
 *
 * @package Beenacle\ElementorTaxonomyLoop
 */

namespace Beenacle\ElementorTaxonomyLoop;

use Beenacle\ElementorTaxonomyLoop\Widgets\Taxonomy_Loop;
use Elementor\Plugin as Elementor;
use Elementor\Utils as Elementor_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Renders one term's Loop Grid on demand.
 *
 * The request carries only *which* widget and *which* term to render. Every
 * setting that shapes the output — post type, taxonomy, loop template, ordering,
 * post count, columns — is re-read from the widget's saved settings in the
 * document, and the term has to be one this widget actually defers. That leaves
 * the endpoint with no attacker-controlled surface, so it needs no nonce: the
 * response can only ever be markup the page itself would have rendered anyway.
 * Dropping the nonce is also what makes lazy loading survive full-page caching,
 * where a cached nonce goes stale and every deferred term would fail to load.
 */
final class Ajax_Handler {

	/**
	 * The `admin-ajax.php` action name.
	 */
	const ACTION = 'elementor_taxonomy_loop_render_term';

	/**
	 * Hook the endpoint up for both logged-in and anonymous visitors.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'wp_ajax_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( __CLASS__, 'handle' ) );
	}

	/**
	 * Handle a lazy-render request.
	 *
	 * @return void
	 */
	public static function handle() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Public read-only endpoint; see the class docblock.
		$post_id    = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$term_id    = isset( $_POST['term_id'] ) ? absint( wp_unslash( $_POST['term_id'] ) ) : 0;
		$element_id = isset( $_POST['element_id'] )
			? preg_replace( '/[^A-Za-z0-9_-]/', '', sanitize_text_field( wp_unslash( $_POST['element_id'] ) ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( $post_id <= 0 || $term_id <= 0 || '' === $element_id ) {
			self::fail( __( 'Invalid request.', 'elementor-taxonomy-loop' ), 400 );
		}

		if ( 'publish' !== get_post_status( $post_id ) && ! current_user_can( 'read_post', $post_id ) ) {
			self::fail( __( 'This content is not available.', 'elementor-taxonomy-loop' ), 403 );
		}

		$widget = self::get_widget_instance( $post_id, $element_id );

		if ( null === $widget ) {
			self::fail( __( 'Widget not found.', 'elementor-taxonomy-loop' ), 404 );
		}

		$html = $widget->render_lazy_term( $term_id );

		if ( null === $html ) {
			self::fail( __( 'This term is not available.', 'elementor-taxonomy-loop' ), 404 );
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Rebuild the widget instance from the document it lives in.
	 *
	 * @param int    $post_id    Document the widget belongs to.
	 * @param string $element_id Elementor element ID of the widget.
	 * @return Taxonomy_Loop|null
	 */
	private static function get_widget_instance( $post_id, $element_id ) {
		if ( ! class_exists( Elementor::class ) ) {
			return null;
		}

		$document = Elementor::$instance->documents->get( $post_id );

		if ( ! $document ) {
			return null;
		}

		$element_data = Elementor_Utils::find_element_recursive( $document->get_elements_data(), $element_id );

		if ( ! is_array( $element_data )
			|| 'widget' !== ( $element_data['elType'] ?? '' )
			|| Taxonomy_Loop::WIDGET_NAME !== ( $element_data['widgetType'] ?? '' ) ) {
			return null;
		}

		$widget = Elementor::$instance->elements_manager->create_element_instance( $element_data );

		return $widget instanceof Taxonomy_Loop ? $widget : null;
	}

	/**
	 * Send a JSON error response and stop.
	 *
	 * @param string $message Human-readable reason.
	 * @param int    $status  HTTP status code.
	 * @return void
	 */
	private static function fail( $message, $status ) {
		wp_send_json_error( array( 'message' => $message ), $status );
	}
}
