<?php

namespace Drupal\scheduler\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class to provide common test setup.
 */
abstract class SchedulerTestBase extends WebTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * The standard modules to be loaded for all tests.
   */
  public static $modules = ['scheduler', 'dblog'];

  /**
   * A user with administration rights.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a 'Basic Page' content type.
    /** @var NodeTypeInterface $node_type */
    $this->nodetype = $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    // @TODO Remove all NodeType::load('page') and use $this->nodetype
    // @TODO Remove all 'page' and use $this->nodetype->get('type')
    // @TODO Remove all 'Basic page' and use $this->nodetype->get('name')

    // Add scheduler functionality to the node type.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Define nodeStorage for use in many tests.
    $this->nodeStorage = $this->container->get('entity.manager')->getStorage('node');

    // Create an administrator user having the main admin permissions, full
    // rights on the 'page' content type and all of the Scheduler permissions.
    // Users with reduced permissions are created in the tests that need them.
    $this->adminUser = $this->drupalCreateUser([
      'administer nodes',
      'access content',
      'access content overview',
      'access site reports',            // required for admin/reports/dblog
      'administer site configuration',  // required for admin/reports/status
      'create page content',
      'edit own page content',
      'delete own page content',
      'view own unpublished content',
      'administer scheduler',
      'schedule publishing of nodes',
      'view scheduled content',
    ]);
  }

}
