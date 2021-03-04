<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the permissions of the Scheduler module.
 *
 * @group scheduler
 */
class SchedulerPermissionsTest extends SchedulerBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create two users who can create and edit the standard scheduler-enabled
    // node and media entity types. One user can schedule nodes but not media,
    // the other can schedule media but not nodes.
    $this->nodeUser = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'create ' . $this->type . ' content',
      'edit own ' . $this->type . ' content',
      'create ' . $this->mediaTypeName . ' media',
      'edit own ' . $this->mediaTypeName . ' media',
      'schedule publishing of nodes',
      'view own unpublished content',
    ]);
    $this->nodeUser->set('name', 'Noddy the Node Editor')->save();

    $this->mediaUser = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'create ' . $this->type . ' content',
      'edit own ' . $this->type . ' content',
      'create ' . $this->mediaTypeName . ' media',
      'edit own ' . $this->mediaTypeName . ' media',
      'schedule publishing of media',
      'view own unpublished media',
    ]);
    $this->mediaUser->set('name', 'Medina the Media Editor')->save();
  }

  /**
   * Tests that users without permission do not see the scheduler date fields.
   *
   * @dataProvider dataPermissionsTest()
   */
  public function testUserPermissionsAdd($entityTypeId, $bundle, $user) {
    $titleField = ($entityTypeId == 'media') ? 'name' : 'title';

    // Log in with the required user, as specified by the parameter.
    $this->drupalLogin($this->$user);

    // Initially run tests when publishing and unpublishing are not required.
    $this->entityTypeObject($entityTypeId)->setThirdPartySetting('scheduler', 'publish_required', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', FALSE)
      ->save();

    // Check that the fields are displayed as expected when creating an entity.
    // If the user variable matches the entity type id then that user has
    // scheduling permission on this type, so the fields should be shown.
    // Otherwise the fields should not be shown.
    $this->drupalGet("$entityTypeId/add/$bundle");
    if (strpos($user, $entityTypeId) !== FALSE) {
      $this->assertSession()->fieldExists('publish_on[0][value][date]');
      $this->assertSession()->fieldExists('unpublish_on[0][value][date]');
    }
    else {
      $this->assertSession()->fieldNotExists('publish_on[0][value][date]');
      $this->assertSession()->fieldNotExists('unpublish_on[0][value][date]');
    }

    // Check that the new entity can be saved and published.
    $title = $this->randomString(15);
    $edit = ["{$titleField}[0][value]" => $title, 'status[value]' => TRUE];
    // If this is a media then we also need to attach a source file.
    if ($entityTypeId == 'media') {
      $this->attachMediaFile($bundle);
    }
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains(sprintf('%s %s has been created.', $this->entityTypeObject($entityTypeId)->label(), $title));
    $this->assertNotEmpty($entity = $this->getEntityByTitle($entityTypeId, $title), sprintf('The new %s with title "%s" was created sucessfully.', $entityTypeId, $title));
    $this->assertTrue($entity->isPublished(), 'The new entity is published');

    // Check that a new entity can be saved as unpublished.
    $title = $this->randomString(15);
    $edit = ["{$titleField}[0][value]" => $title, 'status[value]' => FALSE];
    $this->drupalGet("$entityTypeId/add/$bundle");
    if ($entityTypeId == 'media') {
      $this->attachMediaFile($bundle);
    }
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains(sprintf('%s %s has been created.', $this->entityTypeObject($entityTypeId)->label(), $title));
    $this->assertNotEmpty($entity = $this->getEntityByTitle($entityTypeId, $title), sprintf('The new %s with title "%s" was created sucessfully.', $entityTypeId, $title));
    $this->assertFalse($entity->isPublished(), 'The new entity is unpublished');

    // Set publishing and unpublishing to required, to make it a stronger test.
    // @todo Add tests when scheduled publishing and unpublishing are required.
    // Cannot be done until we make a decision on what 'required'  means.
    // @see https://www.drupal.org/node/2707411
    // "Conflict between 'required publishing' and not having scheduler
    // permission"
  }

  /**
   * Tests that users without permission can edit existing scheduled content.
   *
   * @dataProvider dataPermissionsTest()
   */
  public function testUserPermissionsEdit($entityTypeId, $bundle, $user) {
    $storage = $this->entityStorageObject($entityTypeId);
    $titleField = ($entityTypeId == 'media') ? 'name' : 'title';

    // Log in with the required user, as specified by the parameter.
    $this->drupalLogin($this->$user);

    $publish_time = strtotime('+ 6 hours', $this->requestTime);
    $unpublish_time = strtotime('+ 10 hours', $this->requestTime);

    // Create an unpublished entity with a publish_on date.
    $unpublished_entity = $this->createEntity($entityTypeId, $bundle, [
      'status' => FALSE,
      'publish_on' => $publish_time,
    ]);

    // Verify that the publish_on date is stored as expected before editing.
    $this->assertEquals($publish_time, $unpublished_entity->publish_on->value, 'The publish_on value is stored correctly before edit.');

    // Edit the unpublished entity and check that the fields are displayed as
    // expected, depending on the user.
    $this->drupalGet("{$entityTypeId}/{$unpublished_entity->id()}/edit");
    if (strpos($user, $entityTypeId) !== FALSE) {
      $this->assertSession()->fieldExists('publish_on[0][value][date]');
      $this->assertSession()->fieldExists('unpublish_on[0][value][date]');
    }
    else {
      $this->assertSession()->fieldNotExists('publish_on[0][value][date]');
      $this->assertSession()->fieldNotExists('unpublish_on[0][value][date]');
    }

    // Save the entity and check the title is updated as expected.
    $title = 'For Publishing ' . $this->randomString(10);
    $this->submitForm(["{$titleField}[0][value]" => $title], 'Save');
    $unpublished_entity = $storage->load($unpublished_entity->id());
    $this->assertEquals($title, $unpublished_entity->label(), 'The unpublished entity title has been updated correctly after edit.');

    // Test that the publish_on date is still stored and is unchanged.
    $this->assertEquals($publish_time, $unpublished_entity->publish_on->value, 'The publish_on value is still stored correctly after edit.');

    // Repeat for unpublishing. Create an entity scheduled for unpublishing.
    $published_entity = $this->createEntity($entityTypeId, $bundle, [
      'status' => TRUE,
      'unpublish_on' => $unpublish_time,
    ]);

    // Verify that the unpublish_on date is stored as expected before editing.
    $this->assertEquals($unpublish_time, $published_entity->unpublish_on->value, 'The unpublish_on value is stored correctly before edit.');

    // Edit the published entity and save.
    $title = 'For Unpublishing ' . $this->randomString(10);
    $this->drupalGet("{$entityTypeId}/{$published_entity->id()}/edit");
    $this->submitForm(["{$titleField}[0][value]" => $title], 'Save');

    // Check the updated title, to verify that edit and save was sucessful.
    $published_entity = $storage->load($published_entity->id());
    $this->assertEquals($title, $published_entity->label(), 'The published entity title has been updated correctly after edit.');

    // Test that the unpublish_on date is still stored and is unchanged.
    $this->assertEquals($unpublish_time, $published_entity->unpublish_on->value, 'The unpublish_on value is still stored correctly after edit.');
  }

  /**
   * Provides data for testUserPermissionsAdd() and testUserPermissionsEdit()
   *
   * The data in dataStandardEntityTypes() is expanded to test each entity type
   * with a user who does have scheduler permission and a user who does not.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id, user name].
   */
  public function dataPermissionsTest() {
    $data = [];
    foreach ($this->dataStandardEntityTypes() as $values) {
      $data[] = array_merge($values, ['nodeUser']);
      $data[] = array_merge($values, ['mediaUser']);
    }
    return $data;
  }

}
