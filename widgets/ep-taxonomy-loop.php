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

class Ep_Taxonomy_Loop extends \Elementor\Widget_Base
{

  public function get_name(): string
  {
    return 'ep_taxonomy_loop';
  }

  public function get_title(): string
  {
    return esc_html__('EP Taxonomy Loop', 'executive-elementor-addons');
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
    return ['loop', 'executive', 'taxonomy', 'post', 'custom'];
  }

  public function get_script_depends(): array
  {
    return ['ep-taxonomy-loop-script'];
  }

  public function get_style_depends(): array
  {
    return ['ep-taxonomy-loop-style', 'elementor-pro'];
  }

  // Register Controls
  protected function register_controls(): void
  {
    //Get Post Types & public taxanomies

    $supported_taxonomies = [];
    $public_types = Pro_Utils::get_public_post_types();

    foreach ($public_types as $type => $title) {
      $taxonomies = get_object_taxonomies($type, 'objects'); //To get taxanomies only attached with public postypes
      foreach ($taxonomies as $key => $tax) {
        if (! in_array($tax->name, $supported_taxonomies)) {
          $label = $tax->label . ' (' . $tax->name . ')';
          $supported_taxonomies[$tax->name] = $label;
        }
      }
    }

    //Content Area-----------------------------------
    $this->start_controls_section(
      'content_section',
      [
        'label' => esc_html__('Select Content', 'executive-elementor-addons'),
        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
      ]
    );
    $this->add_control(
      'post_type',
      [
        'label' => __('Select Post Type', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'post',
        'options' => $public_types,
      ]
    );
    $this->add_control(
      'taxonomy',
      [
        'label' => __('Select Taxonomy', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'options' => $supported_taxonomies,
        'default' => 'category',
      ]
    );
    $this->add_control(
      'loop_skin',
      [
        'label' => __('Select Loop Skin', 'executive-elementor-addons'),
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
        'label' => esc_html__('Hide Empty Terms', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_on' => esc_html__('Yes', 'executive-elementor-addons'),
        'label_off' => esc_html__('No', 'executive-elementor-addons'),
        'return_value' => 'yes',
        'default' => 'no',
      ]
    );
    $this->add_control(
      'loop_divider',
      [
        'label' => esc_html__('Show Divider', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_on' => esc_html__('Show', 'executive-elementor-addons'),
        'label_off' => esc_html__('Hide', 'executive-elementor-addons'),
        'return_value' => 'yes',
        'default' => 'no',
      ]
    );
    $this->add_control(
      'include_terms',
      [
        'label' => __('Include Terms (IDs)', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'ai' => [
          'active' => false,
        ],
      ]
    );
    $this->add_control(
      'exclude_terms',
      [
        'label' => __('Exclude Terms (IDs)', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'ai' => [
          'active' => false,
        ],
      ]
    );
    $this->add_control(
      'orderby',
      [
        'label' => __('Order Terms By', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'name',
        'options' => [
          'name' => __('Name', 'executive-elementor-addons'),
          'id' => __('ID', 'executive-elementor-addons'),
          'slug' => __('Slug', 'executive-elementor-addons'),
          'menu_order' => __('Menu Order', 'executive-elementor-addons'),
          'include' => __('Include', 'executive-elementor-addons'),
        ],
      ]
    );
    $this->add_control(
      'order',
      [
        'label' => __('Order Direction', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'ASC',
        'options' => [
          'ASC' => __('Ascending', 'executive-elementor-addons'),
          'DESC' => __('Descending', 'executive-elementor-addons'),
        ],
      ]
    );
    $this->end_controls_section();

    // Style Area-----------------------------------
    $this->start_controls_section(
      'ep_main_category_style',
      [
        'label' => esc_html__('Items Settings', 'executive-elementor-addons'),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
      ]
    );
    $this->add_responsive_control(
      'category_gap',
      [
        'label' => esc_html__('Category Gap', 'executive-elementor-addons'),
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
        'label' => esc_html__('Content Gap', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .ep-taxonomy-posts .ep-posts-list' => 'margin-top: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_group_control(
      \Elementor\Group_Control_Border::get_type(),
      [
        'name' => 'category_border',
        'selector' => '{{WRAPPER}} .ep-taxonomy-posts',
      ]
    );
    $this->add_responsive_control(
      'category_border_radius',
      [
        'label' => esc_html__('Border Radius', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .ep-taxonomy-posts' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
        'condition' => [
          'category_border_border!' => 'none',
        ],
      ]
    );
    $this->add_responsive_control(
      'category_padding',
      [
        'label' => esc_html__('Padding', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .ep-taxonomy-posts' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
      ]
    );

    $this->end_controls_section();
    $this->start_controls_section(
      'ep_term_style',
      [
        'label' => esc_html__('Category Styling', 'executive-elementor-addons'),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
      ]
    );
    $this->add_group_control(
      \Elementor\Group_Control_Border::get_type(),
      [
        'name' => 'term_border',
        'selector' => '{{WRAPPER}} .ep-taxonomy-posts .ep-term-content',
      ]
    );
    $this->add_responsive_control(
      'term_border_radius',
      [
        'label' => esc_html__('Border Radius', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .ep-taxonomy-posts .ep-term-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
        'condition' => [
          'term_border_border!' => 'none',
        ],
      ]
    );
    $this->add_responsive_control(
      'term_padding',
      [
        'label' => esc_html__('Padding', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .ep-taxonomy-posts .ep-term-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
      ]
    );
    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'label' => esc_html__('Title', 'executive-elementor-addons'),
        'name' => 'term_title_typography',
        'selector' => '{{WRAPPER}} .ep-term-content .ep-term-title',
      ]
    );
    $this->add_control(
      'term_title_color',
      [
        'label' => esc_html__('Title Color', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::COLOR,
        'selectors' => [
          '{{WRAPPER}} .ep-term-content .ep-term-title' => 'color: {{VALUE}}',
        ],
      ]
    );
    $this->add_control(
      'term_title_alignment',
      [
        'label' => esc_html__('Alignment', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::CHOOSE,
        'options' => [
          'left' => [
            'title' => esc_html__('Left', 'textexecutive-elementor-addonsdomain'),
            'icon' => 'eicon-text-align-left',
          ],
          'center' => [
            'title' => esc_html__('Center', 'executive-elementor-addons'),
            'icon' => 'eicon-text-align-center',
          ],
          'right' => [
            'title' => esc_html__('Right', 'executive-elementor-addons'),
            'icon' => 'eicon-text-align-right',
          ],
        ],
        'default' => 'left',
        'toggle' => true,
        'selectors' => [
          '{{WRAPPER}} .ep-term-content .ep-term-title' => 'text-align: {{VALUE}};',
        ],
      ]
    );
    $this->end_controls_section();
    $this->start_controls_section(
      'ep_loop_style',
      [
        'label' => esc_html__('Loop Controls', 'executive-elementor-addons'),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
      ]
    );
    $this->add_responsive_control(
      'columns',
      [
        'label' => esc_html__('Loop Columns', 'executive-elementor-addons'),
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
        'label' => esc_html__('Columns Gap', 'executive-elementor-addons'),
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
          '{{WRAPPER}} .ep-posts-list .elementor-widget-loop-grid' => '--grid-column-gap: {{SIZE}}{{UNIT}}',
        ],
      ]
    );
    $this->add_responsive_control(
      'row_gap',
      [
        'label' => esc_html__('Row Gap', 'executive-elementor-addons'),
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
          '{{WRAPPER}} .ep-posts-list .elementor-widget-loop-grid' => '--grid-row-gap: {{SIZE}}{{UNIT}}',
        ],
      ]
    );
    $this->add_control(
      'equal_height',
      [
        'label' => esc_html__('Equal height', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_off' => esc_html__('Off', 'executive-elementor-addons'),
        'label_on' => esc_html__('On', 'executive-elementor-addons'),
        'selectors' => [
          '{{WRAPPER}} .ep-posts-list .elementor-widget-loop-grid .elementor-loop-container' => 'grid-auto-rows: 1fr',
          // `.elementor-section-wrap` exists only when editing the loop template.
          '{{WRAPPER}} .ep-posts-list .elementor-widget-loop-grid .e-loop-item > .elementor-section,
           {{WRAPPER}} .ep-posts-list .elementor-widget-loop-grid .e-loop-item > .elementor-section > .elementor-container,
           {{WRAPPER}} .ep-posts-list .elementor-widget-loop-grid .e-loop-item > .e-con,
           {{WRAPPER}} .ep-posts-list .elementor-widget-loop-grid .e-loop-item .elementor-section-wrap  > .e-con' => 'height: 100%',
        ],
      ]
    );
    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'label' => esc_html__('Not Found', 'executive-elementor-addons'),
        'name' => 'not_found_typography',
        'selector' => '{{WRAPPER}} .ep-taxonomy-posts .ep-not-found',
      ]
    );
    $this->add_control(
      'message_color',
      [
        'label' => esc_html__('Not Found Color', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::COLOR,
        'selectors' => [
          '{{WRAPPER}} .ep-taxonomy-posts .ep-not-found' => 'color: {{VALUE}}',
        ],
      ]
    );
    $this->end_controls_section();
    $this->start_controls_section(
      'ep_loop_divider_style',
      [
        'label' => esc_html__('Divider Style', 'executive-elementor-addons'),
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
        'label' => esc_html__('Width', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'default' => [
          'size' => 200,
          'unit' => 'px',
        ],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .ep-term-content .ep-divider' => 'width: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_responsive_control(
      'divider_height',
      [
        'label' => esc_html__('Height', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'default' => [
          'size' => 3,
          'unit' => 'px',
        ],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .ep-term-content .ep-divider' => 'height: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_control(
      'divider_color',
      [
        'label' => esc_html__('Color', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::COLOR,
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .ep-term-content .ep-divider' => 'background-color: {{VALUE}}',
        ],
      ]
    );
    $this->add_responsive_control(
      'divider_gap',
      [
        'label' => esc_html__('Top Spacing', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .ep-term-content .ep-divider' => 'margin-top: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_responsive_control(
      'divider_radius',
      [
        'label' => esc_html__('Border Radius', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => ['px', 'em', 'rem', 'custom'],
        'selectors' => [
          '{{WRAPPER}} .elementor-widget-container .ep-term-content .ep-divider' => 'border-radius: {{SIZE}}{{UNIT}};',
        ],
      ]
    );
    $this->add_control(
      'divider_alignment',
      [
        'label' => esc_html__('Alignment', 'executive-elementor-addons'),
        'type' => \Elementor\Controls_Manager::CHOOSE,
        'options' => [
          'margin-right: auto;' => [
            'title' => esc_html__('Left', 'executive-elementor-addons'),
            'icon' => 'eicon-text-align-left',
          ],
          'margin:auto;' => [
            'title' => esc_html__('Center', 'executive-elementor-addons'),
            'icon' => 'eicon-text-align-center',
          ],
          'margin-left: auto;' => [
            'title' => esc_html__('Right', 'executive-elementor-addons'),
            'icon' => 'eicon-text-align-right',
          ],
        ],
        'default' => 'margin-right: auto;',
        'toggle' => true,
        'selectors' => [
          '{{WRAPPER}} .ep-term-content .ep-divider' => '{{VALUE}}',
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


    // Fetch terms for the specified taxonomy
    $terms = get_terms([
      'taxonomy'   => $taxonomy,
      'hide_empty' => (isset($settings['show_empty']) && 'yes' === $settings['show_empty']) ? true : false,
      'include'    => $settings['include_terms'],
      'exclude'    => $settings['exclude_terms'],
      'orderby'    => $settings['orderby'],
      'order'      => $settings['order'],
    ]);

    if (!empty($terms) && !is_wp_error($terms)) {
      foreach ($terms as $term) {
        echo '<div class="ep-taxonomy-posts ep-taxonomy-posts-' . esc_attr($term->term_id) . '">';
        echo '<div class="ep-term-content">';
        echo '<h2 class="ep-term-title">' . esc_html($term->name) . '</h2>';
        if ($divider == 'yes') {
          echo '<hr class="ep-divider" />';
        }
        echo '</div>';

        // Query posts for the current term
        $posts = new \WP_Query([
          'post_type'      => $post_type,
          'posts_per_page' => -1,
          'tax_query'      => [
            [
              'taxonomy' => $taxonomy,
              'field'    => 'term_id',
              'terms'    => $term->term_id,
            ],
          ],
        ]);

        $post_ids_array = wp_list_pluck($posts->posts, 'ID');
        //Start of Loop Grid
        echo '<div class="ep-posts-list">';
        if (count($post_ids_array) > 0) {
          //Generate Loop Grid for the current term
          $loop_builder_module = ProPlugin::instance()->modules_manager->get_modules('loop-builder');
          if (! $loop_builder_module || ! $loop_builder_module->is_active()) {
            echo '<p>Loop Builder module not available.</p>';
            return;
          }

          // Proceed if loop template is valid
          if (! empty($skin) && get_post_type($skin) === 'elementor_library') {

            $loop_grid = Elementor::instance()->elements_manager->create_element_instance([
              'id' => 'loop-grid-' . $term->term_id,
              'elType' => 'widget',
              'widgetType' => 'loop-grid',
              'settings' => [
                'template_id' => $skin,
                'post_query_post_type' => 'by_id',
                'post_query_posts_ids' => $post_ids_array,
                // Layout controls
                'columns' => $settings['columns'] ?? 3,
                "columns_tablet" => $settings['columns_tablet'] ?? 2,
                "columns_mobile" => $settings['columns_mobile'] ?? 1,
                'equal_height' => $settings['equal_height'] ?? 'no',
              ],
            ], []);

            $loop_grid->print_element();
          }
        } else {
          echo '<p class="ep-not-found">No posts found for this term.</p>';
        }
        echo '</div>';
        //End of Loop Grid
        echo '</div>';
      }
    } else {
      echo '<p>No terms found for this taxonomy.</p>';
    }
  }
}
