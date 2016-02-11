<?php
/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerValidationTest.
 */

namespace Drupal\scheduler\Tests;

/**
 * Tests the validation when editing a node.
 *
 * @group scheduler
 */
class SchedulerValidationTest extends SchedulerTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests the validation when editing a node.
   */
  public function testValidationDuringEdit() {
    $this->drupalLogin($this->adminUser);

    // Create an unpublished page node.
    $settings = [
      'type' => 'page',
      'status' => FALSE,
      'title[0][value]' => $this->randomString(15),
    ];
    $node = $this->drupalCreateNode($settings);

    // Set unpublishing to be required.
    $node->type->entity->setThirdPartySetting('scheduler', 'unpublish_required', TRUE)->save();

    // Edit the unpublished node and check that if a publish-on date is entered
    // then an unpublish-on date is also needed.
    $edit = [
      'publish_on[0][value][date]' => date('Y-m-d', strtotime('+1 day', REQUEST_TIME)), ### @TODO should we get the default format? not hard-code.
      'publish_on[0][value][time]' => date('H:i:s', strtotime('+1 day', REQUEST_TIME)),
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep unpublished'));
    $this->assertRaw(t("If you set a 'publish-on' date then you must also set an 'unpublish-on' date."), 'Validation prevents entering a publish-on date with no unpublish-on date if unpublishing is required.');

    // Edit the node and check that if both dates are entered then the unpublish
    // date must be later than the publish-on date.
    $edit = [
      'publish_on[0][value][date]' => \Drupal::service('date.formatter')->format(REQUEST_TIME + 7200, 'custom', 'Y-m-d'),
      'publish_on[0][value][time]' => \Drupal::service('date.formatter')->format(REQUEST_TIME + 7200, 'custom', 'H:i:s'),
      'unpublish_on[0][value][date]' => \Drupal::service('date.formatter')->format(REQUEST_TIME + 3600, 'custom', 'Y-m-d'),
      'unpublish_on[0][value][time]' => \Drupal::service('date.formatter')->format(REQUEST_TIME + 3600, 'custom', 'H:i:s'),
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep unpublished'));
    $this->assertRaw(t("The 'unpublish on' date must be later than the 'publish on' date."), 'Validation prevents entering an unpublish-on date which is earlier than the publish-on date.');
  }
}
