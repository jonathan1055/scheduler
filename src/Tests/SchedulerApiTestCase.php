<?php

/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerApiTestCase.
 */

namespace Drupal\scheduler\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the API of the Scheduler module.
 *
 * @group scheduler
 */
//class SchedulerApiTestCase extends WebTestBase {
class SchedulerApiTestCase extends SchedulerTestBase {

  /**
   * The modules to be loaded for these tests.
   */
  public static $modules = array('scheduler', 'scheduler_api_test');

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $config = $this->config('scheduler.settings');

    // TEMP. Add these lines to send devel dd() to the usual file.     
    debug(file_directory_temp(), 'existing file_directory_temp()');   
    \Drupal::configFactory()->getEditable('system.file')->set('path.temporary', '/private/tmp')->save(TRUE);   
    debug(file_directory_temp(), 'new file_directory_temp()');   

    // Add scheduler functionality to the custom node type.
    $this->nodetype = NodeType::load('scheduler_api_test');
    if ($this->nodetype) {
      $this->nodetype->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
        ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
        ->save();
      debug($this->nodetype->get('type'), 'nodetype->get(type)');
      debug($this->nodetype->get('name'), 'nodetype->get(name)');
    }
    else {
      $this->fail('Node type "scheduler_api_test" was not created');
    }
    $node_type_names = node_type_get_names();
    debug($node_type_names, '$node_type_names'); // for debug;
    
    // Create an administrator user.
    $this->adminUser = $this->drupalCreateUser(['create ' . $this->nodetype->get('type') . ' content', 'edit any ' . $this->nodetype->get('type') . ' content',]);
  }

  /**
   * Tests hook_scheduler_allow().
   *
   * This hook can allow or deny the (un)publication of individual nodes. This
   * test uses a content type which has a checkbox 'Approved for publication'.
   * The node may only be published if this checkbox is ticked.
   *
   * @todo Create and update the nodes through the interface so we can check if
   *   the correct messages are displayed.
   */
  public function testAllowedPublishing() {
    // Check that the approved field is shown on the node/add form.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/add/' . $this->nodetype->get('type'));
    $this->assertFieldByName('approved[0][value]', '', 'The Approved field is shown on the node form');
    
    // Create a node that is scheduled but not approved for publication. Then
    // simulate a cron run, and check that the node is not published.
    $node = $this->createUnapprovedNode();
    $this->drupalGet('node/' . $node->id() . '/edit'); // debug to display the node created.
    scheduler_cron();
    $this->assertFalse($node->isPublished(), 'An unapproved node is not published after scheduling.');

    // Approve the node for publication, simulate a cron run, check that the
    // node is now published.
    debug('now approving the node');
    $this->approveNode($node->id());
    scheduler_cron();
    $this->assertTrue($node->isPublished(), 'An approved node is published after scheduling.');

    // Turn on immediate publication of nodes with publication dates in the past
    // and repeat the tests. It is not needed to simulate cron runs now.
    debug('turn on immediate publishing');
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'publish')->save();
    $node = $this->createUnapprovedNode();
    $this->assertFalse($node->isPublished(), 'An unapproved node is not published immediately after saving.');
    $this->approveNode($node->id());
    $this->assertTrue($node->isPublished(), 'An approved node is published immediately after saving.');
  }

  /**
   * Creates a new node that is not approved by the CEO.
   *
   * The node has a publication date in the past to make sure it will be
   * included in the next cron run.
   *
   * @return \Drupal\node\NodeInterface
   *   A node object.
   */
  protected function createUnapprovedNode() {
    $settings = array(
      'status' => 0,
      'publish_on' => strtotime('-1 day'),
      'type' => $this->nodetype->get('type'),
    );
    return $this->drupalCreateNode($settings);
  }

  /**
   * Approves a node for publication.
   *
   * @param int $nid
   *   The nid of the node to approve.
   */
  protected function approveNode($nid) {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $node->set('field_scheduler_api_test_approved' , TRUE);
    $node->save();
  }

}
