<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class to provide common test setup.
 *
 * Extends the preferred BrowserTestBase instead of the old WebTestBase.
 */
abstract class SchedulerBrowserTestBase extends BrowserTestBase {

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
   * The name of the content type created for testing
   *
   * @var string
   */
  protected $type;

  /**
   * The node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodetype;

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

    // Create a 'Basic Page' content type, with 'page' as the identifier.
    $this->type = 'page';
    // @TODO Remove all 'page' and use $this->type
    /** @var NodeTypeInterface $nodetype */
    $this->nodetype = $this->drupalCreateContentType(['type' => $this->type, 'name' => 'Basic page']);

    // Add scheduler functionality to the node type.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Define nodeStorage for use in many tests.
    /** @var EntityStorageInterface $nodeStorage */
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
      "create $this->type content",
      "edit own $this->type content",
      "delete own $this->type content",
      'view own unpublished content',
      'administer scheduler',
      'schedule publishing of nodes',
      'view scheduled content',
    ]);
  }

}
