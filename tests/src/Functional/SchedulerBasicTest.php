<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the modules primary function - publishing and unpublishing content.
 *
 * @group scheduler
 */
class SchedulerBasicTest extends SchedulerBrowserTestBase {

  /**
   * Tests basic scheduling of content.
   */
  public function testPublishingAndUnpublishing() {
    // Login to admin user. This is required here before creating the publish_on
    // date and time values so that date.formatter can utilise the current users
    // timezone. The constraints receive values which have been converted using
    // the users timezone so they need to be consistent.
    $this->drupalLogin($this->adminUser);

    // Create node values. Set time to one hour in the future.
    $edit = [
      'title[0][value]' => 'Publishing ' . $this->randomMachineName(10),
      'publish_on[0][value][date]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'Y-m-d'),
      'publish_on[0][value][time]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'H:i:s'),
    ];
    $this->helpTestScheduler($edit);

    // Remove publish_on and set unpublish_on, then run basic tests again.
    $edit['unpublish_on[0][value][date]'] = $edit['publish_on[0][value][date]'];
    $edit['unpublish_on[0][value][time]'] = $edit['publish_on[0][value][time]'];
    unset($edit['publish_on[0][value][date]']);
    unset($edit['publish_on[0][value][time]']);
    // Need a new title for the new node, as we identify the node by title.
    $edit['title[0][value]'] = 'Unpublishing ' . $this->randomMachineName(10);
    $this->helpTestScheduler($edit);
  }

  /**
   * Helper function for testScheduler(). Schedules content and asserts status.
   */
  protected function helpTestScheduler($edit) {
    // Add a page, using randomMachineName for the body text, not randomString,
    // because assertText works better without difficult non-alpha characters.
    $body = $this->randomMachineName(30);

    $edit['body[0][value]'] = $body;
    $this->drupalLogin($this->schedulerUser);
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    // Verify that the node was created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, sprintf('Node for %s was created sucessfully.', $edit['title[0][value]']));
    if (empty($node)) {
      $this->assert(FALSE, 'Test halted because node was not created.');
      return;
    }

    // Assert that the node is correctly published or unpublished.
    if (isset($edit['publish_on[0][value][date]'])) {
      $key = 'publish_on';
      $this->assertFalse($node->isPublished(), 'Node is unpublished before Cron');
    }
    else {
      $key = 'unpublish_on';
      $this->assertTrue($node->isPublished(), 'Node is published before Cron');
    }

    // Modify the scheduler field data to a time in the past, then run cron.
    // @TODO change this to node_save()
    $this->database->update('node_field_data')->fields([$key => time() - 1])->condition('nid', $node->id())->execute();
    $this->database->update('node_field_revision')->fields([$key => time() - 1])->condition('nid', $node->id())->execute();
    $this->nodeStorage->resetCache([$node->id()]);

    $this->cronRun();

    // Assert that the node is correctly published or unpublished.
    $node = $this->nodeStorage->load($node->id());
    if (isset($edit['publish_on[0][value][date]'])) {
      $this->assertTrue($node->isPublished(), 'Node is published after Cron');
    }
    else {
      $this->assertFalse($node->isPublished(), 'Node is unpublished after Cron');
    }
  }

}
