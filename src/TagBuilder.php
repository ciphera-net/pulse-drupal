<?php

namespace Drupal\pulse_analytics;

/**
 * Builds the Pulse Analytics tag.
 *
 * Pure logic: no Drupal services, no container, no config — everything comes in
 * as arguments and a render array comes out. That is deliberate, so the part
 * that decides what the tag says is unit-testable without a Drupal bootstrap,
 * and so it can be read side by side with the same decisions in the Astro,
 * Framer and Docusaurus integrations.
 *
 * The URLs, the domain grammar and the rule that the companion script is a
 * SECOND request are copied from those integrations rather than re-derived. Two
 * Pulse install surfaces disagreeing about what the tag looks like is a drift
 * bug waiting to happen.
 */
class TagBuilder {

  /**
   * The Pulse tracker.
   */
  const SCRIPT_URL = 'https://js.ciphera.net/script.js';

  /**
   * The companion script: clicks, copies and form submits.
   */
  const COMPANION_URL = 'https://js.ciphera.net/script.interactions.js';

  /**
   * A registrable hostname.
   *
   * Labels of letters, digits and hyphens, at least one dot, a letter-only TLD.
   * Deliberately strict — the value lands inside an HTML attribute, and this
   * shape cannot carry a quote, a space or a bracket. Drupal escapes attribute
   * values itself, so this is the second of two guards rather than the only
   * one; it is kept anyway because a domain that cannot be a hostname is a
   * typo, and a typo is an install that reports nothing and looks fine.
   */
  const DOMAIN_PATTERN = '/^(?=.{1,253}$)(?!-)[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*\.[a-z]{2,63}$/';

  /**
   * Lower-cases a domain and strips a scheme, path, port and trailing dot.
   *
   * Keeps "www." — the Pulse site may be registered either way and the site
   * owner can edit it.
   *
   * @param string $input
   *   Whatever the site owner typed.
   *
   * @return string
   *   The normalised domain.
   */
  public static function normalizeDomain($input) {
    $value = mb_strtolower(trim($input));
    $value = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $value);
    $value = preg_replace('#[/?\#].*$#', '', $value);
    $value = preg_replace('/:.*$/', '', $value);
    return rtrim($value, '.');
  }

  /**
   * Whether a normalised domain is a registrable hostname.
   *
   * @param string $domain
   *   A domain, already through normalizeDomain().
   *
   * @return bool
   *   TRUE when it is safe to put in the tag.
   */
  public static function isValidDomain($domain) {
    return (bool) preg_match(self::DOMAIN_PATTERN, $domain);
  }

  /**
   * Builds the head elements for the tag.
   *
   * @param string $domain
   *   The domain the site is registered under in Pulse, or '' to let the
   *   tracker auto-detect the browser's hostname. An invalid non-empty domain
   *   is dropped rather than injected, and the tag falls back to auto-detection
   *   — the same working install, one attribute short.
   * @param bool $companion
   *   Whether to also load the companion script.
   * @param string $api
   *   A custom API origin, or '' for the default.
   *
   * @return array[]
   *   A list of [render array, key] pairs, ready to append to
   *   $attachments['#attached']['html_head'].
   */
  public static function buildHeadTags($domain, $companion = FALSE, $api = '') {
    $attributes = ['defer' => TRUE];

    $domain = self::normalizeDomain((string) $domain);
    if ($domain !== '' && self::isValidDomain($domain)) {
      $attributes['data-domain'] = $domain;
    }

    $api = rtrim(trim((string) $api), '/');
    if ($api !== '') {
      $attributes['data-api'] = $api;
    }

    $attributes['src'] = self::SCRIPT_URL;

    // '#value' => '' is what makes this render as <script ...></script> rather
    // than a self-closing tag. A self-closed <script> swallows the rest of the
    // head in every browser.
    $tags = [
      [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => $attributes,
          '#value' => '',
        ],
        'pulse_analytics_tracker',
      ],
    ];

    if ($companion) {
      // No data-domain on the companion: the tracker resolves the domain once,
      // from the core script, and a second copy is a second thing to keep in
      // step.
      $tags[] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => [
            'defer' => TRUE,
            'src' => self::COMPANION_URL,
          ],
          '#value' => '',
        ],
        'pulse_analytics_companion',
      ];
    }

    return $tags;
  }

}
