<?php

namespace Drupal\Tests\scheduler\Functional;

use Drush\TestTraits\DrushTestTrait;

/**
 * Tests the Drush commands provided by Scheduler.
 *
 * @group scheduler
 */
class SchedulerDrushTest extends SchedulerBrowserTestBase {

  use DrushTestTrait;

  /**
   * Tests the Scheduler Drush messages.
   */
  public function testDrushCronMessages() {
    // Run the plain command using the full scheduler:cron command name, and
    // check that all of the output messages are shown.
    $this->drush('scheduler:cron');
    $messages = $this->getErrorOutput();
    $this->assertStringContainsString('Lightweight cron run activated by drush command', $messages, 'Starting dblog message not found', TRUE);
    $this->assertStringContainsString('Lightweight cron run completed', $messages, 'Ending dblog message not found', TRUE);
    $this->assertStringContainsString('Message: Scheduler lightweight cron completed', $messages, 'Drush message not found', TRUE);

    // Use the sch:cron alias and simulate the --nomsg parameter, then check
    // that the drush confirmation message is not shown.
    $this->drush('sch:cron', [], ['nomsg' => TRUE]);
    $messages = $this->getErrorOutput();
    $this->assertStringNotContainsString('Message: Scheduler lightweight cron completed', $messages, '--nomsg parameter did not work', TRUE);

    // Use the alternative alias sch-cron and add the --nolog parameter, then
    // check that the dblog messages are not shown.
    $this->drush('sch-cron', [], ['nolog' => TRUE]);
    $messages = $this->getErrorOutput();
    $this->assertStringNotContainsString('Lightweight cron run activated by drush command', $messages, '--nolog parameter did not work for starting message', TRUE);
    $this->assertStringNotContainsString('Lightweight cron run completed', $messages, '--nolog parameter did not work for ending message', TRUE);
  }

  /**
   * Tests scheduled publishing and unpublishing of entities via Drush.
   *
   * @dataProvider dataDrushCronPublishing()
   */
  public function testDrushCronPublishing($entityType, $bundle, $typeFieldName) {
    // Create an entity which is scheduled for publishing.
    $title1 = $this->randomMachineName(20) . ' for publishing';
    $entity = $this->createEntity($entityType, $bundle, [
      'title' => $title1,
      'publish_on' => strtotime('-3 hours'),
    ]);

    // Create an entity which is scheduled for unpublishing.
    $title2 = $this->randomMachineName(20) . ' for unpublishing';
    $entity = $this->createEntity($entityType, $bundle, [
      'title' => $title2,
      'unpublish_on' => strtotime('-2 hours'),
    ]);

    // Run Scheduler's drush cron command and check that the expected publishing
    // and unpublishing messages are found.
    $this->drush('scheduler:cron');
    $messages = $this->getErrorOutput();
    $type_label = $entity->$typeFieldName->entity->label();
    $this->assertStringContainsString(sprintf('%s: scheduled publishing of %s', $type_label, $title1), $messages, 'Scheduled publishing message not found', TRUE);
    $this->assertStringContainsString(sprintf('%s: scheduled unpublishing of %s', $type_label, $title2), $messages, 'Scheduled unpublishing message not found', TRUE);
  }

  /**
   * Provides data for testMetaInformation().
   *
   * @return array
   *   Each array item has the values: [entity type, bundle id, type fieldname].
   */
  public function dataDrushCronPublishing() {
    // The data provider does not have access to $this so we have to hard-code
    // the entity bundle id.
    $data = [
      0 => ['node', 'testpage', 'type'],
      1 => ['media', 'test-video', 'bundle'],
    ];

    // Use unset($data[n]) to remove a temporarily unwanted item, use
    // return [$data[n]] to selectively test just one item, or have the default
    // return $data to test everything.
    return $data;
  }

}
