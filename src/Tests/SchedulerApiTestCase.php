<?php

/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerApiTestCase.
 */

namespace Drupal\scheduler\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the API of the Scheduler module.
 *
 * @group Scheduler
 */
class SchedulerApiTestCase extends WebTestBase {

  /**
   * The modules to be loaded for these tests.
   */
  public static $modules = array('node', 'scheduler', 'scheduler_test');

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

    // Add scheduler functionality to the 'scheduler_test' node type.
    $config
      ->set('scheduler_publish_enable_scheduler_test', 1)
      ->set('scheduler_unpublish_enable_scheduler_test', 1)
      ->save();
    debug($config, '$config');
  }

  /**
   * Tests hook_scheduler_allow().
   *
   * This hook can allow or deny the (un)publication of individual nodes. This
   * test uses a content type 'scheduler_test' which has a checkbox 'Approved
   * for publication by the CEO'. Only if this is checked the node may be
   * published.
   *
   * @todo Create and update the nodes through the interface so we can check if
   *   the correct messages are displayed.
   */
  public function testAllowedPublishing() {
    $config = \Drupal::config('scheduler.settings');
//    $config = $this->config('scheduler.settings');
    // Create a node that is not approved for publication. Then simulate a cron
    // run, and check that the node is not published.
    $node = $this->createUnapprovedNode();
    scheduler_cron();
    $this->assertNodeNotPublished($node->id(), 'An unapproved node is not published after scheduling.');

    // Approve the node for publication, simulate a cron run, check that the
    // node is now published.
    $this->approveNode($node->id());
    scheduler_cron();
    $this->assertNodePublished($node->id(), 'An approved node is published after scheduling.');

    // Turn on immediate publication of nodes with publication dates in the past
    // and repeat the tests. It is not needed to simulate cron runs now.
    $config->set('scheduler_publish_past_date_scheduler_test', 'publish');
    $node = $this->createUnapprovedNode();
    $this->assertNodeNotPublished($node->id(), 'An unapproved node is not published immediately after saving.');
    $this->approveNode($node->id());
    $this->assertNodePublished($node->id(), 'An approved node is published immediately after saving.');
  }

  /**
   * Creates a new node that is not approved by the CEO.
   *
   * The node has a publication date in the past to make sure it will be
   * included in the next scheduling run.
   *
   * @return \Drupal\node\NodeInterface
   *   A node object.
   */
  protected function createUnapprovedNode() {
    $settings = array(
      'status' => 0,
      'publish_on' => strtotime('-1 day'),
      'type' => 'scheduler_test',
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
    $node->set('field_scheduler_test_approved' , TRUE);
    $node->save();
  }

  /**
   * Check to see if a node is not published.
   *
   * @param int $nid
   *   The nid of the node to check.
   * @param string $message
   *   The message to display along with the assertion.
   * @param string $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  public function assertNodeNotPublished($nid, $message = NULL, $group = 'Other') {
    $message = $message ? $message : SafeMarkup::format('Node %nid is not published', array('%nid' => $nid));
    return $this->assertFalse($this->getPublicationStatus($nid), $message, $group);
  }

  /**
   * Check to see if a node is published.
   *
   * @param int $nid
   *   The nid of the node to check.
   * @param string $message
   *   The message to display along with the assertion.
   * @param string $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  public function assertNodePublished($nid, $message = NULL, $group = 'Other') {
    $message = $message ? $message : SafeMarkup::format('Node %nid is published', array('%nid' => $nid));
    return $this->assertTrue($this->getPublicationStatus($nid), $message, $group);
  }

  /**
   * Returns the publication status of a node.
   *
   * @param int $nid
   *   The nid of the node for which the publication status is desired.
   *
   * @return bool
   *   TRUE if the node is published, FALSE otherwise.
   */
  protected function getPublicationStatus($nid) {
    return db_select('node', 'n')
      ->fields('n', array('status'))
      ->condition('n.nid', $nid)
      ->execute()
      ->fetchColumn();
  }

}
