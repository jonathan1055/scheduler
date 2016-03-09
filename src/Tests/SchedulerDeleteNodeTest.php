<?php

/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerDeleteNodeTest
 */

namespace Drupal\scheduler\Tests;

use Drupal\node\Entity\NodeType;

/**
 * Tests the components of the Scheduler interface which use the Date module.
 *
 * @group scheduler
 */
class SchedulerDeleteNodeTest extends SchedulerTestBase {

  /**
   * Tests the deletion of a scheduled node.
   *
   * This tests if it is possible to delete a node that does not have a
   * publication date set, when scheduled publishing is required.
   *
   * @see https://drupal.org/node/1614880
   */
  public function testScheduledNodeDelete() {
    // Log in.
    $this->drupalLogin($this->adminUser);

    // Create a published and an unpublished node, both without scheduling.
    $published_node = $this->drupalCreateNode(['type' => 'page', 'status' => 1]);
    $unpublished_node = $this->drupalCreateNode(['type' => 'page', 'status' => 0]);

    // Make scheduled publishing and unpublishing required.
    $node_type = NodeType::load('page');
    $node_type->setThirdPartySetting('scheduler', 'publish_required', TRUE);
    $node_type->setThirdPartySetting('scheduler', 'unpublish_required', TRUE);
    $node_type->save();

    // Check that deleting the nodes does not throw form validation errors.
    ### @TODO Delete was a button in 7.x but a separate link node/<nid>/delete in 8.x
    ### Is the previous validation (that we had to avoid on delete) still done now in D8, given that there is no form?
    ### Maybe this test is not actually checking anything useful? Can it be altered to do something testable?
    $this->drupalGet('node/' . $published_node->id() . '/delete');
    // Note that the text 'error message' is used in a header h2 html tag which
    // is normally made hidden from browsers but will be in the page source.
    // It is also good when testing for the absense of somthing to also test
    // for the presence of text, hence the second assertion for each check.
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete a published node with no scheduling information.');
    $this->assertRaw(t('Are you sure you want to delete the content'), 'The deletion warning message is shown immediately when trying to delete a published node with no scheduling information.');

    $this->drupalGet('node/' . $unpublished_node->id() . '/delete');
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete an unpublished node with no scheduling information.');
    $this->assertRaw(t('Are you sure you want to delete the content'), 'The deletion warning message is shown immediately when trying to delete an unpublished node with no scheduling information.');
  }
}
