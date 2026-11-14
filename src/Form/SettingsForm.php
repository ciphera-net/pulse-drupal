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
      '#markup' => $this->t('This module adds <a href="@pulse" target="_blank" rel="noopener">Pulse Analytics</a> to your site with a script of about 3&nbsp;KB. Pulse sets no cookies and never stores IP addresses. Scroll depth, outbound links, file downloads, and clicks, copies and form submits are each switched on or off in your site\'s settings in Pulse (Settings → General). The tag stays the same, so there is nothing to change here. You need a Pulse account with a site registered for the same domain.', [
        '@pulse' => 'https://pulse.ciphera.net',
      ]),
    ];

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $config->get('domain'),
      '#description' => $this->t("Enter the domain this site is registered under in Pulse, for example <code>example.com</code>. If you leave it empty, the tracker uses the browser's own hostname, which is right for a site served on one domain."),
      '#maxlength' => 253,
    ];

    $form['track_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Also track administration pages'),
      '#default_value' => $config->get('track_admin'),
      '#description' => $this->t("It is off by default because administration pages are not the site's audience, and counting them inflates every number on the dashboard."),
    ];

    $form['api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom API origin'),
      '#default_value' => $config->get('api'),
      '#description' => $this->t('If you route events through your own proxy, enter its origin here, for example <code>https://example.com</code>. Enter the origin only, without a path, because the tracker adds its own. Leave this empty if you have not set up a proxy.'),
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
      ->set('track_admin', (bool) $form_state->getValue('track_admin'))
      ->set('api', rtrim(trim((string) $form_state->getValue('api')), '/'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
