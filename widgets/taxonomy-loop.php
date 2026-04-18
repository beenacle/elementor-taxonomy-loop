<?php
if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

use ElementorPro\Plugin as ProPlugin;
use Elementor\Plugin as Elementor;
use Elementor\Utils as ElementorUtils;
use ElementorPro\Core\Utils as Pro_Utils;
use ElementorPro\Modules\QueryControl\Controls\Template_Query;
use ElementorPro\Modules\QueryControl\Module as QueryControlModule;
use ElementorPro\Modules\LoopBuilder\Documents\Loop as LoopDocument;

class Beenacle_Taxonomy_Loop extends \Elementor\Widget_Base
{
  private function parse_term_ids($value): array
  {
    if (!is_string($value) || trim($value) === '') {
      return [];
    }

    $parts = preg_split('/[\s,]+/', $value);
    $ids = array_filter(array_map('absint', $parts));

    return array_values(array_unique($ids));
  }

  private static function sanitize_choice($value, array $allowed, string $default): string
  {
    return in_array($value, $allowed, true) ? $value : $default;
  }

  private const TERM_ORDERBY_WHITELIST = ['name', 'id', 'slug', 'menu_order', 'include'];
  private const TERM_ORDER_WHITELIST   = ['ASC', 'DESC'];
  private const POST_ORDERBY_WHITELIST = ['date', 'title', 'ID', 'menu_order', 'rand'];
  private const POST_ORDER_WHITELIST   = ['ASC', 'DESC'];
  private const AJAX_ACTION            = 'elementor_taxonomy_loop_render_term';
  private const AJAX_NONCE_ACTION      = 'elementor_taxonomy_loop_render';
  private const LAZY_PER_TERM_MAX      = 100;

  /**
   * Fetch post IDs for each term, capped at $per_term per term.
   *
   * Runs one bounded query per term so that uneven post distribution across
   * terms can't starve later buckets (a single consolidated query ordered by
   * date/title can exhaust its window on the first term and leave the rest
   * empty).
   *
   * @return array<int, int[]> map of term_id => ordered post IDs
   */
  private static function fetch_post_ids_grouped_by_term(
    string $post_type,
    string $taxonomy,
    array $term_ids,
    string $orderby,
    string $order,
    int $per_term
  ): array {
    $term_ids = array_values(array_unique(array_map('intval', $term_ids)));
    $grouped = array_fill_keys($term_ids, []);

    foreach ($term_ids as $term_id) {
      $grouped[$term_id] = get_posts([
        'post_type'              => $post_type,
        'posts_per_page'         => $per_term,
        'tax_query'              => [
          [
            'taxonomy' => $taxonomy,
            'field'    => 'term_id',
            'terms'    => [$term_id],
          ],
        ],
        'orderby'                => $orderby,
        'order'                  => $order,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
      ]);
    }

    return $grouped;
  }

  public function get_name(): string
  {
    return 'taxonomy_loop';
  }

  public function get_title(): string
  {
    return esc_html__('Taxonomy Loop', 'elementor-taxonomy-loop');
  }

  public function get_icon(): string
  {
    return 'eicon-loop-builder';
  }

  public function get_categories(): array
  {
    return ['general'];
  }

  public function get_keywords(): array
  {
    return ['loop', 'taxonomy', 'post', 'custom'];
  }

  public function get_script_depends(): array
  {
    return ['elementor-taxonomy-loop-lazy'];
  }

  public function get_style_depends(): array
  {
    return ['taxonomy-loop-style'];
  }

  // Register Controls
  protected function register_controls(): void
  {
    //Get Post Types & public taxanomies
    $supported_taxonomies = [];
    $public_types = Pro_Utils::get_public_post_types();

    foreach ($public_types as $type => $title) {
      $taxonomies = get_object_taxonomies($type, 'objects');
      foreach ($taxonomies as $key => $tax) {
        if (!isset($supported_taxonomies[$tax->name])) {
          $supported_taxonomies[$tax->name] = $tax->label . ' (' . $tax->name . ')';
        }
      }
    }

    //Content Area-----------------------------------
    $this->start_controls_section(
      'content_section',
      [
        'label' => esc_html__('Select Content', 'elementor-taxonomy-loop'),
        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
      ]
    );
    $this->add_control(
      'post_type',
      [
        'label' => esc_html__('Select Post Type', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'post',
        'options' => $public_types,
      ]
    );
    $this->add_control(
      'taxonomy',
      [
        'label' => esc_html__('Select Taxonomy', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'options' => $supported_taxonomies,
        'default' => 'category',
      ]
    );
    $this->add_control(
      'loop_skin',
      [
        'label' => esc_html__('Select Loop Skin', 'elementor-taxonomy-loop'),
        'type' => Template_Query::CONTROL_ID,
        'autocomplete' => [
          'object' => QueryControlModule::QUERY_OBJECT_LIBRARY_TEMPLATE,
          'query' => [
            // Query parameters to filter templates
          ],
        ],
        'actions' => [
          'new' => [
            'visible' => true,
            'document_config' => [
              'type' => LoopDocument::get_type(),
            ],
            'after_action' => 'redirect',
          ],
          'edit' => [
            'visible' => true,
            'after_action' => 'redirect',
          ],
        ],
      ]
    );
    $this->add_control(
      'hide_empty',
      [
        'label' => esc_html__('Hide Empty Terms', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_on' => esc_html__('Yes', 'elementor-taxonomy-loop'),
        'label_off' => esc_html__('No', 'elementor-taxonomy-loop'),
        'return_value' => 'yes',
        'default' => 'no',
      ]
    );
    $this->add_control(
      'loop_divider',
      [
        'label' => esc_html__('Show Divider', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_on' => esc_html__('Show', 'elementor-taxonomy-loop'),
        'label_off' => esc_html__('Hide', 'elementor-taxonomy-loop'),
        'return_value' => 'yes',
        'default' => 'no',
      ]
    );
    $this->add_control(
      'include_terms',
      [
        'label' => esc_html__('Include Terms (IDs)', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'ai' => [
          'active' => false,
        ],
      ]
    );
    $this->add_control(
      'exclude_terms',
      [
        'label' => esc_html__('Exclude Terms (IDs)', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'ai' => [
          'active' => false,
        ],
      ]
    );
    $this->add_control(
      'orderby',
      [
        'label' => esc_html__('Order Terms By', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'name',
        'options' => [
          'name' => esc_html__('Name', 'elementor-taxonomy-loop'),
          'id' => esc_html__('ID', 'elementor-taxonomy-loop'),
          'slug' => esc_html__('Slug', 'elementor-taxonomy-loop'),
          'menu_order' => esc_html__('Menu Order', 'elementor-taxonomy-loop'),
          'include' => esc_html__('Include', 'elementor-taxonomy-loop'),
        ],
      ]
    );
    $this->add_control(
      'order',
      [
        'label' => esc_html__('Order Direction', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'ASC',
        'options' => [
          'ASC' => esc_html__('Ascending', 'elementor-taxonomy-loop'),
          'DESC' => esc_html__('Descending', 'elementor-taxonomy-loop'),
        ],
      ]
    );

    $this->add_control(
      'post_orderby',
      [
        'label' => esc_html__('Order Posts By', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'date',
        'options' => [
          'date' => esc_html__('Date', 'elementor-taxonomy-loop'),
          'title' => esc_html__('Title', 'elementor-taxonomy-loop'),
          'ID' => esc_html__('ID', 'elementor-taxonomy-loop'),
          'menu_order' => esc_html__('Menu Order', 'elementor-taxonomy-loop'),
          'rand' => esc_html__('Random', 'elementor-taxonomy-loop'),
        ],
      ]
    );

    $this->add_control(
      'post_order',
      [
        'label' => esc_html__('Post Order Direction', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'DESC',
        'options' => [
          'ASC' => esc_html__('Ascending', 'elementor-taxonomy-loop'),
          'DESC' => esc_html__('Descending', 'elementor-taxonomy-loop'),
        ],
      ]
    );
    $this->add_control(
      'posts_per_term',
      [
        'label' => esc_html__('Posts Per Term', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::NUMBER,
        'default' => 6,
        'min' => -1,
        'step' => 1,
        'description' => esc_html__('Set -1 to show all posts.', 'elementor-taxonomy-loop'),
      ]
    );

    $this->add_control(
      'lazy_load',
      [
        'label' => esc_html__('Lazy Load Terms', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_on' => esc_html__('On', 'elementor-taxonomy-loop'),
        'label_off' => esc_html__('Off', 'elementor-taxonomy-loop'),
        'return_value' => 'yes',
        'default' => 'no',
        'description' => esc_html__('Render terms beyond the eager count only when they scroll into view.', 'elementor-taxonomy-loop'),
      ]
    );

    $this->add_control(
      'eager_terms_count',
      [
        'label' => esc_html__('Eager-Rendered Terms', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::NUMBER,
        'default' => 2,
        'min' => 1,
        'step' => 1,
        'description' => esc_html__('Number of terms rendered server-side on first paint. The rest are fetched lazily when visible.', 'elementor-taxonomy-loop'),
        'condition' => [
          'lazy_load' => 'yes',
        ],
      ]
    );

    // Title Prefix and Suffix Controls
    $this->add_control(
      'title_prefix',
      [
        'label' => esc_html__('Title Prefix', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'placeholder' => esc_html__('Enter text to appear before the title', 'elementor-taxonomy-loop'),
        'label_block' => true,
      ]
    );

    $this->add_control(
      'title_suffix',
      [
        'label' => esc_html__('Title Suffix', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'placeholder' => esc_html__('Enter text to appear after the title', 'elementor-taxonomy-loop'),
        'label_block' => true,
      ]
    );

    $this->end_controls_section();

    // Style Area-----------------------------------
    $this->start_controls_section(
      'main_category_style',
      [
        'label' => esc_html__('Items Settings', 'elementor-taxonomy-loop'),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
      ]
    );
    $this->add_responsive_control(
      'category_gap',
      [
        'label' => esc_html__('Category Gap', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container' => 'gap: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_responsive_control(
      'content_gap',
      [
        'label' => esc_html__('Content Gap', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .taxonomy-posts .posts-list' => 'margin-top: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_group_control(
      \Elementor\Group_Control_Border::get_type(),
      [
        'name' => 'category_border',
        'selector' => '{{WRAPPER}} .taxonomy-posts',
      ]
    );
    $this->add_responsive_control(
      'category_border_radius',
      [
        'label' => esc_html__('Border Radius', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .taxonomy-posts' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
        'condition' => [
          'category_border_border!' => 'none',
        ],
      ]
    );
    $this->add_responsive_control(
      'category_padding',
      [
        'label' => esc_html__('Padding', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .taxonomy-posts' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
      ]
    );

    $this->end_controls_section();
    $this->start_controls_section(
      'term_style',
      [
        'label' => esc_html__('Category Styling', 'elementor-taxonomy-loop'),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
      ]
    );
    $this->add_group_control(
      \Elementor\Group_Control_Border::get_type(),
      [
        'name' => 'term_border',
        'selector' => '{{WRAPPER}} .taxonomy-posts .term-content',
      ]
    );
    $this->add_responsive_control(
      'term_border_radius',
      [
        'label' => esc_html__('Border Radius', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .taxonomy-posts .term-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
        'condition' => [
          'term_border_border!' => 'none',
        ],
      ]
    );
    $this->add_responsive_control(
      'term_padding',
      [
        'label' => esc_html__('Padding', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .taxonomy-posts .term-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
      ]
    );
    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'label' => esc_html__('Title', 'elementor-taxonomy-loop'),
        'name' => 'term_title_typography',
        'selector' => '{{WRAPPER}} .term-content .term-title',
      ]
    );
    $this->add_control(
      'term_title_color',
      [
        'label' => esc_html__('Title Color', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::COLOR,
        'selectors' => [
          '{{WRAPPER}} .term-content .term-title' => 'color: {{VALUE}}',
        ],
      ]
    );
    $this->add_control(
      'term_title_alignment',
      [
        'label' => esc_html__('Alignment', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::CHOOSE,
        'options' => [
          'left' => [
            'title' => esc_html__('Left', 'elementor-taxonomy-loop'),
            'icon' => 'eicon-text-align-left',
          ],
          'center' => [
            'title' => esc_html__('Center', 'elementor-taxonomy-loop'),
            'icon' => 'eicon-text-align-center',
          ],
          'right' => [
            'title' => esc_html__('Right', 'elementor-taxonomy-loop'),
            'icon' => 'eicon-text-align-right',
          ],
        ],
        'default' => 'left',
        'toggle' => true,
        'selectors' => [
          '{{WRAPPER}} .term-content .term-title' => 'text-align: {{VALUE}};',
        ],
      ]
    );
    $this->end_controls_section();
    $this->start_controls_section(
      'loop_style',
      [
        'label' => esc_html__('Loop Controls', 'elementor-taxonomy-loop'),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
      ]
    );
    $this->add_responsive_control(
      'columns',
      [
        'label' => esc_html__('Loop Columns', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::NUMBER,
        'default' => 3,
        'tablet_default' => 2,
        'mobile_default' => 1,
        'min' => 1,
        'max' => 12,
        'frontend_available' => true,
        'separator' => 'before',
        'condition' => [
          'loop_skin!' => '',
        ],
      ]
    );
    $this->add_responsive_control(
      'column_gap',
      [
        'label' => esc_html__('Columns Gap', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'range' => [
          'px' => [
            'max' => 100,
          ],
          'em' => [
            'max' => 10,
          ],
          'rem' => [
            'max' => 10,
          ],
        ],
        'selectors' => [
          '{{WRAPPER}} .posts-list .elementor-widget-loop-grid' => '--grid-column-gap: {{SIZE}}{{UNIT}}',
        ],
      ]
    );
    $this->add_responsive_control(
      'row_gap',
      [
        'label' => esc_html__('Row Gap', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'range' => [
          'px' => [
            'max' => 100,
          ],
          'em' => [
            'max' => 10,
          ],
          'rem' => [
            'max' => 10,
          ],
        ],
        'frontend_available' => true,
        'selectors' => [
          '{{WRAPPER}} .posts-list .elementor-widget-loop-grid' => '--grid-row-gap: {{SIZE}}{{UNIT}}',
        ],
      ]
    );
    $this->add_control(
      'equal_height',
      [
        'label' => esc_html__('Equal height', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_off' => esc_html__('Off', 'elementor-taxonomy-loop'),
        'label_on' => esc_html__('On', 'elementor-taxonomy-loop'),
        'selectors' => [
          '{{WRAPPER}} .posts-list .elementor-widget-loop-grid .elementor-loop-container' => 'grid-auto-rows: 1fr',
          // `.elementor-section-wrap` exists only when editing the loop template.
          '{{WRAPPER}} .posts-list .elementor-widget-loop-grid .e-loop-item > .elementor-section,
           {{WRAPPER}} .posts-list .elementor-widget-loop-grid .e-loop-item > .elementor-section > .elementor-container,
           {{WRAPPER}} .posts-list .elementor-widget-loop-grid .e-loop-item > .e-con,
           {{WRAPPER}} .posts-list .elementor-widget-loop-grid .e-loop-item .elementor-section-wrap  > .e-con' => 'height: 100%',
        ],
      ]
    );
    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'label' => esc_html__('Not Found', 'elementor-taxonomy-loop'),
        'name' => 'not_found_typography',
        'selector' => '{{WRAPPER}} .taxonomy-posts .not-found',
      ]
    );
    $this->add_control(
      'message_color',
      [
        'label' => esc_html__('Not Found Color', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::COLOR,
        'selectors' => [
          '{{WRAPPER}} .taxonomy-posts .not-found' => 'color: {{VALUE}}',
        ],
      ]
    );
    $this->end_controls_section();
    $this->start_controls_section(
      'loop_divider_style',
      [
        'label' => esc_html__('Divider Style', 'elementor-taxonomy-loop'),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        'condition' => [
          'loop_divider' => 'yes',
        ],
      ]
    );
    $this->add_responsive_control(
      'divider_width',
      [
        'label' => esc_html__('Width', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
        'default' => [
          'size' => 200,
          'unit' => 'px',
        ],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .term-content .divider' => 'width: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_responsive_control(
      'divider_height',
      [
        'label' => esc_html__('Height', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'default' => [
          'size' => 3,
          'unit' => 'px',
        ],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .term-content .divider' => 'height: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_control(
      'divider_color',
      [
        'label' => esc_html__('Color', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::COLOR,
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .term-content .divider' => 'background-color: {{VALUE}}',
        ],
      ]
    );
    $this->add_responsive_control(
      'divider_gap',
      [
        'label' => esc_html__('Top Spacing', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .term-content .divider' => 'margin-top: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_responsive_control(
      'divider_radius',
      [
        'label' => esc_html__('Border Radius', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .term-content .divider' => 'border-radius: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_control(
      'divider_alignment',
      [
        'label' => esc_html__('Alignment', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::CHOOSE,
        'options' => [
          'left' => [
            'title' => esc_html__('Left', 'elementor-taxonomy-loop'),
            'icon' => 'eicon-text-align-left',
          ],
          'center' => [
            'title' => esc_html__('Center', 'elementor-taxonomy-loop'),
            'icon' => 'eicon-text-align-center',
          ],
          'right' => [
            'title' => esc_html__('Right', 'elementor-taxonomy-loop'),
            'icon' => 'eicon-text-align-right',
          ],
        ],
        'default' => 'left',
        'toggle' => true,
        'selectors_dictionary' => [
          'left' => 'margin-right: auto;',
          'center' => 'margin: auto;',
          'right' => 'margin-left: auto;',
        ],
        'selectors' => [
          '{{WRAPPER}} .term-content .divider' => '{{VALUE}}',
        ],
      ]
    );
    $this->end_controls_section();
  }

  protected function render(): void
  {
    $settings = $this->get_settings_for_display();
    $taxonomy = $settings['taxonomy'] ?? '';
    $post_type = $settings['post_type'] ?? '';
    $skin = $settings['loop_skin'] ?? '';
    $divider = $settings['loop_divider'] ?? '';
    $title_prefix = $settings['title_prefix'] ?? '';
    $title_suffix = $settings['title_suffix'] ?? '';

    if (empty($taxonomy) || !taxonomy_exists($taxonomy)) {
      echo '<p class="error-message">' . esc_html__('Invalid taxonomy selected.', 'elementor-taxonomy-loop') . '</p>';
      return;
    }

    if (empty($post_type) || !post_type_exists($post_type)) {
      echo '<p class="error-message">' . esc_html__('Invalid post type selected.', 'elementor-taxonomy-loop') . '</p>';
      return;
    }

    if (!is_object_in_taxonomy($post_type, $taxonomy)) {
      echo '<p class="error-message">' . esc_html(sprintf(
        /* translators: 1: taxonomy slug, 2: post type slug */
        __('The taxonomy "%1$s" is not registered for the "%2$s" post type. Pick a matching combination.', 'elementor-taxonomy-loop'),
        $taxonomy,
        $post_type
      )) . '</p>';
      return;
    }

    $include_terms = $this->parse_term_ids($settings['include_terms'] ?? '');
    $exclude_terms = $this->parse_term_ids($settings['exclude_terms'] ?? '');
    $posts_per_term = isset($settings['posts_per_term']) ? (int) $settings['posts_per_term'] : 6;
    if ($posts_per_term === 0 || $posts_per_term < -1) {
      $posts_per_term = -1;
    }

    $term_orderby = self::sanitize_choice($settings['orderby'] ?? 'name', self::TERM_ORDERBY_WHITELIST, 'name');
    $term_order   = self::sanitize_choice($settings['order'] ?? 'ASC', self::TERM_ORDER_WHITELIST, 'ASC');
    $post_orderby = self::sanitize_choice($settings['post_orderby'] ?? 'date', self::POST_ORDERBY_WHITELIST, 'date');
    $post_order   = self::sanitize_choice($settings['post_order'] ?? 'DESC', self::POST_ORDER_WHITELIST, 'DESC');

    // Fetch terms for the specified taxonomy
    // WordPress already caches get_terms() internally, so no need for transient cache
    // Read raw saved data for the legacy `show_empty` key: get_settings_for_display()
    // fills in defaults for registered controls, so the new `hide_empty` key is
    // never null and a ?? fallback on $settings would never reach the legacy value.
    $raw_settings = $this->get_data('settings');
    $hide_empty_setting = is_array($raw_settings) && isset($raw_settings['show_empty'])
      ? $raw_settings['show_empty']
      : ($settings['hide_empty'] ?? 'no');
    $terms_args = [
      'taxonomy'   => $taxonomy,
      'hide_empty' => 'yes' === $hide_empty_setting,
      'orderby'    => $term_orderby,
      'order'      => $term_order,
    ];
    if (!empty($include_terms)) {
      $terms_args['include'] = $include_terms;
    }
    if (!empty($exclude_terms)) {
      $terms_args['exclude'] = $exclude_terms;
    }
    $terms = get_terms($terms_args);

    if (empty($terms) || is_wp_error($terms)) {
      echo '<p class="error-message">' . esc_html__('No terms found for this taxonomy.', 'elementor-taxonomy-loop') . '</p>';
      return;
    }

    $lazy_enabled = ('yes' === ($settings['lazy_load'] ?? 'no')) && !$this->is_editor_render();
    $eager_count  = max(1, (int) ($settings['eager_terms_count'] ?? 2));

    $columns        = (int) ($settings['columns'] ?? 3);
    $columns_tablet = (int) ($settings['columns_tablet'] ?? 2);
    $columns_mobile = (int) ($settings['columns_mobile'] ?? 1);
    $equal_height   = $settings['equal_height'] ?? 'no';

    if ($lazy_enabled) {
      $lazy_config = [
        'ajaxUrl'    => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce(self::AJAX_NONCE_ACTION),
        'action'     => self::AJAX_ACTION,
        'postType'   => $post_type,
        'taxonomy'   => $taxonomy,
        'skin'       => $skin,
        'orderby'    => $post_orderby,
        'order'      => $post_order,
        'perTerm'    => $posts_per_term,
        'columns'    => $columns,
        'cols_t'     => $columns_tablet,
        'cols_m'     => $columns_mobile,
        'equal'      => $equal_height,
      ];
      $this->add_render_attribute('_wrapper', [
        'data-taxonomy-loop-lazy'   => '1',
        'data-taxonomy-loop-config' => wp_json_encode($lazy_config),
      ]);
    }

    $eager_term_ids = [];
    foreach ($terms as $idx => $term) {
      if (!$lazy_enabled || $idx < $eager_count) {
        $eager_term_ids[] = (int) $term->term_id;
      }
    }

    $posts_by_term = !empty($eager_term_ids)
      ? self::fetch_post_ids_grouped_by_term(
          $post_type,
          $taxonomy,
          $eager_term_ids,
          $post_orderby,
          $post_order,
          $posts_per_term
        )
      : [];

    foreach ($terms as $idx => $term) {
      $term_id   = (int) $term->term_id;
      $is_lazy   = $lazy_enabled && $idx >= $eager_count;

      echo '<div class="taxonomy-posts taxonomy-posts-' . esc_attr($term_id) . '">';
      echo '<div class="term-content">';
      echo '<h2 class="term-title">' .
        esc_html($title_prefix) .
        esc_html($term->name) .
        esc_html($title_suffix) .
        '</h2>';
      if ('yes' === $divider) {
        echo '<hr class="divider" />';
      }
      echo '</div>';

      if ($is_lazy) {
        echo '<div class="posts-list" data-taxonomy-loop-stub="1" data-term-id="' . esc_attr((string) $term_id) . '">';
        echo '<p class="taxonomy-loop-loading" aria-busy="true">' . esc_html__('Loading…', 'elementor-taxonomy-loop') . '</p>';
        echo '</div>';
        echo '</div>';
        continue;
      }

      $post_ids_array = $posts_by_term[$term_id] ?? [];

      echo '<div class="posts-list">';
      echo self::render_loop_grid_html(
        $post_ids_array,
        $skin,
        $post_orderby,
        $post_order,
        $columns,
        $columns_tablet,
        $columns_mobile,
        $equal_height
      );
      echo '</div>';
      echo '</div>';
    }
  }

  private function is_editor_render(): bool
  {
    if (!class_exists('\Elementor\Plugin')) {
      return false;
    }
    $plugin = Elementor::instance();
    if (isset($plugin->editor) && method_exists($plugin->editor, 'is_edit_mode') && $plugin->editor->is_edit_mode()) {
      return true;
    }
    if (isset($plugin->preview) && method_exists($plugin->preview, 'is_preview_mode') && $plugin->preview->is_preview_mode()) {
      return true;
    }
    return false;
  }

  /**
   * Render the inner Loop Grid HTML for a single term's post IDs.
   * Returns a complete inner block for the `.posts-list` container
   * (either the Loop Grid markup or an error/empty-state paragraph).
   */
  private static function render_loop_grid_html(
    array $post_ids_array,
    string $skin,
    string $post_orderby,
    string $post_order,
    int $columns,
    int $columns_tablet,
    int $columns_mobile,
    string $equal_height
  ): string {
    if (empty($post_ids_array)) {
      return '<p class="not-found">' . esc_html__('No posts found for this term.', 'elementor-taxonomy-loop') . '</p>';
    }

    $loop_builder_module = ProPlugin::instance()->modules_manager->get_modules('loop-builder');
    if (!$loop_builder_module || !$loop_builder_module->is_active()) {
      return '<p class="error-message">' . esc_html__('Loop Builder module not available.', 'elementor-taxonomy-loop') . '</p>';
    }

    if (empty($skin) || get_post_type($skin) !== 'elementor_library') {
      return '<p class="error-message">' . esc_html__('Please select a valid loop template.', 'elementor-taxonomy-loop') . '</p>';
    }

    try {
      $loop_grid = Elementor::instance()->elements_manager->create_element_instance([
        'id' => method_exists(ElementorUtils::class, 'generate_random_string')
          ? ElementorUtils::generate_random_string()
          : substr(md5('loop-grid-' . wp_rand()), 0, 7),
        'elType' => 'widget',
        'widgetType' => 'loop-grid',
        'settings' => [
          'template_id'          => $skin,
          'post_query_post_type' => 'by_id',
          'post_query_posts_ids' => $post_ids_array,
          'posts_per_page'       => count($post_ids_array),
          'post_query_orderby'   => $post_orderby,
          'post_query_order'     => $post_order,
          'columns'              => $columns,
          'columns_tablet'       => $columns_tablet,
          'columns_mobile'       => $columns_mobile,
          'equal_height'         => $equal_height,
        ],
      ], []);

      if (!$loop_grid) {
        return '<p class="error-message">' . esc_html__('Error generating loop grid.', 'elementor-taxonomy-loop') . '</p>';
      }

      ob_start();
      $loop_grid->print_element();
      return (string) ob_get_clean();
    } catch (\Exception $e) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Elementor Taxonomy Loop Widget Error: ' . $e->getMessage());
      }
      return '<p class="error-message">' . esc_html__('Error generating loop grid.', 'elementor-taxonomy-loop') . '</p>';
    }
  }

  /**
   * AJAX handler: render a single term's Loop Grid HTML.
   * Re-validates every field — never trust the incoming payload.
   */
  public static function ajax_render_term(): void
  {
    if (!check_ajax_referer(self::AJAX_NONCE_ACTION, 'nonce', false)) {
      wp_send_json_error(['message' => __('Invalid request.', 'elementor-taxonomy-loop')], 403);
    }

    $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
    $taxonomy  = isset($_POST['taxonomy']) ? sanitize_key(wp_unslash($_POST['taxonomy'])) : '';
    $term_id   = isset($_POST['term_id']) ? absint(wp_unslash($_POST['term_id'])) : 0;
    $skin      = isset($_POST['skin']) ? absint(wp_unslash($_POST['skin'])) : 0;

    if (!post_type_exists($post_type) || !taxonomy_exists($taxonomy) || !is_object_in_taxonomy($post_type, $taxonomy)) {
      wp_send_json_error(['message' => __('Invalid context.', 'elementor-taxonomy-loop')], 400);
    }

    $term = get_term($term_id, $taxonomy);
    if (is_wp_error($term) || !$term) {
      wp_send_json_error(['message' => __('Invalid term.', 'elementor-taxonomy-loop')], 404);
    }

    if ($skin <= 0 || get_post_type($skin) !== 'elementor_library') {
      wp_send_json_error(['message' => __('Invalid loop template.', 'elementor-taxonomy-loop')], 400);
    }

    $orderby = self::sanitize_choice(
      isset($_POST['orderby']) ? sanitize_key(wp_unslash($_POST['orderby'])) : 'date',
      self::POST_ORDERBY_WHITELIST,
      'date'
    );
    $order = self::sanitize_choice(
      isset($_POST['order']) ? strtoupper(sanitize_key(wp_unslash($_POST['order']))) : 'DESC',
      self::POST_ORDER_WHITELIST,
      'DESC'
    );

    $per_term_raw = isset($_POST['per_term']) ? (int) wp_unslash($_POST['per_term']) : 6;
    if ($per_term_raw === 0 || $per_term_raw < -1) {
      $per_term_raw = -1;
    }
    // Cap to prevent abuse via crafted requests.
    $per_term = $per_term_raw === -1
      ? self::LAZY_PER_TERM_MAX
      : min($per_term_raw, self::LAZY_PER_TERM_MAX);

    $columns        = isset($_POST['columns']) ? max(1, min(12, (int) wp_unslash($_POST['columns']))) : 3;
    $columns_tablet = isset($_POST['cols_t']) ? max(1, min(12, (int) wp_unslash($_POST['cols_t']))) : 2;
    $columns_mobile = isset($_POST['cols_m']) ? max(1, min(12, (int) wp_unslash($_POST['cols_m']))) : 1;
    $equal_height   = (isset($_POST['equal']) && 'yes' === sanitize_key(wp_unslash($_POST['equal']))) ? 'yes' : 'no';

    $posts_by_term = self::fetch_post_ids_grouped_by_term(
      $post_type,
      $taxonomy,
      [$term_id],
      $orderby,
      $order,
      $per_term
    );
    $post_ids = $posts_by_term[$term_id] ?? [];

    $html = self::render_loop_grid_html(
      $post_ids,
      (string) $skin,
      $orderby,
      $order,
      $columns,
      $columns_tablet,
      $columns_mobile,
      $equal_height
    );

    wp_send_json_success(['html' => $html]);
  }
}
