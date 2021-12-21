<?php

namespace Drupal\Tests\scheduler\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the migration of Drupal 7 scheduler configuration.
 *
 * @group scheduler_kernel
 */
class MigrateSchedulerTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'scheduler',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      drupal_get_path('module', 'scheduler'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]));
    $this->installConfig(['scheduler']);
  }

  /**
   * Tests the migration of Scheduler global settings.
   */
  public function testGlobalSettingsMigration() {
    $config_before = $this->config('scheduler.settings');
    $this->assertFalse($config_before->get('allow_date_only'));
    $this->assertSame('00:00:00', $config_before->get('default_time'));

    $this->executeMigration('d7_scheduler_settings');

    $config_after = $this->config('scheduler.settings');
    $this->assertTrue($config_after->get('allow_date_only'));
    $this->assertSame('00:00:38', $config_after->get('default_time'));

  }

}
