<?php
/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerPermissionsTest.
 */

namespace Drupal\scheduler\Tests;

/**
 * Tests the permissions of the Scheduler module.
 *
 * @group scheduler
 */
class SchedulerPermissionsTest extends SchedulerTestBase {

  /**
   * Tests that users without permission do not see the scheduler date fields.
   */
  public function testUserPermissions() {
    // Create a user who can add the content type but who does not have the
    // permission to use the scheduler functionality.
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'create ' . $this->nodetype->get('type') . ' content',
      'edit own ' . $this->nodetype->get('type') . ' content',
      'delete own ' . $this->nodetype->get('type') . ' content',
      'view own unpublished content',
    ]);
    $this->drupalLogin($this->webUser);

    // Set publishing and unpublishing to required, to make it a stronger test.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', TRUE)
      ->save();

    // Check that neither of the fields are displayed when creating a node.
    $this->drupalGet('node/add/page');
    $this->assertNoFieldByName('publish_on[0][value][date]', '', 'The Publish-on field is not shown for users who do not have permission to schedule content');
    $this->assertNoFieldByName('unpublish_on[0][value][date]', '', 'The Unpublish-on field is not shown for users who do not have permission to schedule content');

    // Check that the new node can be created and saved.
    $title = $this->randomString(15);
    $this->drupalPostForm('node/add/page', ['title[0][value]' => $title], t('Save'));
    $this->assertRaw(t('@type %title has been created.', array('@type' => $this->nodetype->get('name'), '%title' => $title)), 'The node was created and saved when the user does not have scheduler permissions.');
  }
}
