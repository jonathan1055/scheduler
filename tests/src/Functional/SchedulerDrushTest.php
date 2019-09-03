<?php

namespace Drupal\Tests\scheduler\Functional;

// use Drush\TestTraits\DrushTestTrait;
// use drush\TestTraits\DrushTestTrait;
use \Drush\TestTraits\DrushTestTrait;

/**
 * Tests the Drush commands provided by Scheduler.
 *
 * @group scheduler
 */
class SchedulerDrushTest extends SchedulerBrowserTestBase {

  use DrushTestTrait;

  /**
   * Tests the Scheduler Cron command.
   */
  public function testCronCommand() {
    // Run the plain command using the full scheduler:cron command name, and
    // check that all of the output messages are shown.
    $this->drush('scheduler:cron');
    $messages = $this->getErrorOutput();
    $this->assertContains('Lightweight cron run activated by drush command', $messages, 'Starting dblog message not found', TRUE);
    $this->assertContains('Lightweight cron run completed', $messages, 'Ending dblog message not found', TRUE);
    $this->assertContains('Message: Scheduler lightweight cron completed', $messages, 'Drush message not found', TRUE);

    // Use the sch:cron alias and simulate the --nomsg parameter, then check
    // that the drush confirmation message is not shown.
    $this->drush('sch:cron', [], ['nomsg' => TRUE]);
    $messages = $this->getErrorOutput();
    $this->assertNotContains('Message: Scheduler lightweight cron completed', $messages, '--nomsg parameter did not work', TRUE);

    // Use the alternative alias sch-cron and add the --nolog parameter, then
    // check that the dblog messages are not shown.
    $this->drush('sch-cron', [], ['nolog' => TRUE]);
    $messages = $this->getErrorOutput();
    $this->assertNotContains('Lightweight cron run activated by drush command', $messages, '--nolog parameter did not work for starting message', TRUE);
    $this->assertNotContains('Lightweight cron run completed', $messages, '--nolog parameter did not work for ending message', TRUE);
  }

}
