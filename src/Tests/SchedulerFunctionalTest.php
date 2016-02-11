<?php
/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerFunctionalTest.
 */

namespace Drupal\scheduler\Tests;

/**
 * Tests the modules primary function - publishing and unpublishing content.
 *
 * @group scheduler
 */
class SchedulerFunctionalTest extends SchedulerTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

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
      'title[0][value]' => $this->randomMachineName(10),
      'publish_on[0][value][date]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'Y-m-d'),
      'publish_on[0][value][time]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'H:i:s'),
      'promote[value]' => 1,
    ];
    $this->helpTestScheduler($edit);

    // Remove publish_on and set unpublish_on, then run basic tests again.
    $edit['unpublish_on[0][value][date]'] = $edit['publish_on[0][value][date]'];
    $edit['unpublish_on[0][value][time]'] = $edit['publish_on[0][value][time]'];
    unset($edit['publish_on[0][value][date]']);
    unset($edit['publish_on[0][value][time]']);
    // Need a new title for the new node, as we identify the node by title.
    $edit['title[0][value]'] = $this->randomMachineName(10);
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
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));
    // Show the site front page for an anonymous visitor, then assert that the
    // node is correctly published or unpublished.
    $this->drupalLogout();
    $this->drupalGet('node');
    if (isset($edit['publish_on[0][value][date]'])) {
      $key = 'publish_on';
      $this->assertNoText($body, t('Node is unpublished before Cron'));
    }
    else {
      $key = 'unpublish_on';
      $this->assertText($body, t('Node is published before Cron'));
    }

    // Modify the scheduler field data to a time in the past, then run cron.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    if (empty($node)) {
      $date_time = $edit[$key . '[0][value][date]'] . ' ' . $edit[$key . '[0][value][time]'];
      $this->assert(FALSE, t('Node with %key = @date_time was not created.', ['%key' => $key, '@date_time' => $date_time]));
      return;
    }
    db_update('node_field_data')->fields(array($key => time() - 1))->condition('nid', $node->id())->execute();

    $this->cronRun();
    // Show the site front page for an anonymous visitor, then assert that the
    // node is correctly published or unpublished.
    $this->drupalGet('node');
    if (isset($edit['publish_on[0][value][date]'])) {
      $this->assertText($body, t('Node is published after Cron'));
    }
    else {
      $this->assertNoText($body, t('Node is unpublished after Cron'));
    }
  }
}
