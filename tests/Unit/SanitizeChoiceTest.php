<?php
/**
 * Pure-logic test for the input whitelist helper.
 */

declare(strict_types=1);

namespace Beenacle\TaxonomyLoop\Tests\Unit;

use Beenacle_Taxonomy_Loop;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class SanitizeChoiceTest extends TestCase
{
  private static function invoke($value, array $allowed, string $default): string
  {
    $method = new ReflectionMethod(Beenacle_Taxonomy_Loop::class, 'sanitize_choice');
    $method->setAccessible(true);
    return $method->invoke(null, $value, $allowed, $default);
  }

  public function test_returns_value_when_whitelisted(): void
  {
    $this->assertSame('ASC', self::invoke('ASC', ['ASC', 'DESC'], 'DESC'));
  }

  public function test_returns_default_when_not_whitelisted(): void
  {
    $this->assertSame('DESC', self::invoke('evil', ['ASC', 'DESC'], 'DESC'));
  }

  public function test_type_strict_comparison_rejects_coerced_match(): void
  {
    // 0 would loose-match 'date' under loose comparison; strict comparison must reject it.
    $this->assertSame('date', self::invoke(0, ['date', 'title'], 'date'));
  }

  public function test_rejects_empty_string(): void
  {
    $this->assertSame('name', self::invoke('', ['name', 'slug'], 'name'));
  }
}
