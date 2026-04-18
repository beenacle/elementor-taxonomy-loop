<?php
/**
 * Minimal stubs for Elementor Pro classes referenced by the widget.
 * Only what the unit tests need.
 */

declare(strict_types=1);

namespace ElementorPro;

if (!class_exists(Plugin::class)) {
  class Plugin
  {
    public $modules_manager;
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
      $this->modules_manager = new class {
        public function get_modules($name) { return null; }
      };
    }
  }
}

namespace ElementorPro\Core;

if (!class_exists(Utils::class)) {
  class Utils
  {
    public static function get_public_post_types(): array { return []; }
  }
}

namespace ElementorPro\Modules\QueryControl\Controls;

if (!class_exists(Template_Query::class)) {
  class Template_Query { public const CONTROL_ID = 'query'; }
}

namespace ElementorPro\Modules\QueryControl;

if (!class_exists(Module::class)) {
  class Module { public const QUERY_OBJECT_LIBRARY_TEMPLATE = 'library_template'; }
}

namespace ElementorPro\Modules\LoopBuilder\Documents;

if (!class_exists(Loop::class)) {
  class Loop { public static function get_type(): string { return 'loop-item'; } }
}
