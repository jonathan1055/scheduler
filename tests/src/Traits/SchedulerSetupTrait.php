<?php

namespace Drupal\Tests\scheduler\Traits;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Generic setup for all Scheduler tests.
 *
 * This is used in SchedulerBrowserTestBase and SchedulerJavascriptTestBase.
 */
trait SchedulerSetupTrait {

  use CronRunTrait;

  use NodeCreationTrait {
    // Allow this trait to be used in Kernel tests (which do not use
    // BrowserTestBase) and hence will not have these two functions.
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }

  // @todo Remove this when core 8.8 is the lowest supported version.
  // @see https://www.drupal.org/project/scheduler/issues/3136744
  use PhpunitCompatibilityCore87Trait;

  /**
   * A user with administration rights.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user with permission to schedule content.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $schedulerUser;

  /**
   * The internal name of the standard content type created for testing.
   *
   * @var string
   */
  protected $type = 'testpage';

  /**
   * The readable name of the standard content type created for testing.
   *
   * @var string
   */
  protected $typeName = 'Test Page';

  /**
   * The node type object.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodetype;

  /**
   * The machine name of the content type which is not enabled for scheduling.
   *
   * @var string
   */
  protected $nonSchedulerType = 'not_for_scheduler';

  /**
   * The readable name of content type which is not enabled for scheduling.
   *
   * @var string
   */
  protected $nonSchedulerTypeName = 'Not For Scheduler';

  /**
   * The node type object which is not enabled for scheduling.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nonSchedulerNodeType;

  /**
   * The node storage object.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The request time stored as interger for direct re-use in many tests.
   *
   * @var int
   */
  protected $requestTime;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Set common properties, define content types and create users.
   */
  public function schedulerSetUp() {

    // Create a test content type using the type and name constants defined
    // above. The tests should use $this->type and $this->typeName and not use
    // $this->nodetype->get('type') or $this->nodetype->get('name'), nor have
    // the hard-coded strings 'testpage' or 'Test Page'.
    /** @var NodeTypeInterface $nodetype */
    $this->nodetype = $this->drupalCreateContentType([
      'type' => $this->type,
      'name' => $this->typeName,
    ]);

    // Add scheduler functionality to the content type.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // The majority of tests use the standard Scheduler-enabled content type but
    // we also need a content type which is not enabled for Scheduler.
    $this->nonSchedulerNodeType = $this->drupalCreateContentType([
      'type' => $this->nonSchedulerType,
      'name' => $this->nonSchedulerTypeName,
    ]);

    // Define nodeStorage for use in many tests.
    /** @var EntityStorageInterface $nodeStorage */
    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');

    // Create an administrator user having the main admin permissions, full
    // rights on the test content type and all of the Scheduler permissions.
    // 'access site reports' is required for admin/reports/dblog.
    // 'administer site configuration' is required for admin/reports/status.
    // 'administer content types' is required for admin/structure/types/manage.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access content overview',
      'access site reports',
      'administer nodes',
      'administer content types',
      'administer site configuration',
      'create ' . $this->type . ' content',
      'edit any ' . $this->type . ' content',
      'delete any ' . $this->type . ' content',
      'create ' . $this->nonSchedulerType . ' content',
      'edit any ' . $this->nonSchedulerType . ' content',
      'delete any ' . $this->nonSchedulerType . ' content',
      'view own unpublished content',
      'administer scheduler',
      'schedule publishing of nodes',
      'view scheduled content',
    ]);
    $this->adminUser->set('name', 'Admolly the Admin user')->save();

    // Create an ordinary Scheduler user, with permission to create and schedule
    // content but not with administrator permissions.
    $this->schedulerUser = $this->drupalCreateUser([
      'create ' . $this->type . ' content',
      'edit own ' . $this->type . ' content',
      'delete own ' . $this->type . ' content',
      'view own unpublished content',
      'schedule publishing of nodes',
    ]);
    $this->schedulerUser->set('name', 'Shelly the Scheduler user')->save();

    // Store the database connection for re-use in the actual tests.
    $this->database = $this->container->get('database');

    // Determine the request time and save for re-use in the actual tests.
    $this->requestTime = $this->container->get('datetime.time')->getRequestTime();

    // Store the core dateFormatter service for re-use in the actual tests.
    $this->dateFormatter = $this->container->get('date.formatter');

  }

  /**
   * Adds a set of permissions to an existing user.
   *
   * This avoids having to create new users when a test requires additional
   * permissions, as that leads to having a list of existing permissions which
   * has to be kept in sync with the standard user permissions.
   *
   * Each test user has two roles, 'authenticated' and one other randomly-named
   * role assigned when the user is created, and unique to that user. This is
   * the role to which these permissions are added.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user object.
   * @param array $permissions
   *   The machine names of new permissions to add to the user's unique role.
   */
  public function addPermissionsToUser(AccountInterface $user, array $permissions) {
    /** @var \Drupal\user\Entity\RoleStorageInterface $roleStorage */
    $roleStorage = $this->container->get('entity_type.manager')->getStorage('user_role');
    foreach ($user->getRoles() as $rid) {
      // The user will have two roles, 'authenticated' and one other.
      if ($rid != 'authenticated') {
        $role = $roleStorage->load($rid);
        foreach ($permissions as $permission) {
          $role->grantPermission($permission);
        }
        $role->save();
      }
    }
  }

  /**
   * Creates a test entity.
   *
   * This is called to generate a node, media or product entity, for tests that
   * process all types of entities, either in loops or via a data provider.
   *
   * @param string $entityTypeId
   *   The name of the entity type - 'node', 'media' or 'commerce_product'.
   * @param string $bundle
   *   The name of the bundle. Optional, will default to $this->type for nodes
   *   $this->mediaTypeName for media, or $this->productTypeName for products.
   * @param array $values
   *   Values for the new entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity object.
   */
  public function createEntity(string $entityTypeId, string $bundle = NULL, array $values = []) {

    switch ($entityTypeId) {
      case 'media':
        $values += ['bundle' => $bundle ?? $this->mediaTypeName];
        // For Media, the title is stored in the 'name' field, so get the title
        // when the 'name' is not defined, to allow the same $value parameters
        // as for Node.
        if (isset($values['title'])) {
          $values['name'] = $values['name'] ?? $values['title'];
          unset($values['title']);
        }
        $entity = $this->createMediaItem($values);
        break;

      case 'commerce_product':
        $values += ['type' => $bundle ?? $this->productTypeName];
        $entity = $this->createProduct($values);
        break;

      case 'node':
      default:
        // For nodes the field for bundle is called 'type'.
        $values += ['type' => $bundle ?? $this->type];
        $entity = $this->drupalCreateNode($values);
        break;
    }
    return $entity;
  }

  /**
   * Gets an entity by title, a direct replacement of drupalGetNodeByTitle().
   *
   * This allows the same test code to be run for Nodes, Media and Products.
   *
   * @param string $entityTypeId
   *   The machine id of the entity type - 'node', 'media', 'commerce_product'.
   * @param string $title
   *   The title to match with.
   *
   * @return mixed
   *   Either a node object, media object, commerce_product object, or none.
   */
  public function getEntityByTitle(string $entityTypeId, string $title) {
    switch ($entityTypeId) {
      case 'node':
        return $this->drupalGetNodeByTitle($title);

      case 'media':
        return $this->getMediaItem($title);

      case 'commerce_product':
        return $this->getProduct($title);

      default:
        // Incorrect parameter value.
        throw new \Exception(sprintf('Unrecognised entityTypeId value "%s" passed to getEntityByTitle()', $entityTypeId));
    }
  }

  /**
   * Returns the stored entity type object from a type id and bundle id.
   *
   * This allows previous usages of $this->nodetype to be replaced by
   * entityTypeObject($entityTypeId) or entityTypeObject($entityTypeId, $bundle)
   * when expanding tests to cover Media and Product entities.
   *
   * @param string $entityTypeId
   *   The machine id of the entity type - 'node', 'media', 'commerce_product'.
   * @param string $bundle
   *   The machine name of the bundle, for example 'testpage', 'test_video',
   *   'not_for_scheduler', etc. Optional. Defaults to the enabled bundle. Also
   *   accepts the fixed string 'non-enabled' to indicate the non-enabled bundle
   *   for the entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The stored entity type object.
   */
  public function entityTypeObject(string $entityTypeId, string $bundle = NULL) {
    switch (TRUE) {
      case ($entityTypeId == 'node' && (empty($bundle) || $bundle == $this->type)):
        return $this->nodetype;

      case ($entityTypeId == 'node' && ($bundle == 'non-enabled' || $bundle == $this->nonSchedulerType)):
        return $this->nonSchedulerNodeType;

      case ($entityTypeId == 'media' && (empty($bundle) || $bundle == $this->mediaTypeName)):
        return $this->mediaType;

      case ($entityTypeId == 'media' && ($bundle == 'non-enabled' || $bundle == $this->nonSchedulerMediaTypeName)):
        return $this->nonSchedulerMediaType;

      case ($entityTypeId == 'commerce_product' && (empty($bundle) || $bundle == $this->productTypeName)):
        return $this->productType;

      case ($entityTypeId == 'commerce_product' && ($bundle == 'non-enabled' || $bundle == $this->nonSchedulerProductTypeName)):
        return $this->nonSchedulerProductType;

      default:
        // Incorrect parameter values.
        throw new \Exception(sprintf('Unrecognised entityTypeId and bundle combination "%s" and "%s" passed to entityTypeObject()', $entityTypeId, $bundle));
    }
  }

  /**
   * Returns the url for adding an entity, for use in drupalGet().
   *
   * @param string $entityTypeId
   *   The machine id of the entity type - 'node', 'media', 'commerce_product'.
   * @param string $bundle
   *   The machine name of the bundle, for example 'testpage', 'test_video',
   *   'not_for_scheduler', etc. Optional. Defaults to the enabled bundle. Also
   *   accepts the fixed string 'non-enabled' to indicate the non-enabled bundle
   *   for the entity type.
   *
   * @return \Drupal\Core\Url
   *   The url object for adding the required entity.
   */
  public function entityAddUrl(string $entityTypeId, string $bundle = NULL) {
    switch ($entityTypeId) {
      case 'node':
        $bundle = ($bundle == 'non-enabled') ? $this->nonSchedulerType : ($bundle ?? $this->type);
        $route = 'node.add';
        $type_parameter = 'node_type';
        break;

      case 'media':
        $bundle = ($bundle == 'non-enabled') ? $this->nonSchedulerMediaTypeName : ($bundle ?? $this->mediaTypeName);
        $route = 'entity.media.add_form';
        $type_parameter = 'media_type';
        break;

      case 'commerce_product':
        $bundle = ($bundle == 'non-enabled') ? $this->nonSchedulerProductTypeName : ($bundle ?? $this->productTypeName);
        $route = 'entity.commerce_product.add_form';
        $type_parameter = 'commerce_product_type';
        break;

      default:
    }
    if (!$url = Url::fromRoute($route, [$type_parameter => $bundle])) {
      // Incorrect parameter values.
      throw new \Exception(sprintf('Invalid entityTypeId "%s" or bundle "%s" passed to entityAddUrl()', $entityTypeId, $bundle));
    }
    return $url;
  }

  /**
   * Returns the storage object of the entity type passed by string.
   *
   * This allows previous usage of the hard-coded $this->nodeStorage to be
   * replaced with $this->entityStorageObject($entityTypeId) when expanding the
   * tests to cover media and product entity types.
   *
   * @param string $entityTypeId
   *   The machine id of the entity type.
   *
   * @return \Drupal\Core\Entity\ContentEntityStorageInterface
   *   The entity storage object.
   */
  public function entityStorageObject(string $entityTypeId) {
    return $this->container->get('entity_type.manager')->getStorage($entityTypeId);
  }

  /**
   * Provides test data containing the standard entity types.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id]. The array
   *   key is #entity_type_id, to allow easy removal of unwanted rows later.
   */
  public function dataStandardEntityTypes() {
    // The data provider has access to $this where the values are set in the
    // property definition.
    $data = [
      '#node' => ['node', $this->type],
      '#media' => ['media', $this->mediaTypeName],
      '#commerce_product' => ['commerce_product', $this->productTypeName],
    ];
    return $data;
  }

}
