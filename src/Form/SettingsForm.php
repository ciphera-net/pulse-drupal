<?php

namespace Drupal\pulse_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pulse_analytics\TagBuilder;

/**
 * Configures the Pulse Analytics tracking tag.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pulse_analytics_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pulse_analytics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pulse_analytics.settings');

    $form['intro'] = [
      '#markup' => $this->t('Add <a href="@pulse" target="_blank" rel="noopener">Pulse Analytics</a> to this site. No cookies, no personal data, and the script it adds to your pages is under 3&nbsp;KB. You need a Pulse account with a site registered for the same domain.', [
        '@pulse' => 'https://pulse.ciphera.net',
      ]),
    ];

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $config->get('domain'),
      '#description' => $this->t('The domain this site is registered under in Pulse, for example <code>example.com</code>. Leave empty to let the tracker use the browser&rsquo;s own hostname, which is correct for a site served on one domain.'),
      '#maxlength' => 253,
    ];

    $form['companion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Record clicks, copies and form submits'),
      '#default_value' => $config->get('companion'),
      '#description' => $this->t('Loads a second, separate script. It is a second request on purpose: the core script&rsquo;s size is a published claim and nothing is folded into it to make a feature look free.'),
    ];

    $form['track_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Also track administration pages'),
      '#default_value' => $config->get('track_admin'),
      '#description' => $this->t('Off by default. Administration pages are not the site&rsquo;s audience, and counting them inflates every number on the dashboard.'),
    ];

    $form['api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom API origin'),
      '#default_value' => $config->get('api'),
      '#description' => $this->t('Optional. Route events through your own proxy origin, for example <code>https://example.com</code>. A bare origin, not a full URL &mdash; the tracker appends its own path. Leave empty unless you have set a proxy up.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * A mistyped domain is the one failure worth being noisy about: the tag still
   * loads, the site still works, and the dashboard stays empty forever with
   * nothing to see. So it is rejected here rather than quietly dropped.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $domain = trim((string) $form_state->getValue('domain'));
    if ($domain !== '') {
      $normalized = TagBuilder::normalizeDomain($domain);
      if (!TagBuilder::isValidDomain($normalized)) {
        $form_state->setErrorByName('domain', $this->t('%domain is not a valid domain. Enter the hostname this site is registered under in Pulse, for example example.com.', [
          '%domain' => $domain,
        ]));
      }
    }

    $api = trim((string) $form_state->getValue('api'));
    if ($api !== '' && !preg_match('#^https?://#', $api)) {
      $form_state->setErrorByName('api', $this->t('The API origin must start with http:// or https://.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $domain = trim((string) $form_state->getValue('domain'));

    $this->config('pulse_analytics.settings')
      // Stored normalised, so what is saved is what is injected. Storing the
      // raw input would mean the form showed one thing and the page carried
      // another.
      ->set('domain', $domain === '' ? '' : TagBuilder::normalizeDomain($domain))
      ->set('companion', (bool) $form_state->getValue('companion'))
      ->set('track_admin', (bool) $form_state->getValue('track_admin'))
      ->set('api', rtrim(trim((string) $form_state->getValue('api')), '/'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
