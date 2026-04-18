<?php
/**
 * Pure-logic tests for parse_term_ids().
 */

declare(strict_types=1);

namespace Beenacle\TaxonomyLoop\Tests\Unit;

use Beenacle_Taxonomy_Loop;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ParseTermIdsTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();
    // absint() is what the widget uses to coerce each split fragment.
    Functions\when('absint')->alias(function ($value) {
      $int = (int) $value;
      return $int < 0 ? 0 : $int;
    });
  }

  protected function tearDown(): void
  {
    Monkey\tearDown();
    parent::tearDown();
  }

  private static function invoke($value): array
  {
    $method = new ReflectionMethod(Beenacle_Taxonomy_Loop::class, 'parse_term_ids');
    $method->setAccessible(true);
    return $method->invoke(null, $value);
  }

  public function test_empty_string_returns_empty_array(): void
  {
    $this->assertSame([], self::invoke(''));
    $this->assertSame([], self::invoke('   '));
  }

  public function test_non_string_input_returns_empty_array(): void
  {
    $this->assertSame([], self::invoke(null));
    $this->assertSame([], self::invoke(123));
    $this->assertSame([], self::invoke([1, 2, 3]));
  }

  public function test_splits_on_commas_and_whitespace(): void
  {
    $this->assertSame([1, 2, 3], self::invoke('1, 2 3'));
    $this->assertSame([1, 2, 3], self::invoke("1\n2\t3"));
  }

  public function test_deduplicates_and_drops_zero(): void
  {
    // absint('abc') returns 0; zeros get filtered out by array_filter.
    $this->assertSame([5, 7], self::invoke('5, 5, 7, abc, 0'));
  }

  public function test_coerces_negative_values_to_zero_then_filters(): void
  {
    $this->assertSame([4], self::invoke('-1, 4, -7'));
  }
}
