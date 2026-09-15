<?php

namespace Drupal\Tests\pulse_analytics\Unit;

use Drupal\pulse_analytics\TagBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the tag builder.
 *
 * TagBuilder touches no Drupal service, so these assertions run without a
 * container. What they cannot prove is what the render arrays below turn into
 * once Drupal renders them — that is TagRenderTest's job, and it is the check
 * that actually matters.
 *
 * @group pulse_analytics
 */
class TagBuilderTest extends UnitTestCase {

  /**
   * Normalising keeps the host and drops everything around it.
   */
  public function testNormalizeDomainStripsEverythingButTheHost() {
    $this->assertSame('example.com', TagBuilder::normalizeDomain('https://example.com/docs?a=1#b'));
    $this->assertSame('example.com', TagBuilder::normalizeDomain('http://example.com:8080'));
    $this->assertSame('example.com', TagBuilder::normalizeDomain('  ExAmPle.COM  '));
    $this->assertSame('www.example.com', TagBuilder::normalizeDomain('www.example.com.'));
  }

  /**
   * Ordinary registrable hostnames are accepted.
   */
  public function testIsValidDomainAcceptsRegistrableHostnames() {
    $this->assertTrue(TagBuilder::isValidDomain('example.com'));
    $this->assertTrue(TagBuilder::isValidDomain('docs.example.co.uk'));
    $this->assertTrue(TagBuilder::isValidDomain('my-site.dev'));
  }

  /**
   * Anything that is not a registrable hostname is rejected.
   */
  public function testIsValidDomainRejectsEverythingElse() {
    // A bare label, so "localhost" can never be registered as a site.
    $this->assertFalse(TagBuilder::isValidDomain('localhost'));
    $this->assertFalse(TagBuilder::isValidDomain('-bad.com'));
    $this->assertFalse(TagBuilder::isValidDomain('example.123'));
    $this->assertFalse(TagBuilder::isValidDomain(''));
    // Anything that could break out of an HTML attribute.
    $this->assertFalse(TagBuilder::isValidDomain('example.com" onload="x'));
    $this->assertFalse(TagBuilder::isValidDomain('example.com x'));
    $this->assertFalse(TagBuilder::isValidDomain('example.com>'));
  }

  /**
   * A configured domain produces one tag carrying it.
   */
  public function testBuildsOneTagWithTheDomain() {
    $tags = TagBuilder::buildHeadTags('example.com');
    $this->assertCount(1, $tags);
    [$element, $key] = $tags[0];
    $this->assertSame('pulse_analytics_tracker', $key);
    $this->assertSame('html_tag', $element['#type']);
    $this->assertSame('script', $element['#tag']);
    // '' and not NULL: it is what makes this render as <script></script>
    // rather than a self-closing tag, which would swallow the rest of the head.
    $this->assertSame('', $element['#value']);
    $this->assertSame(
      ['defer' => TRUE, 'data-domain' => 'example.com', 'src' => TagBuilder::SCRIPT_URL],
      $element['#attributes']
    );
  }

  /**
   * Defer is passed as a boolean, so Drupal emits a bare attribute.
   */
  public function testDeferIsBooleanSoDrupalEmitsBareAttribute() {
    $tags = TagBuilder::buildHeadTags('example.com');
    $this->assertTrue($tags[0][0]['#attributes']['defer']);
    $this->assertNotSame('defer', $tags[0][0]['#attributes']['defer']);
  }

  /**
   * A domain is normalised before it reaches the attribute.
   */
  public function testNormalisesTheDomainOnTheWayIn() {
    $tags = TagBuilder::buildHeadTags('HTTPS://Example.COM/some/path');
    $this->assertSame('example.com', $tags[0][0]['#attributes']['data-domain']);
  }

  /**
   * No domain means no data-domain attribute, not an empty one.
   */
  public function testOmitsDataDomainWhenThereIsNoneToUse() {
    foreach (['', '   '] as $empty) {
      $tags = TagBuilder::buildHeadTags($empty);
      $this->assertArrayNotHasKey('data-domain', $tags[0][0]['#attributes']);
    }
  }

  /**
   * A domain that survived the form must never reach the attribute.
   *
   * An imported config or a hand-edited YAML can carry one. Dropping it leaves
   * a working install that auto-detects, which is strictly better than
   * injecting rubbish.
   */
  public function testDropsInvalidDomainRatherThanInjectingIt() {
    $tags = TagBuilder::buildHeadTags('bad" onload="alert(1)');
    $this->assertArrayNotHasKey('data-domain', $tags[0][0]['#attributes']);
    $this->assertSame(TagBuilder::SCRIPT_URL, $tags[0][0]['#attributes']['src']);
  }

  /**
   * A custom API origin rides on the tag, without trailing slashes.
   */
  public function testCarriesCustomApiOriginWithTrailingSlashesStripped() {
    $tags = TagBuilder::buildHeadTags('example.com', FALSE, 'https://proxy.example.com//');
    $this->assertSame('https://proxy.example.com', $tags[0][0]['#attributes']['data-api']);
  }

  /**
   * No custom API origin means no data-api attribute.
   */
  public function testOmitsDataApiByDefault() {
    $tags = TagBuilder::buildHeadTags('example.com');
    $this->assertArrayNotHasKey('data-api', $tags[0][0]['#attributes']);
  }

  /**
   * The companion is a separate tag and carries no domain.
   */
  public function testTheCompanionIsSeparateTagAndCarriesNoDomain() {
    $tags = TagBuilder::buildHeadTags('example.com', TRUE);
    $this->assertCount(2, $tags);
    [$element, $key] = $tags[1];
    $this->assertSame('pulse_analytics_companion', $key);
    $this->assertSame(
      ['defer' => TRUE, 'src' => TagBuilder::COMPANION_URL],
      $element['#attributes']
    );
  }

  /**
   * Every html_head key must be unique.
   *
   * Drupal uses the key to deduplicate, so two tags sharing one means that one
   * of them silently never renders.
   */
  public function testTheTwoTagsHaveDistinctKeys() {
    $tags = TagBuilder::buildHeadTags('example.com', TRUE);
    $this->assertNotSame($tags[0][1], $tags[1][1]);
  }

  /**
   * The tag points at js.ciphera.net and nowhere else.
   */
  public function testPointsAtJsCipheraNetAndNowhereElse() {
    $this->assertSame('https://js.ciphera.net/script.js', TagBuilder::SCRIPT_URL);
    $this->assertSame('https://js.ciphera.net/script.interactions.js', TagBuilder::COMPANION_URL);
  }

}
