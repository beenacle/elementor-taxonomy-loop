<?php
/**
 * Minimal stubs for Elementor core classes referenced by the widget.
 * Only what the unit tests need — not a full API re-implementation.
 */

declare(strict_types=1);

namespace Elementor;

if (!class_exists(Widget_Base::class)) {
  abstract class Widget_Base
  {
    protected function get_settings_for_display(): array { return []; }
    protected function get_data($key = '') { return []; }
    protected function add_render_attribute($element, $key = null, $value = null): self { return $this; }
    protected function start_controls_section(string $id, array $args = []): void {}
    protected function end_controls_section(): void {}
    protected function add_control(string $id, array $args = []): void {}
    protected function add_responsive_control(string $id, array $args = []): void {}
    protected function add_group_control(string $type, array $args = []): void {}
  }
}

if (!class_exists(Controls_Manager::class)) {
  class Controls_Manager
  {
    public const SELECT      = 'select';
    public const TEXT        = 'text';
    public const SWITCHER    = 'switcher';
    public const NUMBER      = 'number';
    public const SLIDER      = 'slider';
    public const DIMENSIONS  = 'dimensions';
    public const COLOR       = 'color';
    public const CHOOSE      = 'choose';
    public const TAB_CONTENT = 'content';
    public const TAB_STYLE   = 'style';
  }
}

if (!class_exists(Group_Control_Border::class)) {
  class Group_Control_Border { public static function get_type(): string { return 'border'; } }
}
if (!class_exists(Group_Control_Typography::class)) {
  class Group_Control_Typography { public static function get_type(): string { return 'typography'; } }
}

if (!class_exists(Utils::class)) {
  class Utils
  {
    public static function generate_random_string(): string { return 'test123'; }
  }
}

if (!class_exists(Plugin::class)) {
  class Plugin
  {
    public $editor;
    public $preview;
    public $elements_manager;

    private static ?Plugin $instance = null;

    public static function instance(): self
    {
      if (null === self::$instance) {
        self::$instance = new self();
      }
      return self::$instance;
    }

    public function __construct()
    {
      $this->editor = new class { public function is_edit_mode(): bool { return false; } };
      $this->preview = new class { public function is_preview_mode(): bool { return false; } };
      $this->elements_manager = new class {
        public function create_element_instance(array $data, array $args = [])
        {
          return null;
        }
      };
    }
  }
}
