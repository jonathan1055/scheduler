<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\media\Entity\MediaType;

/**
 * Tests the API hook functions of the Scheduler module.
 *
 * This class covers the eight hook functions that Scheduler provides, allowing
 * other modules to interact with editting, scheduling and processing via cron.
 *
 * @group scheduler
 */
class SchedulerHooksTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   *
   * @todo 'menu_ui' is in the exported node.type definition, and 'path' is in
   * the entity_form_display. Could these be removed from the config files and
   * then not needed here?
   */
  protected static $modules = ['scheduler_api_test', 'menu_ui', 'path'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Load the custom node type. It will be enabled for Scheduler automatically
    // as that is pre-configured in node.type.scheduler_api_test.yml.
    $this->customName = 'scheduler_api_test';
    $this->customNodetype = NodeType::load($this->customName);

    // Check that the custom node type has loaded OK.
    $this->assertNotNull($this->customNodetype, "Custom node type $this->customName failed to load during setUp");

    // Load the custom media type.
    $this->customMediaName = 'scheduler_api_media_test';
    $this->customMediatype = MediaType::load($this->customMediaName);

    // Check that the custom media type has loaded OK.
    $this->assertNotNull($this->customMediatype, "Custom media type $this->customMediaName failed to load during setUp");

    // Create a web user that has permission to create and edit and schedule
    // the custom entity types.
    $this->webUser = $this->drupalCreateUser([
      'create ' . $this->customName . ' content',
      'edit any ' . $this->customName . ' content',
      "create $this->customMediaName media",
      "edit any $this->customMediaName media",
      'schedule publishing of nodes',
      'view own unpublished media',
      // @todo The permission 'schedule publishing of media' does not seem to be
      // needed. Investigation required. Fix when working on permissions test.
      // 'schedule publishing of media'.
    ]);

  }

  /**
   * Provides test data containing the standard entity types.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id].
   */
  public function dataStandardTypes() {
    $data = [
      0 => ['node', 'testpage'],
      1 => ['media', 'test-video'],
    ];

    // Use unset($data[n]) to remove a temporarily unwanted item, use
    // return [$data[n]] to selectively test just one item, or have the default
    // return $data to test everything.
    return $data;
  }

  /**
   * Provides test data containing the custom entity types.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id].
   */
  public function dataCustomTypes() {
    $data = [
      0 => ['node', 'scheduler_api_test'],
      1 => ['media', 'scheduler_api_media_test'],
    ];
    return $data;
  }

  /**
   * Covers hook_scheduler_nid_list($action)
   *
   * Hook_scheduler_nid_list() allows other modules to add more node ids into
   * the list to be processed. In real scenarios, the third-party module would
   * likely have more complex data structures and/or tables from which to
   * identify nodes to add. In this test, to keep it simple, we identify nodes
   * by the text of the title.
   *
   * This test also covers hook_scheduler_media_list($action).
   *
   * @dataProvider dataStandardTypes()
   */
  public function testIdList($entityTypeId, $bundle) {
    $storage = $this->entityStorageObject($entityTypeId);
    $titleField = ($entityTypeId == 'media') ? 'name' : 'title';
    $this->drupalLogin($this->schedulerUser);

    // Create test entities using the standard scheduler test entity types.
    // Entity 1 is not published and has no publishing date set. The test API
    // module will add this entity into the list to be published.
    $entity1 = $this->createEntity($entityTypeId, $bundle, [
      'status' => FALSE,
      "$titleField" => 'API TEST id list publish me',
    ]);
    // Entity 2 is published and has no unpublishing date set. The test API
    // module will add this entity into the list to be unpublished.
    $entity2 = $this->createEntity($entityTypeId, $bundle, [
      'status' => TRUE,
      "$titleField" => 'API TEST id list unpublish me',
    ]);

    // Before cron, check entity 1 is unpublished and entity 2 is published.
    $this->assertFalse($entity1->isPublished(), "Before cron, $entityTypeId 1 '{$entity1->label()}' should be unpublished.");
    $this->assertTrue($entity2->isPublished(), "Before cron, $entityTypeId 2 '{$entity2->label()}' should be published.");

    // Run cron and refresh the entities.
    scheduler_cron();
    $storage->resetCache();
    $entity1 = $storage->load($entity1->id());
    $entity2 = $storage->load($entity2->id());

    // Check entity 1 is published and entity 2 is unpublished.
    $this->assertTrue($entity1->isPublished(), "After cron, $entityTypeId 1 '{$entity1->label()}' should be published.");
    $this->assertFalse($entity2->isPublished(), "After cron, $entityTypeId 2 '{$entity2->label()}' should be unpublished.");
  }

  /**
   * Covers hook_scheduler_nid_list_alter($action)
   *
   * This hook allows other modules to add or remove node ids from the list to
   * be processed.
   *
   * This test also covers hook_scheduler_media_list_alter($action).
   *
   * @dataProvider dataStandardTypes()
   */
  public function testIdListAlter($entityTypeId, $bundle) {
    $storage = $this->entityStorageObject($entityTypeId);
    $titleField = ($entityTypeId == 'media') ? 'name' : 'title';
    $this->drupalLogin($this->schedulerUser);

    // Create test entities using the standard scheduler test entity types.
    // Entity 1 is set for scheduled publishing, but will be removed by the test
    // API list_alter() function.
    $entity1 = $this->createEntity($entityTypeId, $bundle, [
      'status' => FALSE,
      "$titleField" => 'API TEST list_alter do not publish me',
      'publish_on' => strtotime('-1 day'),
    ]);
    // Entity 2 is not published and has no publishing date set. The test module
    // will add a date and add the id into the list to be published.
    $entity2 = $this->createEntity($entityTypeId, $bundle, [
      'status' => FALSE,
      "$titleField" => 'API TEST list_alter publish me',
    ]);

    // Entity 3 is set for scheduled unpublishing, but will be removed by the
    // test API list_alter() function.
    $entity3 = $this->createEntity($entityTypeId, $bundle, [
      'status' => TRUE,
      "$titleField" => 'API TEST list_alter do not unpublish me',
      'unpublish_on' => strtotime('-1 day'),
    ]);

    // Entity 4 is published and has no unpublishing date set. The test module
    // will add a date and add the id into the list to be unpublished.
    $entity4 = $this->createEntity($entityTypeId, $bundle, [
      'status' => TRUE,
      "$titleField" => 'API TEST list_alter unpublish me',
    ]);

    // Before cron, check 1 and 2 are unpublished and 3 and 4 are published.
    $this->assertFalse($entity1->isPublished(), "Before cron, $entityTypeId 1 '{$entity1->label()}' should be unpublished.");
    $this->assertFalse($entity2->isPublished(), "Before cron, $entityTypeId 2 '{$entity2->label()}' should be unpublished.");
    $this->assertTrue($entity3->isPublished(), "Before cron, $entityTypeId 3 '{$entity3->label()}' should be published.");
    $this->assertTrue($entity4->isPublished(), "Before cron, $entityTypeId 4 '{$entity4->label()}' should be published.");

    // Run cron and refresh the entities from storage.
    scheduler_cron();
    $storage->resetCache();
    $entity1 = $storage->load($entity1->id());
    $entity2 = $storage->load($entity2->id());
    $entity3 = $storage->load($entity3->id());
    $entity4 = $storage->load($entity4->id());

    // Check entity 2 and 3 are published and entity 1 and 4 are unpublished.
    $this->assertFalse($entity1->isPublished(), "After cron, $entityTypeId 1 '{$entity1->label()}' should be unpublished.");
    $this->assertTrue($entity2->isPublished(), "After cron, $entityTypeId 2 '{$entity2->label()}' should be published.");
    $this->assertTrue($entity3->isPublished(), "After cron, $entityTypeId 3 '{$entity3->label()}' should be published.");
    $this->assertFalse($entity4->isPublished(), "After cron, $entityTypeId 4 '{$entity4->label()}' should be unpublished.");
  }

  /**
   * Covers hook_scheduler_allow_publishing()
   *
   * This hook can allow or deny the publishing of individual entities. The test
   * uses the customised content type which has checkboxes 'Approved for
   * publishing' and 'Approved for unpublishing'.
   *
   * This test also covers hook_scheduler_media_allow_publishing().
   *
   * @todo Create and update the nodes through the interface so we can check if
   *   the correct messages are displayed.
   *
   * @dataProvider dataCustomTypes()
   */
  public function testAllowedPublishing($entityTypeId, $bundle) {
    $storage = $this->entityStorageObject($entityTypeId);
    $titleField = ($entityTypeId == 'media') ? 'name' : 'title';
    $this->drupalLogin($this->webUser);

    // Check the 'approved for publishing' field is shown on the node form.
    $this->drupalGet("$entityTypeId/add/$bundle");
    $this->assertSession()->fieldExists('edit-field-approved-publishing-value');

    // Check that the message is shown when scheduling an entity for publishing
    // which is not yet allowed to be published.
    $edit = [
      "{$titleField}[0][value]" => 'Set publish-on date without approval',
      'publish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'publish_on[0][value][time]' => date('H:i:s', time() + 3),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextMatches('/is scheduled for publishing.* but will not be published until approved/');

    // Create an entity that is scheduled but not approved for publishing. Then
    // run cron for scheduler, and check that the entity is still not published.
    $entity = $this->createUnapprovedEntity($entityTypeId, $bundle, 'publish_on');
    scheduler_cron();
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertFalse($entity->isPublished(), 'An unapproved entity is not published during cron processing.');

    // Create an entity and approve it for publishing, run cron for scheduler
    // and check that the entity is published. This is a stronger test than
    // simply approving the previously used entity above, as we do not know what
    // publish state that may be in after the cron run above.
    $entity = $this->createUnapprovedEntity($entityTypeId, $bundle, 'publish_on');
    $this->approve($entityTypeId, $entity->id(), 'field_approved_publishing');
    $this->assertFalse($entity->isPublished(), 'A new approved entity is initially not published.');
    scheduler_cron();
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertTrue($entity->isPublished(), 'An approved entity is published during cron processing.');

    // Turn on immediate publishing when the date is in the past and repeat
    // the tests. It is not needed to run cron jobs here.
    $bundle_field_name = $entity->getEntityType()->get('entity_keys')['bundle'];
    $entity->$bundle_field_name->entity->setThirdPartySetting('scheduler', 'publish_past_date', 'publish')->save();

    // Check that an entity can be approved and published programatically.
    $entity = $this->createUnapprovedEntity($entityTypeId, $bundle, 'publish_on');
    $this->assertFalse($entity->isPublished(), 'An unapproved entity with a date in the past is not published immediately after saving.');
    $this->approve($entityTypeId, $entity->id(), 'field_approved_publishing');
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertTrue($entity->isPublished(), 'An approved entity with a date in the past should be published immediately programatically.');

    // Check that an entity can be approved and published via edit form.
    $entity = $this->createUnapprovedEntity($entityTypeId, $bundle, 'publish_on');
    $this->drupalGet("{$entityTypeId}/{$entity->id()}/edit");
    $this->submitForm(['field_approved_publishing[value]' => '1'], 'Save');
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertTrue($entity->isPublished(), 'An approved entity with a date in the past is published immediately after saving via edit form.');
  }

  /**
   * Covers hook_scheduler_allow_unpublishing()
   *
   * This hook can allow or deny the unpublishing of individual entities. This
   * test is simpler than the test sequence for allowed publishing, because the
   * past date 'publish' option is not applicable.
   *
   * The test also covers hook_scheduler_media_allow_unpublishing().
   *
   * @dataProvider dataCustomTypes()
   */
  public function testAllowedUnpublishing($entityTypeId, $bundle) {
    $storage = $this->entityStorageObject($entityTypeId);
    $titleField = ($entityTypeId == 'media') ? 'name' : 'title';
    $this->drupalLogin($this->webUser);

    // Check the 'approved for unpublishing' field is shown on the node form.
    $this->drupalGet("$entityTypeId/add/$bundle");
    $this->assertSession()->fieldExists('edit-field-approved-unpublishing-value');

    // Check that the message is shown when scheduling an entity for
    // unpublishing which is not yet allowed to be unpublished.
    $edit = [
      "{$titleField}[0][value]" => 'Set unpublish-on date without approval',
      'unpublish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'unpublish_on[0][value][time]' => date('H:i:s', time() + 3),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextMatches('/is scheduled for unpublishing.* but will not be unpublished until approved/');

    // Create an entity that is scheduled but not approved for unpublishing, run
    // cron for scheduler, and check that the entity is still published.
    $entity = $this->createUnapprovedEntity($entityTypeId, $bundle, 'unpublish_on');
    scheduler_cron();
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertTrue($entity->isPublished(), 'An unapproved entity is not unpublished during cron processing.');

    // Create an entity and approve it for unpublishing, run cron for scheduler
    // and check that the entity is unpublished.
    $entity = $this->createUnapprovedEntity($entityTypeId, $bundle, 'unpublish_on');
    $this->approve($entityTypeId, $entity->id(), 'field_approved_unpublishing');
    $this->assertTrue($entity->isPublished(), 'The new approved entity is initially published.');
    scheduler_cron();
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertFalse($entity->isPublished(), 'An approved entity is unpublished during cron processing.');
  }

  /**
   * Creates a new entity that is not approved.
   *
   * The entity will have a publish/unpublish date in the past to make sure it
   * will be included in the next cron run.
   *
   * @param string $entityTypeId
   *   The entity type to create, 'node' or 'media'.
   * @param string $bundle
   *   The bundle to create, 'scheduler_api_test' or 'scheduler_api_media_test'.
   * @param string $date_field
   *   The Scheduler date field to set, either 'publish_on' or 'unpublish_on'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity object.
   */
  protected function createUnapprovedEntity($entityTypeId, $bundle, $date_field) {
    $settings = [
      'status' => ($date_field == 'unpublish_on'),
      $date_field => strtotime('-1 day'),
      'field_approved_publishing' => 0,
      'field_approved_unpublishing' => 0,
    ];
    return $this->createEntity($entityTypeId, $bundle, $settings);
  }

  /**
   * Approves an entity for publication or unpublication.
   *
   * @param string $entityTypeId
   *   The entity type to approve, 'node' or 'media'.
   * @param int $id
   *   The id of the entity to approve.
   * @param string $field_name
   *   The name of the field to set, either 'field_approved_publishing' or
   *   'field_approved_unpublishing'.
   */
  protected function approve($entityTypeId, $id, $field_name) {
    $storage = $this->entityStorageObject($entityTypeId);
    $storage->resetCache([$id]);
    $entity = $storage->load($id);
    $entity->set($field_name, TRUE)->save();
  }

  /**
   * Tests the hooks which allow hiding of scheduler input fields.
   *
   * Covers hook_scheduler_hide_publish_on_field() and
   * hook_scheduler_hide_unpublish_on_field().
   */
  public function testHideField() {
    $this->drupalLogin($this->schedulerUser);

    // Create test nodes.
    $node1 = $this->drupalCreateNode([
      'type' => $this->type,
      'title' => 'Red will not have either field hidden',
    ]);
    $node2 = $this->drupalCreateNode([
      'type' => $this->type,
      'title' => 'Orange will have the publish-on field hidden',
    ]);
    $node3 = $this->drupalCreateNode([
      'type' => $this->type,
      'title' => 'Yellow will have the unpublish-on field hidden',
    ]);
    $node4 = $this->drupalCreateNode([
      'type' => $this->type,
      'title' => 'Green will have both Scheduler fields hidden',
    ]);

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Node 1 'red' should have both fields displayed.
    $this->drupalGet('node/' . $node1->id() . '/edit');
    $assert->ElementExists('xpath', '//input[@id = "edit-publish-on-0-value-date"]');
    $assert->ElementExists('xpath', '//input[@id = "edit-unpublish-on-0-value-date"]');

    // Node 2 'orange' should have only the publish-on field hidden.
    $this->drupalGet('node/' . $node2->id() . '/edit');
    $assert->ElementNotExists('xpath', '//input[@id = "edit-publish-on-0-value-date"]');
    $assert->ElementExists('xpath', '//input[@id = "edit-unpublish-on-0-value-date"]');

    // Node 3 'yellow' should have only the unpublish-on field hidden.
    $this->drupalGet('node/' . $node3->id() . '/edit');
    $assert->ElementExists('xpath', '//input[@id = "edit-publish-on-0-value-date"]');
    $assert->ElementNotExists('xpath', '//input[@id = "edit-unpublish-on-0-value-date"]');

    // Node 4 'green' should have both publish-on and unpublish-on hidden.
    $this->drupalGet('node/' . $node4->id() . '/edit');
    $assert->ElementNotExists('xpath', '//input[@id = "edit-publish-on-0-value-date"]');
    $assert->ElementNotExists('xpath', '//input[@id = "edit-unpublish-on-0-value-date"]');

    // TEMPORARY Create test media.
    // This is done to show that when fixed, the existing hooks are not called
    // for Media and no runtime errrors are produced.
    // @todo Convert this test to cover the four cases above properly.
    // Expand the API module to implement the new hooks.
    // Use a data provider.
    $media1 = $this->createEntity('media');
    $this->drupalGet('media/' . $media1->id() . '/edit');

  }

  /**
   * Tests when other modules process the 'publish' and 'unpublish' actions.
   *
   * This covers hook_scheduler_publish_action() and
   * hook_scheduler_unpublish_action().
   */
  public function testHookPublishUnpublishAction() {
    $this->drupalLogin($this->schedulerUser);

    // Create test nodes.
    $node1 = $this->drupalCreateNode([
      'type' => $this->type,
      'status' => FALSE,
      'title' => 'Red will cause a failure on publishing',
      'publish_on' => strtotime('-1 day'),
    ]);
    $node2 = $this->drupalCreateNode([
      'type' => $this->type,
      'status' => TRUE,
      'title' => 'Orange will be unpublished by the API test module not Scheduler',
      'unpublish_on' => strtotime('-1 day'),
    ]);
    $node3 = $this->drupalCreateNode([
      'type' => $this->type,
      'status' => FALSE,
      'title' => 'Yellow will be published by the API test module not Scheduler',
      'publish_on' => strtotime('-1 day'),
    ]);
    // 'green' nodes will have both fields hidden so is harder to test manually.
    // Therefore introduce a different colour.
    $node4 = $this->drupalCreateNode([
      'type' => $this->type,
      'status' => TRUE,
      'title' => 'Blue will cause a failure on unpublishing',
      'unpublish_on' => strtotime('-1 day'),
    ]);

    // Simulate a cron run.
    scheduler_cron();

    // Check the red node.
    $this->nodeStorage->resetCache([$node1->id()]);
    $node1 = $this->nodeStorage->load($node1->id());
    $this->assertFalse($node1->isPublished(), 'The red node is still unpublished.');
    $this->assertNotEmpty($node1->publish_on->value, 'The red node still has a publish-on date.');

    // Check the orange node.
    $this->nodeStorage->resetCache([$node2->id()]);
    $node2 = $this->nodeStorage->load($node2->id());
    $this->assertFalse($node2->isPublished(), 'The orange node was unpublished by the API test module.');
    $this->assertNotEmpty(stristr($node2->title->value, 'unpublishing processed by API test module'), 'The orange node was processed by the API test module.');
    $this->assertEmpty($node2->unpublish_on->value, 'The orange node no longer has an unpublish-on date.');

    // Check the yellow node.
    $this->nodeStorage->resetCache([$node3->id()]);
    $node3 = $this->nodeStorage->load($node3->id());
    $this->assertTrue($node3->isPublished(), 'The yellow node was published by the API test module.');
    $this->assertNotEmpty(stristr($node3->title->value, 'publishing processed by API test module'), 'The yellow node was processed by the API test module.');
    $this->assertEmpty($node3->publish_on->value, 'The yellow node no longer has a publish-on date.');

    // Check the blue node.
    $this->nodeStorage->resetCache([$node4->id()]);
    $node4 = $this->nodeStorage->load($node4->id());
    $this->assertTrue($node4->isPublished(), 'The green node is still published.');
    $this->assertNotEmpty($node4->unpublish_on->value, 'The green node still has an unpublish-on date.');

  }

}
