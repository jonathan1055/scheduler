<?php
/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerNonEnabledTypeTest.
 */

namespace Drupal\scheduler\Tests;

/**
 * Tests a content type which is not enabled for scheduled publishing and
 * unpublishing.
 *
 * @group scheduler
 */
class SchedulerNonEnabledTypeTest extends SchedulerTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a 'Not for scheduler' content type.
    $this->content_name = 'not_for_scheduler';
    $this->nodetype = $this->drupalCreateContentType(['type' => $this->content_name, 'name' => t('Not for Scheduler')]);

    // Create an administrator user.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer scheduler',
      'create ' . $this->content_name . ' content',
      'edit own ' . $this->content_name . ' content',
      'delete own ' . $this->content_name . ' content',
      'view own unpublished content',
      'administer nodes',
      'schedule publishing of nodes',
    ]);
  }

  /**
   * Helper function for testNonEnabledNodeType().
   *
   * This function is called four times. Checks that the date entry are correct
   * shown or not shown in /node/add. Check that a node is not processed by cron
   * if it is not enabled for that action.
   */
  protected function checkNonEnabledTypes($publishing_enabled, $unpublishing_enabled, $text) {

    // Create title to show what combinations are being tested.
    $info = 'Publishing ' . ($publishing_enabled ? 'enabled' : 'not enabled')
      . ', Unpublishing ' . ($unpublishing_enabled ? 'enabled' : 'not enabled')
      . ', ' . $text;
    $title = $info . ', test fields (a)';

    // Check that the field(s) are displayed only for the correct settings.
    $this->drupalGet('node/add/' . $this->content_name);
    if ($publishing_enabled) {
      $this->assertFieldByName('publish_on[0][value][date]', '', 'The Publish-on field is shown for: ' . $title);
    }
    else {
      $this->assertNoFieldByName('publish_on[0][value][date]', '', 'The Publish-on field is not shown for: ' . $title);
    }

    if ($unpublishing_enabled) {
      $this->assertFieldByName('unpublish_on[0][value][date]', '', 'The Unpublish-on field is shown for: ' . $title);
    }
    else {
      $this->assertNoFieldByName('unpublish_on[0][value][date]', '', 'The Unpublish-on field is not shown for: ' . $title);
    }

    // Create an unpublished node then set a publishing date, which mimics what
    // could be done by a third-party module, or a by-product of the node type
    // being enabled for publishing then being disabled before it got published.
    $title = $info . ', test publishing (b)';
    $edit = [
      'title' => $title,
      'promote' => 1,
      'status' => 0,
      'type' => $this->content_name,
      'body' => $this->randomMachineName(30),
      'publish_on' => REQUEST_TIME -2,
    ];
    $node = $this->drupalCreateNode($edit);

    // Run cron.
    $this->cronRun();
    // Reload the node.
    $this->node_storage->resetCache([$node->id()]);
    $node = $this->node_storage->load($node->id());
    // Check if the node has been published or remains unpublished.
    if ($publishing_enabled) {
      $this->assertTrue($node->isPublished(), 'The unpublished node has been published for: ' . $title);
    }
    else {
      $this->assertFalse($node->isPublished(), 'The unpublished node remains unpublished for: ' . $title);
    }

    // Create a published node and set an unpublishing date.
    $title = $info . ', test unpublishing (c)';
    $edit = [
      'title' => $title,
      'promote' => 1,
      'status' => 1,
      'type' => $this->content_name,
      'body' => $this->randomMachineName(30),
      'unpublish_on' => REQUEST_TIME -1,
    ];
    $node = $this->drupalCreateNode($edit);

    // Run cron.
    $this->cronRun();
    // Reload the node.
    $this->node_storage->resetCache([$node->id()]);
    $node = $this->node_storage->load($node->id());
    // Check if the node has been unpublished or remains published.
    if ($unpublishing_enabled) {
      $this->assertFalse($node->isPublished(), 'The published node has been unpublished for: ' . $title);
    }
    else {
      $this->assertTrue($node->isPublished(), 'The published node remains published for: ' . $title);
    }
  }

  /**
   * Tests that a non-enabled node type cannot be scheduled.
   */
  public function testNonEnabledNodeType() {
    // Log in.
    $this->drupalLogin($this->adminUser);

    // Get node storage for use in checkNonEnabledTypes()
    $this->node_storage = $this->container->get('entity.manager')->getStorage('node');

    // The case when both options are enabled is covered in the main tests. Here
    // we need to check each of the other combinations, to ensure that the
    // settings work independently.

    // 1. By default check that the scheduler date fields are not displayed.
    $this->checkNonEnabledTypes(FALSE, FALSE, 'by default (1)');

    // 2. Explicitly disable this content type for both settings and test again.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_enable', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', FALSE)
      ->save();
    $this->checkNonEnabledTypes(FALSE, FALSE, 'after disabling both settings (2)');

    // 3. Turn on scheduled publishing only and test again.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->save();
    $this->checkNonEnabledTypes(TRUE, FALSE, 'after enabling publishing only (3)');

    // 4. Turn on scheduled unpublishing only and test again.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_enable', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();
    $this->checkNonEnabledTypes(FALSE, TRUE, 'after enabling unpublishing only (4)');
  }

}
