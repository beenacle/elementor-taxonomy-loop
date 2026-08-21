<?php
/**
 * The Taxonomy Loop widget.
 *
 * @package Beenacle\ElementorTaxonomyLoop
 */

namespace Beenacle\ElementorTaxonomyLoop\Widgets;

use Beenacle\ElementorTaxonomyLoop\Plugin as Addon;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Plugin as Elementor;
use Elementor\Utils as Elementor_Utils;
use Elementor\Widget_Base;
use ElementorPro\Modules\LoopBuilder\Documents\Loop as Loop_Document;
use ElementorPro\Modules\QueryControl\Controls\Template_Query;
use ElementorPro\Modules\QueryControl\Module as Query_Control_Module;
use ElementorPro\Plugin as Elementor_Pro;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Renders one Elementor Pro Loop Grid per taxonomy term.
 *
 * Each term becomes its own titled section followed by a Loop Grid built from a
 * Loop Builder template, which is what Elementor Pro's stock Loop Grid cannot do
 * on its own: it renders a single flat query rather than one group per term.
 */
class Taxonomy_Loop extends Widget_Base {

	/**
	 * Widget type as persisted in saved Elementor documents. Never rename this.
	 */
	const WIDGET_NAME = 'taxonomy_loop';

	/**
	 * `orderby` values accepted for the term query.
	 */
	const TERM_ORDERBY_WHITELIST = array( 'name', 'id', 'slug', 'menu_order', 'count', 'include' );

	/**
	 * `orderby` values accepted for the per-term post query.
	 */
	const POST_ORDERBY_WHITELIST = array( 'date', 'modified', 'title', 'ID', 'menu_order', 'rand' );

	/**
	 * Sort directions accepted for both queries.
	 */
	const ORDER_WHITELIST = array( 'ASC', 'DESC' );

	/**
	 * HTML tags the term title may be rendered with.
	 */
	const TITLE_TAG_WHITELIST = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p', 'span' );

	/**
	 * Widget type name.
	 *
	 * @return string
	 */
	public function get_name() {
		return self::WIDGET_NAME;
	}

	/**
	 * Widget label shown in the editor panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Taxonomy Loop', 'elementor-taxonomy-loop' );
	}

	/**
	 * Widget panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-loop-builder';
	}

	/**
	 * Editor panel categories this widget belongs to.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( Addon::WIDGET_CATEGORY );
	}

	/**
	 * Search terms that surface the widget in the editor's widget search.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array(
			'taxonomy',
			'loop',
			'loop grid',
			'category',
			'categories',
			'term',
			'terms',
			'tag',
			'grid',
			'posts',
			'archive',
			'group',
			'custom post type',
			'woocommerce',
			'product category',
		);
	}

	/**
	 * Documentation link shown from the widget's panel.
	 *
	 * @return string
	 */
	public function get_custom_help_url() {
		return 'https://github.com/beenacle/elementor-taxonomy-loop#usage';
	}

	/**
	 * Follow the site's "Optimized Markup" setting, exactly as core widgets do.
	 *
	 * Elementor swaps the whole common-controls stack based on this experiment:
	 * with it off, the Advanced tab's background, border, padding and transform
	 * controls all target `.elementor-widget-container`. Hard-coding this to false
	 * would delete that element while Elementor kept styling it, silently breaking
	 * the Advanced tab. So the wrapper tracks the experiment — and this widget's own
	 * styling never touches it, hooking `.e-taxonomy-loop` instead, which is present
	 * either way.
	 *
	 * @return bool
	 */
	public function has_widget_inner_wrapper(): bool {
		return ! Elementor::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	/**
	 * Stylesheets this widget needs.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( Addon::STYLE_HANDLE );
	}

	/**
	 * Scripts this widget needs.
	 *
	 * The lazy loader is only useful when lazy loading is switched on, so pages
	 * that render every term server-side ship no JavaScript at all.
	 *
	 * Elementor also calls this on the registration ("type") instance, which is
	 * constructed with no element data at all -- the editor preview does exactly
	 * that on every load, via Widgets_Manager::enqueue_widgets_scripts(). Reading
	 * settings there reaches Controls_Stack::sanitize_settings( null ) and fatals,
	 * so the type instance always declares the script. That is what the editor
	 * wants anyway: toggling Lazy Load in the panel cannot re-run
	 * wp_enqueue_script(), so the handle has to already be on the preview page.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		if ( ! $this->is_type_instance() && 'yes' !== $this->get_settings( 'lazy_load' ) ) {
			return array();
		}

		return array( Addon::SCRIPT_HANDLE );
	}

	/**
	 * Register the widget's controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$post_types = $this->get_selectable_post_types();

		$this->register_content_controls( $post_types );
		$this->register_items_style_controls();
		$this->register_term_style_controls();
		$this->register_loop_style_controls();
		$this->register_divider_style_controls();
	}

	/**
	 * Public post types offered by the Post Type control.
	 *
	 * Prefers Elementor Pro's helper so the list matches the rest of the editor,
	 * and falls back to core if Pro ever moves it.
	 *
	 * @return array<string,string> Post type slug => label.
	 */
	private function get_selectable_post_types() {
		if ( class_exists( '\ElementorPro\Core\Utils' ) && method_exists( '\ElementorPro\Core\Utils', 'get_public_post_types' ) ) {
			return \ElementorPro\Core\Utils::get_public_post_types();
		}

		$post_types = array();

		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) {
			$post_types[ $post_type->name ] = $post_type->label;
		}

		return $post_types;
	}

	/**
	 * Taxonomies registered against at least one public post type.
	 *
	 * @param array<string,string> $post_types Public post types keyed by slug.
	 * @return array<string,string> Taxonomy slug => label with slug suffix.
	 */
	private function get_selectable_taxonomies( array $post_types ) {
		$taxonomies = array();

		foreach ( array_keys( $post_types ) as $post_type ) {
			foreach ( get_object_taxonomies( $post_type, 'objects' ) as $taxonomy ) {
				if ( ! isset( $taxonomies[ $taxonomy->name ] ) ) {
					$taxonomies[ $taxonomy->name ] = $taxonomy->label . ' (' . $taxonomy->name . ')';
				}
			}
		}

		return $taxonomies;
	}

	/**
	 * Register the Content tab.
	 *
	 * @param array<string,string> $post_types Public post types keyed by slug.
	 * @return void
	 */
	private function register_content_controls( array $post_types ) {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => esc_html__( 'Select Content', 'elementor-taxonomy-loop' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'post_type',
			array(
				'label'   => esc_html__( 'Select Post Type', 'elementor-taxonomy-loop' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => $post_types,
			)
		);

		$this->add_control(
			'taxonomy',
			array(
				'label'       => esc_html__( 'Select Taxonomy', 'elementor-taxonomy-loop' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'category',
				'options'     => $this->get_selectable_taxonomies( $post_types ),
				'description' => esc_html__( 'Must be a taxonomy registered for the selected post type.', 'elementor-taxonomy-loop' ),
			)
		);

		$this->add_control( 'loop_skin', $this->get_loop_skin_control_args() );

		$this->add_control(
			'hide_empty',
			array(
				'label'        => esc_html__( 'Hide Empty Terms', 'elementor-taxonomy-loop' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'elementor-taxonomy-loop' ),
				'label_off'    => esc_html__( 'No', 'elementor-taxonomy-loop' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'loop_divider',
			array(
				'label'        => esc_html__( 'Show Divider', 'elementor-taxonomy-loop' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'elementor-taxonomy-loop' ),
				'label_off'    => esc_html__( 'Hide', 'elementor-taxonomy-loop' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'include_terms',
			array(
				'label'       => esc_html__( 'Include Terms (IDs)', 'elementor-taxonomy-loop' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => '12, 34, 56',
				'description' => esc_html__( 'Comma or space separated term IDs. When set, Exclude Terms is ignored.', 'elementor-taxonomy-loop' ),
				'ai'          => array(
					'active' => false,
				),
			)
		);

		$this->add_control(
			'exclude_terms',
			array(
				'label'       => esc_html__( 'Exclude Terms (IDs)', 'elementor-taxonomy-loop' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => '78, 90',
				'ai'          => array(
					'active' => false,
				),
			)
		);

		$this->add_control(
			'terms_limit',
			array(
				'label'       => esc_html__( 'Max Terms', 'elementor-taxonomy-loop' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'step'        => 1,
				'description' => esc_html__( 'Cap how many terms render. Set 0 for every term — avoid that on taxonomies with hundreds of terms.', 'elementor-taxonomy-loop' ),
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Order Terms By', 'elementor-taxonomy-loop' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'name',
				'options' => array(
					'name'       => esc_html__( 'Name', 'elementor-taxonomy-loop' ),
					'id'         => esc_html__( 'ID', 'elementor-taxonomy-loop' ),
					'slug'       => esc_html__( 'Slug', 'elementor-taxonomy-loop' ),
					'menu_order' => esc_html__( 'Menu Order', 'elementor-taxonomy-loop' ),
					'count'      => esc_html__( 'Post Count', 'elementor-taxonomy-loop' ),
					'include'    => esc_html__( 'Include Order', 'elementor-taxonomy-loop' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Order Direction', 'elementor-taxonomy-loop' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'ASC',
				'options' => array(
					'ASC'  => esc_html__( 'Ascending', 'elementor-taxonomy-loop' ),
					'DESC' => esc_html__( 'Descending', 'elementor-taxonomy-loop' ),
				),
			)
		);

		$this->add_control(
			'post_orderby',
			array(
				'label'     => esc_html__( 'Order Posts By', 'elementor-taxonomy-loop' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'date',
				'separator' => 'before',
				'options'   => array(
					'date'       => esc_html__( 'Date', 'elementor-taxonomy-loop' ),
					'modified'   => esc_html__( 'Last Modified', 'elementor-taxonomy-loop' ),
					'title'      => esc_html__( 'Title', 'elementor-taxonomy-loop' ),
					'ID'         => esc_html__( 'ID', 'elementor-taxonomy-loop' ),
					'menu_order' => esc_html__( 'Menu Order', 'elementor-taxonomy-loop' ),
					'rand'       => esc_html__( 'Random', 'elementor-taxonomy-loop' ),
				),
			)
		);

		$this->add_control(
			'post_order',
			array(
				'label'   => esc_html__( 'Post Order Direction', 'elementor-taxonomy-loop' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'ASC'  => esc_html__( 'Ascending', 'elementor-taxonomy-loop' ),
					'DESC' => esc_html__( 'Descending', 'elementor-taxonomy-loop' ),
				),
			)
		);

		$this->add_control(
			'posts_per_term',
			array(
				'label'       => esc_html__( 'Posts Per Term', 'elementor-taxonomy-loop' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 6,
				'min'         => -1,
				'step'        => 1,
				'description' => esc_html__( 'Set -1 to show all posts.', 'elementor-taxonomy-loop' ),
			)
		);

		$this->add_control(
			'lazy_load',
			array(
				'label'        => esc_html__( 'Lazy Load Terms', 'elementor-taxonomy-loop' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'elementor-taxonomy-loop' ),
				'label_off'    => esc_html__( 'Off', 'elementor-taxonomy-loop' ),
				'return_value' => 'yes',
				'default'      => 'no',
				'separator'    => 'before',
				'description'  => esc_html__( 'Render terms beyond the eager count only when they scroll into view.', 'elementor-taxonomy-loop' ),
			)
		);

		$this->add_control(
			'eager_terms_count',
			array(
				'label'       => esc_html__( 'Eager-Rendered Terms', 'elementor-taxonomy-loop' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 2,
				'min'         => 1,
				'step'        => 1,
				'description' => esc_html__( 'Number of terms rendered server-side on first paint. The rest are fetched lazily when visible.', 'elementor-taxonomy-loop' ),
				'condition'   => array(
					'lazy_load' => 'yes',
				),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'     => esc_html__( 'Title HTML Tag', 'elementor-taxonomy-loop' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'separator' => 'before',
				'options'   => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'p'    => 'p',
					'span' => 'span',
				),
			)
		);

		$this->add_control(
			'title_prefix',
			array(
				'label'       => esc_html__( 'Title Prefix', 'elementor-taxonomy-loop' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Enter text to appear before the title', 'elementor-taxonomy-loop' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'title_suffix',
			array(
				'label'       => esc_html__( 'Title Suffix', 'elementor-taxonomy-loop' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Enter text to appear after the title', 'elementor-taxonomy-loop' ),
				'label_block' => true,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Arguments for the Loop Skin (template) control.
	 *
	 * Falls back to a plain number input if Elementor Pro's template query control
	 * is ever renamed, so the editor keeps working instead of fatalling.
	 *
	 * @return array<string,mixed>
	 */
	private function get_loop_skin_control_args() {
		$label = esc_html__( 'Select Loop Skin', 'elementor-taxonomy-loop' );

		if ( ! class_exists( Template_Query::class ) || ! class_exists( Query_Control_Module::class ) || ! class_exists( Loop_Document::class ) ) {
			return array(
				'label'       => $label,
				'type'        => Controls_Manager::NUMBER,
				'description' => esc_html__( 'Enter the ID of a Loop Builder template.', 'elementor-taxonomy-loop' ),
			);
		}

		return array(
			'label'        => $label,
			'type'         => Template_Query::CONTROL_ID,
			'autocomplete' => array(
				'object' => Query_Control_Module::QUERY_OBJECT_LIBRARY_TEMPLATE,
				'query'  => array(),
			),
			'actions'      => array(
				'new'  => array(
					'visible'         => true,
					'document_config' => array(
						'type' => Loop_Document::get_type(),
					),
					'after_action'    => 'redirect',
				),
				'edit' => array(
					'visible'      => true,
					'after_action' => 'redirect',
				),
			),
		);
	}

	/**
	 * Register the per-term wrapper style controls.
	 *
	 * @return void
	 */
	private function register_items_style_controls() {
		$this->start_controls_section(
			'main_category_style',
			array(
				'label' => esc_html__( 'Items Settings', 'elementor-taxonomy-loop' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'category_gap',
			array(
				'label'      => esc_html__( 'Gap Between Terms', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .e-taxonomy-loop' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'content_gap',
			array(
				'label'      => esc_html__( 'Content Gap', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .taxonomy-posts .posts-list' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'category_border',
				'selector' => '{{WRAPPER}} .taxonomy-posts',
			)
		);

		$this->add_responsive_control(
			'category_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .taxonomy-posts' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array(
					'category_border_border!' => 'none',
				),
			)
		);

		$this->add_responsive_control(
			'category_padding',
			array(
				'label'      => esc_html__( 'Padding', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .taxonomy-posts' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the term title style controls.
	 *
	 * @return void
	 */
	private function register_term_style_controls() {
		$this->start_controls_section(
			'term_style',
			array(
				'label' => esc_html__( 'Term Title', 'elementor-taxonomy-loop' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'term_border',
				'selector' => '{{WRAPPER}} .taxonomy-posts .term-content',
			)
		);

		$this->add_responsive_control(
			'term_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .taxonomy-posts .term-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array(
					'term_border_border!' => 'none',
				),
			)
		);

		$this->add_responsive_control(
			'term_padding',
			array(
				'label'      => esc_html__( 'Padding', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .taxonomy-posts .term-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'label'    => esc_html__( 'Title', 'elementor-taxonomy-loop' ),
				'name'     => 'term_title_typography',
				'selector' => '{{WRAPPER}} .term-content .term-title',
			)
		);

		$this->add_control(
			'term_title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'elementor-taxonomy-loop' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .term-content .term-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'term_title_alignment',
			array(
				'label'     => esc_html__( 'Alignment', 'elementor-taxonomy-loop' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'left',
				'toggle'    => true,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'elementor-taxonomy-loop' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'elementor-taxonomy-loop' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'elementor-taxonomy-loop' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .term-content .term-title' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the Loop Grid style controls.
	 *
	 * @return void
	 */
	private function register_loop_style_controls() {
		$this->start_controls_section(
			'loop_style',
			array(
				'label' => esc_html__( 'Loop Controls', 'elementor-taxonomy-loop' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Loop Columns', 'elementor-taxonomy-loop' ),
				'type'           => Controls_Manager::NUMBER,
				'default'        => 3,
				'tablet_default' => 2,
				'mobile_default' => 1,
				'min'            => 1,
				'max'            => 12,
				'separator'      => 'before',
				'condition'      => array(
					'loop_skin!' => '',
				),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'      => esc_html__( 'Columns Gap', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px'  => array( 'max' => 100 ),
					'em'  => array( 'max' => 10 ),
					'rem' => array( 'max' => 10 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .posts-list .elementor-widget-loop-grid' => '--grid-column-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'      => esc_html__( 'Row Gap', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px'  => array( 'max' => 100 ),
					'em'  => array( 'max' => 10 ),
					'rem' => array( 'max' => 10 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .posts-list .elementor-widget-loop-grid' => '--grid-row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'equal_height',
			array(
				'label'        => esc_html__( 'Equal height', 'elementor-taxonomy-loop' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_off'    => esc_html__( 'Off', 'elementor-taxonomy-loop' ),
				'label_on'     => esc_html__( 'On', 'elementor-taxonomy-loop' ),
				'return_value' => 'yes',
				'selectors'    => array(
					'{{WRAPPER}} .posts-list .elementor-widget-loop-grid .elementor-loop-container' => 'grid-auto-rows: 1fr;',
					// `.elementor-section-wrap` exists only when editing the loop template.
					'{{WRAPPER}} .posts-list .elementor-widget-loop-grid .e-loop-item > .elementor-section,
					 {{WRAPPER}} .posts-list .elementor-widget-loop-grid .e-loop-item > .elementor-section > .elementor-container,
					 {{WRAPPER}} .posts-list .elementor-widget-loop-grid .e-loop-item > .e-con,
					 {{WRAPPER}} .posts-list .elementor-widget-loop-grid .e-loop-item .elementor-section-wrap > .e-con' => 'height: 100%;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'label'    => esc_html__( 'Not Found', 'elementor-taxonomy-loop' ),
				'name'     => 'not_found_typography',
				'selector' => '{{WRAPPER}} .taxonomy-posts .not-found',
			)
		);

		$this->add_control(
			'message_color',
			array(
				'label'     => esc_html__( 'Not Found Color', 'elementor-taxonomy-loop' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .taxonomy-posts .not-found' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the divider style controls.
	 *
	 * @return void
	 */
	private function register_divider_style_controls() {
		$this->start_controls_section(
			'loop_divider_style',
			array(
				'label'     => esc_html__( 'Divider Style', 'elementor-taxonomy-loop' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'loop_divider' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'divider_width',
			array(
				'label'      => esc_html__( 'Width', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'default'    => array(
					'size' => 200,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .term-content .divider' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'divider_height',
			array(
				'label'      => esc_html__( 'Height', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'default'    => array(
					'size' => 3,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .term-content .divider' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'divider_color',
			array(
				'label'     => esc_html__( 'Color', 'elementor-taxonomy-loop' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .term-content .divider' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'divider_gap',
			array(
				'label'      => esc_html__( 'Top Spacing', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .term-content .divider' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'divider_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'elementor-taxonomy-loop' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .term-content .divider' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'divider_alignment',
			array(
				'label'                => esc_html__( 'Alignment', 'elementor-taxonomy-loop' ),
				'type'                 => Controls_Manager::CHOOSE,
				'default'              => 'left',
				'toggle'               => true,
				'options'              => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'elementor-taxonomy-loop' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'elementor-taxonomy-loop' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'elementor-taxonomy-loop' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors_dictionary' => array(
					'left'   => 'margin-right: auto;',
					'center' => 'margin: auto;',
					'right'  => 'margin-left: auto;',
				),
				'selectors'            => array(
					'{{WRAPPER}} .term-content .divider' => '{{VALUE}}',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget on the frontend and in the editor preview.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$config   = $this->resolve_config( $settings );

		if ( isset( $config['error'] ) ) {
			$this->print_editor_notice( $config['error'] );

			return;
		}

		$terms = $this->query_terms( $settings, $config );

		if ( is_wp_error( $terms ) ) {
			$this->log( 'get_terms() error — ' . $terms->get_error_message() );
			$this->print_message( esc_html__( 'No terms found for this taxonomy.', 'elementor-taxonomy-loop' ), 'not-found' );

			return;
		}

		if ( empty( $terms ) ) {
			$this->print_message( esc_html__( 'No terms found for this taxonomy.', 'elementor-taxonomy-loop' ), 'not-found' );

			return;
		}

		$is_lazy = $config['lazy_load'] && ! $this->is_editor_render();

		$this->add_render_attribute( 'container', 'class', 'e-taxonomy-loop' );

		if ( $is_lazy ) {
			$this->add_render_attribute(
				'container',
				array(
					'data-taxonomy-loop-lazy' => '1',
					'data-post-id'            => (string) $this->get_document_id(),
					'data-element-id'         => $this->get_id(),
				)
			);
			$this->prepare_lazy_assets();
		}

		$eager_term_ids = array();

		foreach ( array_values( $terms ) as $index => $term ) {
			if ( ! $is_lazy || $index < $config['eager_count'] ) {
				$eager_term_ids[] = (int) $term->term_id;
			}
		}

		$posts_by_term = empty( $eager_term_ids )
			? array()
			: $this->fetch_post_ids_grouped_by_term( $config, $eager_term_ids );

		echo '<div ';
		$this->print_render_attribute_string( 'container' );
		echo '>';

		foreach ( array_values( $terms ) as $index => $term ) {
			$term_id = (int) $term->term_id;

			printf( '<div class="taxonomy-posts taxonomy-posts-%s">', esc_attr( (string) $term_id ) );

			$this->print_term_header( $term, $settings );

			if ( $is_lazy && $index >= $config['eager_count'] ) {
				$this->print_lazy_stub( $term_id );
			} else {
				echo '<div class="posts-list">';
				Elementor_Utils::print_unescaped_internal_string(
					$this->render_loop_grid_html( $posts_by_term[ $term_id ] ?? array(), $config )
				);
				echo '</div>';
			}

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Render a single lazily-loaded term, for the AJAX endpoint.
	 *
	 * Everything is re-derived from the widget's saved settings, and the requested
	 * term has to actually be one of this widget's lazy terms. Nothing about the
	 * output is taken from the request beyond the term ID itself.
	 *
	 * @param int $term_id Term to render.
	 * @return string|null Loop Grid markup, or null when the term is not lazily rendered here.
	 */
	public function render_lazy_term( $term_id ) {
		$term_id  = (int) $term_id;
		$settings = $this->get_settings_for_display();
		$config   = $this->resolve_config( $settings );

		if ( isset( $config['error'] ) || ! $config['lazy_load'] ) {
			return null;
		}

		$terms = $this->query_terms( $settings, $config );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		$position = null;

		foreach ( array_values( $terms ) as $index => $term ) {
			if ( (int) $term->term_id === $term_id ) {
				$position = $index;
				break;
			}
		}

		if ( null === $position || $position < $config['eager_count'] ) {
			return null;
		}

		$posts_by_term = $this->fetch_post_ids_grouped_by_term( $config, array( $term_id ) );

		return $this->render_loop_grid_html( $posts_by_term[ $term_id ] ?? array(), $config );
	}

	/**
	 * Validate and normalise every setting the render paths depend on.
	 *
	 * @param array<string,mixed> $settings Display settings.
	 * @return array<string,mixed> Normalised config, or an array with an `error` key.
	 */
	private function resolve_config( array $settings ) {
		$taxonomy  = isset( $settings['taxonomy'] ) ? (string) $settings['taxonomy'] : '';
		$post_type = isset( $settings['post_type'] ) ? (string) $settings['post_type'] : '';

		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return array( 'error' => esc_html__( 'Invalid taxonomy selected.', 'elementor-taxonomy-loop' ) );
		}

		if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
			return array( 'error' => esc_html__( 'Invalid post type selected.', 'elementor-taxonomy-loop' ) );
		}

		if ( ! is_object_in_taxonomy( $post_type, $taxonomy ) ) {
			return array(
				'error' => sprintf(
					/* translators: 1: taxonomy slug, 2: post type slug. */
					esc_html__( 'The taxonomy "%1$s" is not registered for the "%2$s" post type. Pick a matching combination.', 'elementor-taxonomy-loop' ),
					$taxonomy,
					$post_type
				),
			);
		}

		$posts_per_term = isset( $settings['posts_per_term'] ) ? (int) $settings['posts_per_term'] : 6;

		if ( $posts_per_term < 1 ) {
			$posts_per_term = -1;
		}

		return array(
			'taxonomy'       => $taxonomy,
			'post_type'      => $post_type,
			'skin'           => absint( $settings['loop_skin'] ?? 0 ),
			'post_orderby'   => $this->sanitize_choice( $settings['post_orderby'] ?? 'date', self::POST_ORDERBY_WHITELIST, 'date' ),
			'post_order'     => $this->sanitize_choice( strtoupper( (string) ( $settings['post_order'] ?? 'DESC' ) ), self::ORDER_WHITELIST, 'DESC' ),
			'posts_per_term' => $posts_per_term,
			'columns'        => $this->clamp_columns( $settings['columns'] ?? 3, 3 ),
			'columns_tablet' => $this->clamp_columns( $settings['columns_tablet'] ?? 2, 2 ),
			'columns_mobile' => $this->clamp_columns( $settings['columns_mobile'] ?? 1, 1 ),
			'equal_height'   => 'yes' === ( $settings['equal_height'] ?? '' ) ? 'yes' : '',
			'lazy_load'      => 'yes' === ( $settings['lazy_load'] ?? 'no' ),
			'eager_count'    => max( 1, (int) ( $settings['eager_terms_count'] ?? 2 ) ),
		);
	}

	/**
	 * Run the term query for the current settings.
	 *
	 * @param array<string,mixed> $settings Display settings.
	 * @param array<string,mixed> $config   Normalised config.
	 * @return WP_Term[]|\WP_Error
	 */
	private function query_terms( array $settings, array $config ) {
		$include = $this->parse_term_ids( $settings['include_terms'] ?? '' );
		$exclude = $this->parse_term_ids( $settings['exclude_terms'] ?? '' );
		$limit   = max( 0, (int) ( $settings['terms_limit'] ?? 0 ) );

		$args = array(
			'taxonomy'   => $config['taxonomy'],
			'hide_empty' => $this->should_hide_empty_terms( $settings ),
			'orderby'    => $this->sanitize_choice( $settings['orderby'] ?? 'name', self::TERM_ORDERBY_WHITELIST, 'name' ),
			'order'      => $this->sanitize_choice( strtoupper( (string) ( $settings['order'] ?? 'ASC' ) ), self::ORDER_WHITELIST, 'ASC' ),
		);

		if ( ! empty( $include ) ) {
			$args['include'] = $include;
		}

		if ( ! empty( $exclude ) ) {
			$args['exclude'] = $exclude;
		}

		if ( $limit > 0 ) {
			$args['number'] = $limit;
		}

		/**
		 * Filters the arguments passed to `get_terms()` for the Taxonomy Loop widget.
		 *
		 * `fields` is reset afterwards — the widget needs whole term objects, so that
		 * one argument is not yours to change.
		 *
		 * @since 2.0.0
		 *
		 * @param array         $args   Term query arguments.
		 * @param Taxonomy_Loop $widget The widget instance.
		 */
		$args = apply_filters( 'elementor_taxonomy_loop_terms_args', $args, $this );

		$args['fields'] = 'all';

		return get_terms( $args );
	}

	/**
	 * Resolve the Hide Empty Terms toggle, honouring the pre-1.1 control key.
	 *
	 * `get_settings_for_display()` fills defaults for registered controls, so the
	 * current `hide_empty` key is never null and a `??` fallback would never reach
	 * the legacy value. The raw saved data is the only place it survives.
	 *
	 * @param array<string,mixed> $settings Display settings.
	 * @return bool
	 */
	private function should_hide_empty_terms( array $settings ) {
		$raw = $this->get_data( 'settings' );

		if ( is_array( $raw ) && isset( $raw['show_empty'] ) ) {
			return 'yes' === $raw['show_empty'];
		}

		return 'yes' === ( $settings['hide_empty'] ?? 'no' );
	}

	/**
	 * Fetch post IDs for each term, capped at the configured per-term count.
	 *
	 * One bounded query per term keeps the distribution across terms honest even
	 * when posts are skewed heavily toward a single term.
	 *
	 * @param array<string,mixed> $config   Normalised config.
	 * @param int[]               $term_ids Terms to fetch posts for.
	 * @return array<int,int[]> Term ID => ordered post IDs.
	 */
	private function fetch_post_ids_grouped_by_term( array $config, array $term_ids ) {
		$term_ids = array_values( array_unique( array_map( 'intval', $term_ids ) ) );
		$grouped  = array_fill_keys( $term_ids, array() );

		foreach ( $term_ids as $term_id ) {
			$args = array(
				'post_type'              => $config['post_type'],
				'posts_per_page'         => $config['posts_per_term'],
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Grouping by term is the widget's purpose.
					array(
						'taxonomy' => $config['taxonomy'],
						'field'    => 'term_id',
						'terms'    => array( $term_id ),
					),
				),
				'orderby'                => $config['post_orderby'],
				'order'                  => $config['post_order'],
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			);

			/**
			 * Filters the per-term post query arguments for the Taxonomy Loop widget.
			 *
			 * `fields` is reset afterwards — the Loop Grid is handed post IDs, so that
			 * one argument is not yours to change.
			 *
			 * @since 2.0.0
			 *
			 * @param array         $args    Post query arguments.
			 * @param int           $term_id The term being queried.
			 * @param Taxonomy_Loop $widget  The widget instance.
			 */
			$args = apply_filters( 'elementor_taxonomy_loop_posts_args', $args, $term_id, $this );

			$args['fields'] = 'ids';

			$grouped[ $term_id ] = array_map( 'intval', (array) get_posts( $args ) );
		}

		return $grouped;
	}

	/**
	 * Print a term's title block.
	 *
	 * @param WP_Term             $term     Term being rendered.
	 * @param array<string,mixed> $settings Display settings.
	 * @return void
	 */
	private function print_term_header( WP_Term $term, array $settings ) {
		$tag = Elementor_Utils::validate_html_tag(
			$this->sanitize_choice( $settings['title_tag'] ?? 'h2', self::TITLE_TAG_WHITELIST, 'h2' )
		);

		$title = ( $settings['title_prefix'] ?? '' ) . $term->name . ( $settings['title_suffix'] ?? '' );

		printf(
			'<div class="term-content"><%1$s class="term-title">%2$s</%1$s>',
			esc_attr( $tag ),
			esc_html( $title )
		);

		if ( 'yes' === ( $settings['loop_divider'] ?? '' ) ) {
			echo '<hr class="divider" />';
		}

		echo '</div>';
	}

	/**
	 * Print the placeholder a lazily-loaded term is swapped into.
	 *
	 * @param int $term_id Term the stub stands in for.
	 * @return void
	 */
	private function print_lazy_stub( $term_id ) {
		printf(
			'<div class="posts-list" data-taxonomy-loop-stub="1" data-term-id="%d" aria-busy="true"><p class="taxonomy-loop-loading">%s</p></div>',
			(int) $term_id,
			esc_html__( 'Loading…', 'elementor-taxonomy-loop' )
		);
	}

	/**
	 * Build the Loop Grid markup for one term's posts.
	 *
	 * @param int[]               $post_ids Post IDs to render.
	 * @param array<string,mixed> $config   Normalised config.
	 * @return string Complete inner markup for a `.posts-list` container.
	 */
	private function render_loop_grid_html( array $post_ids, array $config ) {
		if ( empty( $post_ids ) ) {
			return '<p class="not-found">' . esc_html__( 'No posts found for this term.', 'elementor-taxonomy-loop' ) . '</p>';
		}

		if ( ! $this->is_loop_builder_available() ) {
			return $this->notice_markup( esc_html__( 'Elementor Pro\'s Loop Builder is not available.', 'elementor-taxonomy-loop' ) );
		}

		if ( $config['skin'] <= 0 || 'elementor_library' !== get_post_type( $config['skin'] ) ) {
			return $this->notice_markup( esc_html__( 'Please select a valid loop template.', 'elementor-taxonomy-loop' ) );
		}

		$buffer_level = ob_get_level();

		try {
			$loop_grid = Elementor::instance()->elements_manager->create_element_instance(
				array(
					'id'         => Elementor_Utils::generate_random_string(),
					'elType'     => 'widget',
					'widgetType' => 'loop-grid',
					'settings'   => array(
						'template_id'          => $config['skin'],
						'post_query_post_type' => 'by_id',
						'post_query_posts_ids' => $post_ids,
						'posts_per_page'       => count( $post_ids ),
						'post_query_orderby'   => $config['post_orderby'],
						'post_query_order'     => $config['post_order'],
						'columns'              => $config['columns'],
						'columns_tablet'       => $config['columns_tablet'],
						'columns_mobile'       => $config['columns_mobile'],
						'equal_height'         => $config['equal_height'],
					),
				),
				array()
			);

			if ( ! $loop_grid ) {
				return $this->notice_markup( esc_html__( 'Error generating loop grid.', 'elementor-taxonomy-loop' ) );
			}

			ob_start();
			$loop_grid->print_element();

			return (string) ob_get_clean();
		} catch ( \Throwable $exception ) {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}

			$this->log( 'Loop Grid render failed — ' . $exception->getMessage() );

			return $this->notice_markup( esc_html__( 'Error generating loop grid.', 'elementor-taxonomy-loop' ) );
		}
	}

	/**
	 * Whether Elementor Pro's Loop Builder module is loaded and enabled.
	 *
	 * @return bool
	 */
	private function is_loop_builder_available() {
		if ( ! class_exists( Elementor_Pro::class ) ) {
			return false;
		}

		$module = Elementor_Pro::instance()->modules_manager->get_modules( 'loop-builder' );

		return $module && $module->is_active();
	}

	/**
	 * Make sure the assets a lazily-injected Loop Grid needs are on the page.
	 *
	 * Elementor only enqueues a widget's stylesheet when that widget renders. If
	 * every eagerly-rendered term happens to be empty, no Loop Grid is printed and
	 * its CSS never loads — leaving the lazily-fetched grids unstyled.
	 *
	 * @return void
	 */
	private function prepare_lazy_assets() {
		wp_enqueue_script( Addon::SCRIPT_HANDLE );

		/**
		 * Filters the Elementor Pro style handles preloaded for lazy-rendered grids.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $handles Registered style handles to enqueue.
		 */
		$handles = apply_filters( 'elementor_taxonomy_loop_lazy_style_handles', array( 'widget-loop-grid' ) );

		foreach ( $handles as $handle ) {
			if ( wp_style_is( $handle, 'registered' ) && ! wp_style_is( $handle, 'enqueued' ) ) {
				wp_enqueue_style( $handle );
			}
		}
	}

	/**
	 * ID of the document that owns this widget instance.
	 *
	 * @return int
	 */
	private function get_document_id() {
		$document = Elementor::instance()->documents->get_current();

		if ( $document ) {
			return (int) $document->get_main_id();
		}

		return (int) get_the_ID();
	}

	/**
	 * Whether the widget is being rendered inside the editor or its preview.
	 *
	 * @return bool
	 */
	private function is_editor_render() {
		$elementor = Elementor::instance();

		return $elementor->editor->is_edit_mode() || $elementor->preview->is_preview_mode();
	}

	/**
	 * Print a misconfiguration message — but only where someone can fix it.
	 *
	 * Visitors should never be shown a builder error, so these stay inside the
	 * editor and the widget simply renders nothing on the frontend.
	 *
	 * @param string $message Message to display.
	 * @return void
	 */
	private function print_editor_notice( $message ) {
		if ( ! $this->is_editor_render() ) {
			return;
		}

		$this->print_message( $message, 'error-message' );
	}

	/**
	 * Print a single message paragraph inside the widget wrapper.
	 *
	 * @param string $message   Message to display.
	 * @param string $css_class CSS class for the paragraph.
	 * @return void
	 */
	private function print_message( $message, $css_class ) {
		printf(
			'<div class="e-taxonomy-loop"><p class="%s">%s</p></div>',
			esc_attr( $css_class ),
			esc_html( $message )
		);
	}

	/**
	 * Wrap a message in the markup used inside a `.posts-list` container.
	 *
	 * @param string $message Message to display.
	 * @return string
	 */
	private function notice_markup( $message ) {
		return '<p class="error-message">' . esc_html( $message ) . '</p>';
	}

	/**
	 * Parse a free-text list of term IDs.
	 *
	 * @param mixed $value Raw control value.
	 * @return int[]
	 */
	private function parse_term_ids( $value ) {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return array();
		}

		$ids = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', $value ) ) );

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Constrain a value to a known-good set.
	 *
	 * @param mixed    $value    Candidate value.
	 * @param string[] $allowed  Accepted values.
	 * @param string   $fallback Value to use when the candidate is not accepted.
	 * @return string
	 */
	private function sanitize_choice( $value, array $allowed, $fallback ) {
		return in_array( $value, $allowed, true ) ? (string) $value : $fallback;
	}

	/**
	 * Clamp a column count into the range the control allows.
	 *
	 * @param mixed $value    Candidate value.
	 * @param int   $fallback Value to use when the candidate is empty.
	 * @return int
	 */
	private function clamp_columns( $value, $fallback ) {
		$columns = ( '' === $value || null === $value ) ? $fallback : (int) $value;

		return max( 1, min( 12, $columns ) );
	}

	/**
	 * Write a debug line when WP_DEBUG is on.
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	private function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Elementor Taxonomy Loop Widget: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-only diagnostics.
		}
	}
}
