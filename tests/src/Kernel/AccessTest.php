<?php

namespace Drupal\Tests\pulse_analytics\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Who may change the tag, and how sites that had the old permission keep it.
 *
 * The settings decide what a script tag on every page carries, so they sit
 * behind their own restricted permission rather than the generic
 * "administer site configuration". Test metadata is doc-comment only, for the
 * reason TagRenderTest gives.
 *
 * @group pulse_analytics
 */
class AccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'pulse_analytics'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['pulse_analytics']);
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * The settings route asks for the module's own permission.
   */
  public function testSettingsRouteNeedsTheModulePermission() {
    $route = $this->container->get('router.route_provider')->getRouteByName('pulse_analytics.settings');
    $this->assertSame('administer pulse analytics', $route->getRequirement('_permission'));
  }

  /**
   * The permission is marked as one to give only to trusted roles.
   */
  public function testPermissionIsRestricted() {
    $permissions = $this->container->get('user.permissions')->getPermissions();
    $this->assertArrayHasKey('administer pulse analytics', $permissions);
    $this->assertTrue($permissions['administer pulse analytics']['restrict access']);
    $this->assertSame('pulse_analytics', $permissions['administer pulse analytics']['provider']);
  }

  /**
   * The update grants it to exactly the roles that held the old permission.
   */
  public function testUpdateGrantsThePermissionToSiteConfigurationRoles() {
    Role::create(['id' => 'site_builder', 'label' => 'Site builder'])
      ->grantPermission('administer site configuration')
      ->save();
    Role::create(['id' => 'editor', 'label' => 'Editor'])->save();

    $this->container->get('module_handler')->loadInclude('pulse_analytics', 'install');
    $message = (string) pulse_analytics_update_10101();

    $this->assertTrue(Role::load('site_builder')->hasPermission('administer pulse analytics'));
    $this->assertFalse(Role::load('editor')->hasPermission('administer pulse analytics'));
    $this->assertStringContainsString('Site builder', $message);

    // A second run changes nothing and says so.
    $this->assertStringContainsString('No role needed', (string) pulse_analytics_update_10101());
  }

  /**
   * The help page explains the module and links its settings form.
   */
  public function testHelpPageLinksTheSettingsForm() {
    $output = (string) pulse_analytics_help('help.page.pulse_analytics', $this->container->get('current_route_match'));
    $this->assertStringContainsString('/admin/config/services/pulse-analytics', $output);
    $this->assertStringContainsString('https://pulse.ciphera.net', $output);
    $this->assertNull(pulse_analytics_help('system.admin', $this->container->get('current_route_match')));
  }

}
