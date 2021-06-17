<?php

namespace Drupal\Tests\scheduler\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\scheduler\Traits\SchedulerCommerceProductSetupTrait;
use Drupal\Tests\scheduler\Traits\SchedulerMediaSetupTrait;
use Drupal\Tests\scheduler\Traits\SchedulerSetupTrait;

/**
 * Base class for Scheduler javascript tests.
 *
 * @group scheduler
 */
abstract class SchedulerJavascriptTestBase extends WebDriverTestBase {

  use SchedulerSetupTrait;
  use SchedulerMediaSetupTrait;
  use SchedulerCommerceProductSetupTrait;

  /**
   * The standard modules to load for all javascript tests.
   *
   * Additional modules can be specified in the tests that need them.
   *
   * @var array
   */
  protected static $modules = [
    'scheduler',
    'media',
    'commerce_product',
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Call the common set-up functions defined in the traits.
    $this->schedulerSetUp();
    $this->schedulerMediaSetUp();
    $this->SchedulerCommerceProductSetUp();
  }

  /**
   * Flush cache.
   */
  protected function flushCache() {
    $module_handler = \Drupal::moduleHandler();
    $module_handler->invokeAll('cache_flush');
  }

}
