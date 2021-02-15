<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests deletion of entities enabled for Scheduler.
 *
 * This checks how the deletion of an entity interacts with the Scheduler
 * 'required' options and scheduled dates in the past.
 *
 * @group scheduler
 */
class SchedulerDeleteEntityTest extends SchedulerBrowserTestBase {

  /**
   * Tests the deletion of an entity when the scheduler dates are required.
   *
   * Check that it is possible to delete an entity that does not have a
   * publishing date set, when scheduled publishing is required.
   * Likewise for unpublishing.
   *
   * @see https://www.drupal.org/project/scheduler/issues/1614880
   *
   * @dataProvider dataDeleteEntity()
   */
  public function testDeleteEntityWhenSchedulingIsRequired($entityType, $bundle, $typeFieldName) {
    // Log in.
    $this->drupalLogin($this->adminUser);

    // Create a published and an unpublished entity, with no scheduled dates.
    $published_entity = $this->createEntity($entityType, $bundle, [
      'status' => TRUE,
    ]);
    $unpublished_entity = $this->createEntity($entityType, $bundle, [
      'status' => FALSE,
    ]);

    // Make scheduled publishing and unpublishing required.
    $published_entity->$typeFieldName->entity->setThirdPartySetting('scheduler', 'publish_required', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', TRUE)
      ->save();
    $entity_type_label = $published_entity->getEntityType()->getSingularLabel();

    // Check that deleting the entity does not throw form validation errors.
    $this->drupalGet("$entityType/{$published_entity->id()}/edit");
    $this->clickLink('Delete');
    // The text 'error message' is used in a header h2 html tag which is
    // normally made hidden from browsers but will be in the page source.
    // It is also good when testing for the absense of something to also test
    // for the presence of text, hence the second assertion for each check.
    $this->assertSession()->pageTextNotContains('Error message');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the $entity_type_label {$published_entity->label()}");

    // Do the same test for the unpublished entity.
    $this->drupalGet("$entityType/{$unpublished_entity->id()}/edit");
    $this->clickLink('Delete');
    $this->assertSession()->pageTextNotContains('Error message');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the $entity_type_label {$unpublished_entity->label()}");
  }

  /**
   * Tests the deletion of scheduled entities.
   *
   * Check that entities can be deleted with no validation errors even if the
   * dates are in the past.
   *
   * @see https://www.drupal.org/project/scheduler/issues/2627370
   *
   * @dataProvider dataDeleteEntity()
   */
  public function testDeleteEntityWithPastDates($entityType, $bundle, $typeFieldName) {
    // Log in.
    $this->drupalLogin($this->adminUser);

    // Create entities with publish_on and unpublish_on dates in the past.
    $published_entity = $this->createEntity($entityType, $bundle, [
      'status' => TRUE,
      'unpublish_on' => strtotime('- 2 day'),
    ]);
    $unpublished_entity = $this->createEntity($entityType, $bundle, [
      'status' => FALSE,
      'publish_on' => strtotime('- 2 day'),
    ]);
    $entity_type_label = $published_entity->getEntityType()->getSingularLabel();

    // Attempt to delete the published entity and check for no validation error.
    $this->drupalGet("$entityType/{$published_entity->id()}/edit");
    $this->clickLink('Delete');
    $this->assertSession()->pageTextNotContains('Error message');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the $entity_type_label {$published_entity->label()}");

    // Attempt to delete the unpublished entity and check no validation error.
    $this->drupalGet("$entityType/{$unpublished_entity->id()}/edit");
    $this->clickLink('Delete');
    $this->assertSession()->pageTextNotContains('Error message');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the $entity_type_label {$unpublished_entity->label()}");
  }

  /**
   * Provides data for both of the test functions in this class.
   *
   * @return array
   *   Each array item has the values: [entity type, bundle id, type fieldname].
   */
  public function dataDeleteEntity() {
    $data = [
      0 => ['node', 'testpage', 'type'],
      1 => ['media', 'test-video', 'bundle'],
    ];

    // Use unset($data[n]) to remove a temporarily unwanted item, use
    // return [$data[n]] to selectively test just one item, or have the default
    // return $data to test everything.
    return $data;
  }

}
