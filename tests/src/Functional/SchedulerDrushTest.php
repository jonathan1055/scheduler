<?php

namespace Drupal\Tests\scheduler\Functional;

use Drush\TestTraits\DrushTestTrait;

/**
 * Tests the Drush commands provided by Scheduler.
 *
 * @group scheduler
 */
class SchedulerDrushTest extends SchedulerBrowserTestBase {


  public function testCronCommand() {
    // Run the plain command using the full scheduler:cron command name, and
    // check that all of the output messages are shown.
    DrushTestTrait::drush('scheduler:cron');
    $messages = DrushTestTrait::getErrorOutput();
    // $this->assertContains('Lightweight cron run activated by drush command', $messages, 'Starting dblog message not found', TRUE);
  }
}
