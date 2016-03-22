<?php

/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerApiTestCase.
 */

namespace Drupal\scheduler\Tests;

use Drupal\node\Entity\NodeType;

/**
 * Tests the API of the Scheduler module.
 *
 * @group scheduler
 */
class SchedulerApiTestCase extends SchedulerTestBase {

  /**
   * The additional modules to be loaded for this test.
   */
  public static $modules = ['scheduler_api_test', 'menu_ui', 'path'];
  // @todo 'menu_ui' is in the exported node.type definition, and 'path' is in
  // the entity_form_display. Could these be removed from the config files and
  // then not needed here?

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Load the custom node type and check it .
    $this->custom_type = 'scheduler_api_test';
    $this->nodetype = NodeType::load($this->custom_type);
    if ($this->nodetype) {
      $this->pass('Custom node type ' . $this->custom_type . ' "' . $this->nodetype->get('name') . '"  created during install');
      // Do not need to enable this node type for scheduler as that is already
      // pre-configured in node.type.scheduler_api_test.yml
    }
    else {
      $this->fail('*** Custom node type ' . $this->custom_type . ' does not exist. Testing abandoned ***');
      return;
    }

    // Create a web user for this content type.
    $this->webUser = $this->drupalCreateUser([
      'create ' . $this->custom_type . ' content',
      'edit any ' . $this->custom_type . ' content',
      'schedule publishing of nodes',
    ]);

    // Create node_storage property.
    $this->node_storage = $this->container->get('entity.manager')->getStorage('node');

  }

  /**
   * Tests hook_scheduler_allow().
   *
   * This hook can allow or deny the (un)publication of individual nodes. This
   * test uses a content type which has checkboxes 'Approved for publication'
   * and 'Approved for unpublication'. The node may only be published or 
   * unpublished if the appropriate checkbox is ticked.
   *
   * @todo Create and update the nodes through the interface so we can check if
   *   the correct messages are displayed.
   */
  public function testAllowedPublishingAndUnpublishing() {
    if (empty($this->nodetype)) {
      $this->fail('*** Custom node type ' . $this->custom_type . ' does not exist. Testing abandoned ***');
      return;
    }

    // Check that the approved fields are shown on the node/add form.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/add/' . $this->custom_type);
    $this->assertFieldById('edit-field-approved-publishing-value', '', 'The "Approved for publishing" field is shown on the node form');
    $this->assertFieldById('edit-field-approved-unpublishing-value', '', 'The "Approved for unpublishing" field is shown on the node form');

    // Create a node that is scheduled but not approved for publication. Then
    // simulate a cron run, and check that the node is still not published.
    $node = $this->createUnapprovedNode('publish_on');
    scheduler_cron();
    $this->node_storage->resetCache(array($node->id()));
    $node = $this->node_storage->load($node->id());
    $this->assertFalse($node->isPublished(), 'An unapproved node is not published during cron processing.');

    // Approve the node for publication, simulate a cron run and check that the
    // node is now published.
    $this->approveNode($node->id(), 'field_approved_publishing');
    scheduler_cron();
    $this->node_storage->resetCache(array($node->id()));
    $node = $this->node_storage->load($node->id());
    $this->assertTrue($node->isPublished(), 'An approved node is published during cron processing.');

    // Turn on immediate publication of nodes with publication dates in the past
    // and repeat the tests. It is not needed to simulate cron runs here.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'publish')->save();
    $node = $this->createUnapprovedNode('publish_on');
    $this->assertFalse($node->isPublished(), 'An unapproved node with a date in the past is not published immediately after saving.');

    // Check that the node can be approved and published programatically.
    $this->approveNode($node->id(), 'field_approved_publishing');
    $this->node_storage->resetCache(array($node->id()));
    $node = $this->node_storage->load($node->id());
    $this->assertTrue($node->isPublished(), 'An approved node with a date in the past is published immediately via $node->set()->save().');

    // Check that a node can be approved and published via edit form.
    $node = $this->createUnapprovedNode('publish_on');
    $this->drupalPostForm('node/' . $node->id() . '/edit', ['field_approved_publishing[value]' => '1'], t('Save'));
    $this->node_storage->resetCache(array($node->id()));
    $node = $this->node_storage->load($node->id());
    $this->assertTrue($node->isPublished(), 'An approved node with a date in the past is published immediately after saving via edit form.');

    // Test approval for unpublishing. This is simpler than the test sequence
    // for publishing, because the 'immediate' option is not applicable.

    // Create a node that is scheduled but not approved for unpublication. Then
    // simulate a cron run, and check that the node is still published.
    $node = $this->createUnapprovedNode('unpublish_on');
    scheduler_cron();
    $this->node_storage->resetCache(array($node->id()));
    $node = $this->node_storage->load($node->id());
    $this->assertTrue($node->isPublished(), 'An unapproved node is not unpublished during cron processing.');

    // Approve the node for unpublishing, simulate a cron run and check that
    // the node is now unpublished.
    $this->approveNode($node->id(), 'field_approved_unpublishing');
    scheduler_cron();
    $this->node_storage->resetCache(array($node->id()));
    $node = $this->node_storage->load($node->id());
    $this->assertFalse($node->isPublished(), 'An approved node is unpublished during cron processing.');
  }

  /**
   * Creates a new node that is not approved.
   *
   * The node has a publish/unpublish date in the past to make sure it will be
   * included in the next cron run.
   *
   * @param string $date_field
   *   The Scheduler date field to set, either 'publish_on' or 'unpublish_on'.
   *
   * @return \Drupal\node\NodeInterface
   *   A node object.
   */
  protected function createUnapprovedNode($date_field) {
    $settings = array(
      'status' => ($date_field == 'unpublish_on'),
      $date_field => strtotime('-1 day'),
      'field_approved_publishing' => 0,
      'field_approved_unpublishing' => 0,
      'type' => $this->custom_type,
    );
    return $this->drupalCreateNode($settings);
  }

  /**
   * Approves a node for publication or unpublication.
   *
   * @param int $nid
   *   The id of the node to approve.
   *
   * @param string $field_name
   *   The name of the field to set, either 'field_approved_publishing' or
   *   'field_approved_for_unpublishing'.
   */
  protected function approveNode($nid, $field_name) {
    $this->node_storage->resetCache(array($nid));
    $node = $this->node_storage->load($nid);
    $node->set($field_name, TRUE)->save();
  }

}
