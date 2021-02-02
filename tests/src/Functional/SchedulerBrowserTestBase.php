<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\scheduler\Traits\SchedulerSetupTrait;
use Drupal\Tests\scheduler\Traits\SchedulerMediaSetupTrait;

/**
 * Base class to provide common browser test setup.
 */
abstract class SchedulerBrowserTestBase extends BrowserTestBase {

  use SchedulerSetupTrait;
  use SchedulerMediaSetupTrait;

  /**
   * The standard modules to load for all browser tests.
   *
   * Additional modules can be specified in the tests that need them.
   *
   * @var array
   */
  protected static $modules = ['scheduler', 'media', 'dblog'];

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

    $this->schedulerSetUp();
    $this->schedulerMediaSetUp();

  }

}
