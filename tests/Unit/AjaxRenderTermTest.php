<?php
/**
 * Security contract for the AJAX handler.
 *
 * Full happy-path coverage would require stubbing Elementor's loop-grid
 * render pipeline, so this suite focuses on the rejection paths — the
 * bits that matter for security: nonce check, context validation, and
 * the loop-template type check.
 */

declare(strict_types=1);

namespace Beenacle\TaxonomyLoop\Tests\Unit;

use Beenacle_Taxonomy_Loop;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class AjaxRenderTermTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();
    // wp_send_json_error() normally exits; raise a catchable exception so
    // the test can assert on the status code it would have sent.
    Functions\when('wp_send_json_error')->alias(function ($data, $status = 200) {
      throw new JsonErrorException((string) ($data['message'] ?? ''), (int) $status);
    });
    Functions\when('wp_send_json_success')->alias(function ($data = null) {
      throw new JsonSuccessException();
    });
    Functions\when('wp_unslash')->returnArg();
    Functions\when('sanitize_key')->alias(fn ($v) => preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $v)));
    Functions\when('absint')->alias(fn ($v) => max(0, (int) $v));
    Functions\when('__')->returnArg();
    $_POST = [];
  }

  protected function tearDown(): void
  {
    Monkey\tearDown();
    $_POST = [];
    parent::tearDown();
  }

  public function test_missing_nonce_returns_403(): void
  {
    Functions\when('check_ajax_referer')->justReturn(false);

    $this->expectException(JsonErrorException::class);
    $this->expectExceptionCode(403);

    Beenacle_Taxonomy_Loop::ajax_render_term();
  }

  public function test_invalid_post_type_returns_400(): void
  {
    Functions\when('check_ajax_referer')->justReturn(true);
    Functions\when('post_type_exists')->justReturn(false);
    Functions\when('taxonomy_exists')->justReturn(true);
    Functions\when('is_object_in_taxonomy')->justReturn(true);

    $_POST = [
      'post_type' => 'ghost',
      'taxonomy'  => 'category',
      'term_id'   => '1',
      'skin'      => '10',
    ];

    $this->expectException(JsonErrorException::class);
    $this->expectExceptionCode(400);

    Beenacle_Taxonomy_Loop::ajax_render_term();
  }

  public function test_taxonomy_not_registered_for_post_type_returns_400(): void
  {
    Functions\when('check_ajax_referer')->justReturn(true);
    Functions\when('post_type_exists')->justReturn(true);
    Functions\when('taxonomy_exists')->justReturn(true);
    Functions\when('is_object_in_taxonomy')->justReturn(false);

    $_POST = [
      'post_type' => 'post',
      'taxonomy'  => 'product_cat',
      'term_id'   => '1',
      'skin'      => '10',
    ];

    $this->expectException(JsonErrorException::class);
    $this->expectExceptionCode(400);

    Beenacle_Taxonomy_Loop::ajax_render_term();
  }

  public function test_non_library_skin_is_rejected(): void
  {
    Functions\when('check_ajax_referer')->justReturn(true);
    Functions\when('post_type_exists')->justReturn(true);
    Functions\when('taxonomy_exists')->justReturn(true);
    Functions\when('is_object_in_taxonomy')->justReturn(true);
    Functions\when('get_term')->justReturn((object) ['term_id' => 1, 'name' => 'X']);
    // Imagine an attacker passes a Post ID that isn't an Elementor template.
    Functions\when('get_post_type')->justReturn('post');

    $_POST = [
      'post_type' => 'post',
      'taxonomy'  => 'category',
      'term_id'   => '1',
      'skin'      => '99',
    ];

    $this->expectException(JsonErrorException::class);
    $this->expectExceptionCode(400);

    Beenacle_Taxonomy_Loop::ajax_render_term();
  }

  public function test_missing_term_returns_404(): void
  {
    Functions\when('check_ajax_referer')->justReturn(true);
    Functions\when('post_type_exists')->justReturn(true);
    Functions\when('taxonomy_exists')->justReturn(true);
    Functions\when('is_object_in_taxonomy')->justReturn(true);
    Functions\when('get_term')->justReturn(null);
    Functions\when('is_wp_error')->justReturn(false);

    $_POST = [
      'post_type' => 'post',
      'taxonomy'  => 'category',
      'term_id'   => '999999',
      'skin'      => '10',
    ];

    $this->expectException(JsonErrorException::class);
    $this->expectExceptionCode(404);

    Beenacle_Taxonomy_Loop::ajax_render_term();
  }
}

/** Test-only exceptions that stand in for wp_send_json_*()'s normal wp_die() behavior. */
final class JsonErrorException extends \RuntimeException {}
final class JsonSuccessException extends \RuntimeException {}
