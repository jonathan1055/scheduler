<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests Scheduler with Workbench Moderation installed.
 *
 * @group scheduler
 */
class SchedulerWorkbenchModerationTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   */
  protected static $modules = ['workbench_moderation'];

  /**
   * Helper function to test publishing and unpublishing via cron.
   */
  public function schedulingWithWorkbenchModeration($type) {
    $this->drupalLogin($this->schedulerUser);

    // Create a node that is scheduled for publishing.
    $settings = [
      'publish_on' => strtotime('-1 day'),
      'status' => FALSE,
      'type' => $type,
      'title' => "{$type} for publishing",
    ];
    $node = $this->drupalCreateNode($settings);

    // Run cron and check that the node has been published successfully.
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertTrue($node->isPublished(), "The node should be published after cron");

    // Set a date for unpublishing the node.
    $node->set('unpublish_on', strtotime('-1 day'))->save();

    // Run cron and check that the node has been unpublished successfully.
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertFalse($node->isPublished(), "The node should be unpublished after cron");
  }

  /**
   * Test when only workbench_moderation is installed.
   */
  public function testWorkbenchModerationOnly() {
    // Test with a node type that is not included in a moderation workflow.
    $this->schedulingWithWorkbenchModeration($this->type);
  }

  /**
   * Test when workbench_moderation_actions is also installed.
   */
  public function testWorkbenchModerationWithWorkbenchModerationActions() {
    // Install workbench_moderation_actions and run the same test as above.
    \Drupal::service('module_installer')->install(['workbench_moderation_actions']);
    $this->schedulingWithWorkbenchModeration($this->type);
  }

}
