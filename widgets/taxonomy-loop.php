<?php
if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

use ElementorPro\Plugin as ProPlugin;
use Elementor\Plugin as Elementor;
use ElementorPro\Core\Utils as Pro_Utils;
use ElementorPro\Modules\QueryControl\Controls\Template_Query;
use ElementorPro\Modules\QueryControl\Module as QueryControlModule;
use ElementorPro\Modules\LoopBuilder\Documents\Loop as LoopDocument;

class Taxonomy_Loop extends \Elementor\Widget_Base
{

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
    return ['basic'];
  }

  public function get_keywords(): array
  {
    return ['loop', 'taxonomy', 'post', 'custom'];
  }

  public function get_script_depends(): array
  {
    return ['taxonomy-loop-script'];
  }

  public function get_style_depends(): array
  {
    return ['taxonomy-loop-style', 'elementor-pro'];
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
        if (!in_array($tax->name, $supported_taxonomies)) {
          $label = $tax->label . ' (' . $tax->name . ')';
          $supported_taxonomies[$tax->name] = $label;
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
        'label' => __('Select Post Type', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'post',
        'options' => $public_types,
      ]
    );
    $this->add_control(
      'taxonomy',
      [
        'label' => __('Select Taxonomy', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'options' => $supported_taxonomies,
        'default' => 'category',
      ]
    );
    $this->add_control(
      'loop_skin',
      [
        'label' => __('Select Loop Skin', 'elementor-taxonomy-loop'),
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
      'show_empty',
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
        'label' => __('Include Terms (IDs)', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'ai' => [
          'active' => false,
        ],
      ]
    );
    $this->add_control(
      'exclude_terms',
      [
        'label' => __('Exclude Terms (IDs)', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'ai' => [
          'active' => false,
        ],
      ]
    );
    $this->add_control(
      'orderby',
      [
        'label' => __('Order Terms By', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'name',
        'options' => [
          'name' => __('Name', 'elementor-taxonomy-loop'),
          'id' => __('ID', 'elementor-taxonomy-loop'),
          'slug' => __('Slug', 'elementor-taxonomy-loop'),
          'menu_order' => __('Menu Order', 'elementor-taxonomy-loop'),
          'include' => __('Include', 'elementor-taxonomy-loop'),
        ],
      ]
    );
    $this->add_control(
      'order',
      [
        'label' => __('Order Direction', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'ASC',
        'options' => [
          'ASC' => __('Ascending', 'elementor-taxonomy-loop'),
          'DESC' => __('Descending', 'elementor-taxonomy-loop'),
        ],
      ]
    );

    $this->add_control(
      'post_orderby',
      [
        'label' => __('Order Posts By', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'date',
        'options' => [
          'date' => __('Date', 'elementor-taxonomy-loop'),
          'title' => __('Title', 'elementor-taxonomy-loop'),
          'ID' => __('ID', 'elementor-taxonomy-loop'),
          'menu_order' => __('Menu Order', 'elementor-taxonomy-loop'),
          'rand' => __('Random', 'elementor-taxonomy-loop'),
        ],
      ]
    );

    $this->add_control(
      'post_order',
      [
        'label' => __('Post Order Direction', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'DESC',
        'options' => [
          'ASC' => __('Ascending', 'elementor-taxonomy-loop'),
          'DESC' => __('Descending', 'elementor-taxonomy-loop'),
        ],
      ]
    );

    // Title Prefix and Suffix Controls
    $this->add_control(
      'title_prefix',
      [
        'label' => __('Title Prefix', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'placeholder' => __('Enter text to appear before the title', 'elementor-taxonomy-loop'),
        'label_block' => true,
      ]
    );

    $this->add_control(
      'title_suffix',
      [
        'label' => __('Title Suffix', 'elementor-taxonomy-loop'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'placeholder' => __('Enter text to appear after the title', 'elementor-taxonomy-loop'),
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
        'devices' => ['desktop', 'tablet', 'mobile'],
        'default' => '3',
        'tablet_default' => '2',
        'mobile_default' => '1',
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
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .elementor-loop-container ' => '--grid-column-gap: {{SIZE}}{{UNIT}};',

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
          'margin-right: auto;' => [
            'title' => esc_html__('Left', 'elementor-taxonomy-loop'),
            'icon' => 'eicon-text-align-left',
          ],
          'margin:auto;' => [
            'title' => esc_html__('Center', 'elementor-taxonomy-loop'),
            'icon' => 'eicon-text-align-center',
          ],
          'margin-left: auto;' => [
            'title' => esc_html__('Right', 'elementor-taxonomy-loop'),
            'icon' => 'eicon-text-align-right',
          ],
        ],
        'default' => 'margin-right: auto;',
        'toggle' => true,
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
    $taxonomy = $settings['taxonomy'];
    $post_type = $settings['post_type'];
    $skin = $settings['loop_skin'];
    $divider = $settings['loop_divider'];
    $title_prefix = $settings['title_prefix'];
    $title_suffix = $settings['title_suffix'];

    // Generate cache key based on settings
    $cache_key = 'elementor_taxonomy_loop_' . md5(serialize([
      'taxonomy' => $taxonomy,
      'post_type' => $post_type,
      'show_empty' => $settings['show_empty'],
      'include_terms' => $settings['include_terms'],
      'exclude_terms' => $settings['exclude_terms'],
      'orderby' => $settings['orderby'],
      'order' => $settings['order']
    ]));

    // Try to get cached terms
    $terms = get_transient($cache_key);

    if (false === $terms) {
      // Fetch terms for the specified taxonomy
      $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => (isset($settings['show_empty']) && 'yes' === $settings['show_empty']) ? true : false,
        'include'    => $settings['include_terms'],
        'exclude'    => $settings['exclude_terms'],
        'orderby'    => $settings['orderby'],
        'order'      => $settings['order'],
      ]);

      if (!is_wp_error($terms)) {
        set_transient($cache_key, $terms, HOUR_IN_SECONDS);
      }
    }

    if (!empty($terms) && !is_wp_error($terms)) {
      foreach ($terms as $term) {
        echo '<div class="taxonomy-posts taxonomy-posts-' . esc_attr($term->term_id) . '">';
        echo '<div class="term-content">';
        echo '<h2 class="term-title">' .
             esc_html($title_prefix) .
             esc_html($term->name) .
             esc_html($title_suffix) .
             '</h2>';
        if ($divider == 'yes') {
          echo '<hr class="divider" />';
        }
        echo '</div>';

        // Query posts for the current term
        $posts = new \WP_Query([
          'post_type'      => $post_type,
          'posts_per_page' => -1,
          'orderby'        => $settings['post_orderby'],
          'order'          => $settings['post_order'],
          'tax_query'      => [
            [
              'taxonomy' => $taxonomy,
              'field'    => 'term_id',
              'terms'    => $term->term_id,
            ],
          ],
          'no_found_rows' => true, // Optimize query by not counting rows
          'cache_results' => true, // Enable query caching
        ]);

        $post_ids_array = wp_list_pluck($posts->posts, 'ID');
        echo '<div class="posts-list">';
        if (count($post_ids_array) > 0) {
          //Generate Loop Grid for the current term
          $loop_builder_module = ProPlugin::instance()->modules_manager->get_modules('loop-builder');
          if (!$loop_builder_module || !$loop_builder_module->is_active()) {
            echo '<p class="error-message">' . esc_html__('Loop Builder module not available.', 'elementor-taxonomy-loop') . '</p>';
            return;
          }

          // Proceed if loop template is valid
          if (!empty($skin) && get_post_type($skin) === 'elementor_library') {
            try {
              $loop_grid = Elementor::instance()->elements_manager->create_element_instance([
                'id' => 'loop-grid-' . $term->term_id,
                'elType' => 'widget',
                'widgetType' => 'loop-grid',
                'settings' => [
                  'template_id' => $skin,
                  'post_query_post_type' => 'by_id',
                  'post_query_posts_ids' => $post_ids_array,
                  'columns' => $settings['columns'] ?? 3,
                  "columns_tablet" => $settings['columns_tablet'] ?? 2,
                  "columns_mobile" => $settings['columns_mobile'] ?? 1,
                  'equal_height' => $settings['equal_height'] ?? 'no',
                ],
              ], []);

              if ($loop_grid) {
                $loop_grid->print_element();
              }
            } catch (\Exception $e) {
              error_log('Elementor Taxonomy Loop Widget Error: ' . $e->getMessage());
              echo '<p class="error-message">' . esc_html__('Error generating loop grid.', 'elementor-taxonomy-loop') . '</p>';
            }
          }
        } else {
          echo '<p class="not-found">' . esc_html__('No posts found for this term.', 'elementor-taxonomy-loop') . '</p>';
        }
        echo '</div>';
        echo '</div>';
      }
    } else {
      echo '<p class="error-message">' . esc_html__('No terms found for this taxonomy.', 'elementor-taxonomy-loop') . '</p>';
    }
  }
}
