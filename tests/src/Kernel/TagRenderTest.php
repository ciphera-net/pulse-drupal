<?php

namespace Drupal\Tests\pulse_analytics\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\pulse_analytics\TagBuilder;

/**
 * Renders the tag through Drupal and reads the markup back.
 *
 * 🔴 This is the test that matters, and the unit tests are not a substitute for
 * it. They assert the render ARRAY; this asserts the HTML a visitor receives.
 * The gap between those two is exactly where a defect hides — whether
 * `'defer' => TRUE` comes out as `defer`, `defer="defer"` or `defer=""` is
 * decided by Drupal's renderer, not by us, and no amount of array-shape
 * assertion can see it.
 *
 * ⚠️ Test metadata here is doc-comment only, with no PHPUnit attributes,
 * and that is a measured decision rather than an oversight.
 *
 * Drupal 10.6 pins PHPUnit ^9.6, where the attribute classes do not exist:
 * phpstan reports that the attribute class does not exist and the drupal.org
 * CI job for Drupal 10 goes red. Drupal 11.4 pins ^11.5, which merely emits a
 * deprecation notice for doc-comment metadata. A red build on one supported
 * core version beats a cosmetic notice on another.
 *
 * Revisit when the core floor moves past Drupal 10, which reaches end of life
 * in December 2026, and switch to attributes then — that is what core 11
 * already does everywhere.
 *
 * @group pulse_analytics
 */
class TagRenderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'pulse_analytics'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['pulse_analytics']);
  }

  /**
   * Renders html_head pairs the way Drupal's page renderer does.
   *
   * @param array $tags
   *   [render array, key] pairs from TagBuilder::buildHeadTags().
   *
   * @return string
   *   The concatenated markup.
   */
  protected function renderTags(array $tags) {
    $renderer = $this->container->get('renderer');
    $out = '';
    foreach ($tags as $pair) {
      $element = $pair[0];
      $out .= (string) $renderer->renderInIsolation($element);
    }
    return trim($out);
  }

  /**
   * The emitted tag is byte for byte the one the Pulse install panel shows.
   */
  public function testEmitsTheLiteralPulseTag() {
    $this->assertSame(
      '<script defer data-domain="example.com" src="https://js.ciphera.net/script.js"></script>',
      $this->renderTags(TagBuilder::buildHeadTags('example.com'))
    );
  }

  /**
   * With no domain, the tag ships without the attribute and auto-detects.
   */
  public function testEmitsNoDataDomainWhenAutoDetecting() {
    $this->assertSame(
      '<script defer src="https://js.ciphera.net/script.js"></script>',
      $this->renderTags(TagBuilder::buildHeadTags(''))
    );
  }

  /**
   * A custom API origin rides on the tag, not on an inline script.
   */
  public function testEmitsDataApi() {
    $this->assertSame(
      '<script defer data-domain="example.com" data-api="https://proxy.example.com" src="https://js.ciphera.net/script.js"></script>',
      $this->renderTags(TagBuilder::buildHeadTags('example.com', FALSE, 'https://proxy.example.com/'))
    );
  }

  /**
   * The companion is a separate tag with no domain of its own.
   */
  public function testEmitsTheCompanionAsSeparateTag() {
    $html = $this->renderTags(TagBuilder::buildHeadTags('example.com', TRUE));
    $this->assertStringContainsString(
      '<script defer src="https://js.ciphera.net/script.interactions.js"></script>',
      $html
    );
    $this->assertSame(1, substr_count($html, 'data-domain='));
  }

  /**
   * Nothing this module emits is an inline script.
   *
   * The whole point of html_head over an inline bootstrap is that a site with a
   * strict Content-Security-Policy needs no nonce and no 'unsafe-inline' for
   * this module. If a future change starts emitting inline JavaScript, that
   * claim in README.md silently stops being true — so it is pinned here.
   */
  public function testEmitsNoInlineScript() {
    $html = $this->renderTags(TagBuilder::buildHeadTags('example.com', TRUE, 'https://proxy.example.com'));
    $this->assertStringNotContainsString('</script><script', str_replace('></script>', '>@@</script>', $html));
    foreach (explode("\n", $html) as $line) {
      if (trim($line) === '') {
        continue;
      }
      $this->assertMatchesRegularExpression('#^<script [^>]*src="https://js\.ciphera\.net/[^"]+"></script>$#', trim($line));
    }
  }

  /**
   * The hook attaches the tag and the config cache tag.
   *
   * The cache tag is not decoration: without it, changing the domain leaves
   * every already-rendered page serving the old one, and because the page still
   * works nothing looks broken.
   */
  public function testHookAttachesTagsAndTheConfigCacheTag() {
    $this->config('pulse_analytics.settings')->set('domain', 'example.com')->save();

    $attachments = [];
    pulse_analytics_page_attachments($attachments);

    $this->assertContains('config:pulse_analytics.settings', $attachments['#cache']['tags']);
    $this->assertCount(1, $attachments['#attached']['html_head']);
    $this->assertSame(
      'example.com',
      $attachments['#attached']['html_head'][0][0]['#attributes']['data-domain']
    );
  }

  /**
   * The companion setting reaches the page.
   */
  public function testHookHonoursTheCompanionSetting() {
    $this->config('pulse_analytics.settings')
      ->set('domain', 'example.com')
      ->set('companion', TRUE)
      ->save();

    $attachments = [];
    pulse_analytics_page_attachments($attachments);

    $this->assertCount(2, $attachments['#attached']['html_head']);
  }

  /**
   * An empty domain is a working install, not a disabled one.
   */
  public function testHookStillAttachesWithNoDomainConfigured() {
    $attachments = [];
    pulse_analytics_page_attachments($attachments);

    $this->assertCount(1, $attachments['#attached']['html_head']);
    $this->assertArrayNotHasKey(
      'data-domain',
      $attachments['#attached']['html_head'][0][0]['#attributes']
    );
  }

}
