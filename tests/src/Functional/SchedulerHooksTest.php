<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\node\Entity\NodeType;

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
    $this->assertNotNull($this->customNodetype, 'Custom node type "' . $this->customName . '"  was created during install');

    // Create a web user for this content type.
    $this->webUser = $this->drupalCreateUser([
      'create ' . $this->customName . ' content',
      'edit any ' . $this->customName . ' content',
      'schedule publishing of nodes',
    ]);

  }

  /**
   * Provides test data for some tests in this class.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id].
   */
  public function dataHookTestData() {
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
   * @dataProvider dataHookTestData()
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
   * @dataProvider dataHookTestData()
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
   * This hook can allow or deny the publishing of individual nodes. This test
   * uses the customised content type which has checkboxes 'Approved for
   * publication' and 'Approved for unpublication'.
   *
   * @todo Create and update the nodes through the interface so we can check if
   *   the correct messages are displayed.
   */
  public function testAllowedPublishing() {
    $this->drupalLogin($this->webUser);
    // Check the 'approved for publishing' field is shown on the node form.
    $this->drupalGet('node/add/' . $this->customName);
    $this->assertSession()->fieldExists('edit-field-approved-publishing-value');

    // Check that the message is shown when scheduling a node for publishing
    // which is not yet allowed to be published.
    $edit = [
      'title[0][value]' => 'Set publish-on date without approval',
      'publish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'publish_on[0][value][time]' => date('H:i:s', time() + 3),
    ];
    $this->drupalPostForm('node/add/' . $this->customName, $edit, 'Save');
    $this->assertSession()->pageTextContains('is scheduled for publishing, but will not be published until approved.');

    // Create a node that is scheduled but not approved for publication. Then
    // simulate a cron run, and check that the node is still not published.
    $node = $this->createUnapprovedNode('publish_on');
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertFalse($node->isPublished(), 'An unapproved node is not published during cron processing.');

    // Create a node and approve it for publication, simulate a cron run and
    // check that the node is published. This is a stronger test than simply
    // approving the previously used node above, as we do not know what publish
    // state that may be in after the cron run above.
    $node = $this->createUnapprovedNode('publish_on');
    $this->approveNode($node->id(), 'field_approved_publishing');
    $this->assertFalse($node->isPublished(), 'A new approved node is initially not published.');
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertTrue($node->isPublished(), 'An approved node is published during cron processing.');

    // Turn on immediate publication of nodes with publication dates in the past
    // and repeat the tests. It is not needed to simulate cron runs here.
    $this->customNodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'publish')->save();
    $node = $this->createUnapprovedNode('publish_on');
    $this->assertFalse($node->isPublished(), 'An unapproved node with a date in the past is not published immediately after saving.');

    // Check that the node can be approved and published programatically.
    $this->approveNode($node->id(), 'field_approved_publishing');
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertTrue($node->isPublished(), 'An approved node with a date in the past is published immediately via $node->set()->save().');

    // Check that a node can be approved and published via edit form.
    $node = $this->createUnapprovedNode('publish_on');
    $this->drupalPostForm('node/' . $node->id() . '/edit', ['field_approved_publishing[value]' => '1'], 'Save');
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertTrue($node->isPublished(), 'An approved node with a date in the past is published immediately after saving via edit form.');

    // Show the dblog messages.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/reports/dblog');
  }

  /**
   * Covers hook_scheduler_allow_unpublishing()
   *
   * This hook can allow or deny the unpublishing of individual nodes. This test
   * is simpler than the test sequence for allowed publishing, because the past
   * date 'publish' option is not applicable.
   */
  public function testAllowedUnpublishing() {
    $this->drupalLogin($this->webUser);
    // Check the 'approved for unpublishing' field is shown on the node form.
    $this->drupalGet('node/add/' . $this->customName);
    $this->assertSession()->fieldExists('edit-field-approved-unpublishing-value');

    // Check that the message is shown when scheduling a node for unpublishing
    // which is not yet allowed to be unpublished.
    $edit = [
      'title[0][value]' => 'Set unpublish-on date without approval',
      'unpublish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'unpublish_on[0][value][time]' => date('H:i:s', time() + 3),
    ];
    $this->drupalPostForm('node/add/' . $this->customName, $edit, 'Save');
    $this->assertSession()->pageTextContains('is scheduled for unpublishing, but will not be unpublished until approved.');

    // Create a node that is scheduled but not approved for unpublication. Then
    // simulate a cron run, and check that the node is still published.
    $node = $this->createUnapprovedNode('unpublish_on');
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertTrue($node->isPublished(), 'An unapproved node is not unpublished during cron processing.');

    // Create a node, then approve it for unpublishing, simulate a cron run and
    // check that the node is now unpublished.
    $node = $this->createUnapprovedNode('unpublish_on');
    $this->approveNode($node->id(), 'field_approved_unpublishing');
    $this->assertTrue($node->isPublished(), 'A new approved node is initially published.');
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertFalse($node->isPublished(), 'An approved node is unpublished during cron processing.');

    // Show the dblog messages.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/reports/dblog');
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
    $settings = [
      'status' => ($date_field == 'unpublish_on'),
      $date_field => strtotime('-1 day'),
      'field_approved_publishing' => 0,
      'field_approved_unpublishing' => 0,
      'type' => $this->customName,
    ];
    return $this->drupalCreateNode($settings);
  }

  /**
   * Approves a node for publication or unpublication.
   *
   * @param int $nid
   *   The id of the node to approve.
   * @param string $field_name
   *   The name of the field to set, either 'field_approved_publishing' or
   *   'field_approved_unpublishing'.
   */
  protected function approveNode($nid, $field_name) {
    $this->nodeStorage->resetCache([$nid]);
    $node = $this->nodeStorage->load($nid);
    $node->set($field_name, TRUE)->save();
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
