<?php
/**
 * Regression coverage for the starvation bug fix + object-cache behavior.
 *
 * The old consolidated-query approach would pull `per_term * count(terms)`
 * posts in one shot and bucket them in PHP; if the top-N posts all belonged
 * to one term, later terms received zero posts even when they had matching
 * rows. The current implementation runs one bounded query per term — this
 * suite asserts that contract and the wp_cache_* memoization path.
 */

declare(strict_types=1);

namespace Beenacle\TaxonomyLoop\Tests\Unit;

use Beenacle_Taxonomy_Loop;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class FetchPostIdsGroupedByTermTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();
    Functions\when('wp_cache_get_last_changed')->justReturn('v1');
  }

  protected function tearDown(): void
  {
    Monkey\tearDown();
    parent::tearDown();
  }

  private static function invoke(
    string $post_type,
    string $taxonomy,
    array $term_ids,
    string $orderby,
    string $order,
    int $per_term
  ): array {
    $method = new ReflectionMethod(Beenacle_Taxonomy_Loop::class, 'fetch_post_ids_grouped_by_term');
    $method->setAccessible(true);
    return $method->invoke(null, $post_type, $taxonomy, $term_ids, $orderby, $order, $per_term);
  }

  public function test_runs_one_bounded_query_per_term(): void
  {
    Functions\when('wp_cache_get')->justReturn(false);
    Functions\when('wp_cache_set')->justReturn(true);

    // The core assertion: get_posts() must receive a single-term tax_query
    // for each term, with posts_per_page capped at $per_term. This is what
    // prevents the starvation bug from coming back.
    $per_term_seen = [];
    $single_term_tax_query_seen = [];

    Functions\expect('get_posts')
      ->times(3)
      ->andReturnUsing(function (array $args) use (&$per_term_seen, &$single_term_tax_query_seen) {
        $per_term_seen[] = $args['posts_per_page'];
        $single_term_tax_query_seen[] =
          isset($args['tax_query'][0]['terms']) &&
          is_array($args['tax_query'][0]['terms']) &&
          count($args['tax_query'][0]['terms']) === 1;

        $term_id = (int) $args['tax_query'][0]['terms'][0];
        // Simulate skewed distribution: term 1 has lots of posts, term 2 has one, term 3 has none.
        switch ($term_id) {
          case 1: return [101, 102, 103, 104, 105, 106];
          case 2: return [201];
          case 3: return [];
        }
        return [];
      });

    $result = self::invoke('post', 'category', [1, 2, 3], 'date', 'DESC', 6);

    $this->assertSame([
      1 => [101, 102, 103, 104, 105, 106],
      2 => [201],
      3 => [],
    ], $result);

    $this->assertSame([6, 6, 6], $per_term_seen, 'posts_per_page must equal $per_term for every call');
    $this->assertSame([true, true, true], $single_term_tax_query_seen, 'tax_query must target exactly one term per call');
  }

  public function test_dedupes_term_ids_before_querying(): void
  {
    Functions\when('wp_cache_get')->justReturn(false);
    Functions\when('wp_cache_set')->justReturn(true);

    Functions\expect('get_posts')
      ->times(2) // 1 and 2, not 3 duplicates
      ->andReturnUsing(fn (array $args) => [(int) $args['tax_query'][0]['terms'][0] * 10]);

    $result = self::invoke('post', 'category', [1, 2, 1, 2, 1], 'date', 'DESC', 5);

    $this->assertSame([1 => [10], 2 => [20]], $result);
  }

  public function test_cache_hit_short_circuits_get_posts(): void
  {
    Functions\when('wp_cache_get')->justReturn([999, 888]);
    Functions\expect('get_posts')->never();
    Functions\expect('wp_cache_set')->never();

    $result = self::invoke('post', 'category', [42], 'date', 'DESC', 5);

    $this->assertSame([42 => [999, 888]], $result);
  }

  public function test_cache_miss_populates_cache(): void
  {
    Functions\when('wp_cache_get')->justReturn(false);
    Functions\when('get_posts')->justReturn([7, 8, 9]);

    Functions\expect('wp_cache_set')
      ->once()
      ->with(
        \Mockery::type('string'),
        [7, 8, 9],
        'elementor_taxonomy_loop'
      )
      ->andReturn(true);

    $result = self::invoke('post', 'category', [42], 'date', 'DESC', 5);
    $this->assertSame([42 => [7, 8, 9]], $result);
  }

  public function test_empty_term_list_returns_empty_array(): void
  {
    Functions\expect('get_posts')->never();
    Functions\when('wp_cache_get')->justReturn(false);
    Functions\when('wp_cache_set')->justReturn(true);

    $this->assertSame([], self::invoke('post', 'category', [], 'date', 'DESC', 5));
  }
}
