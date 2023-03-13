<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests that repeat widget is being enabled along with scheduled date fields.
 *
 * @group scheduler
 */
class SchedulerRepeatFormTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   */
  protected static $modules = ['scheduler_repeat', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create node type that only has scheduled publishing enabled.
    $this->onlyPublishOnNodetype = $this->drupalCreateContentType([
      'type' => 'only_publish_hon_nodetype',
      'name' => 'Only publish on nodetype',
    ]);
    $this->onlyPublishOnNodetype->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', FALSE)
      ->save();

    // Create node type that only has scheduled unpublishing enabled.
    $this->onlyUnpublishOnNodetype = $this->drupalCreateContentType([
      'type' => 'only_unpublish_hon_nodetype',
      'name' => 'Only unpublish on nodetype',
    ]);
    $this->onlyUnpublishOnNodetype->setThirdPartySetting('scheduler', 'publish_enable', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

  }

  /**
   * Tests that the repeat input is displayed correctly in the node edit form.
   *
   * This tests covers scheduler_repeat_form_node_form_alter().
   */
  public function testRepeatNodeForm() {
    $this->drupalLogin($this->adminUser);

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Check that repeat selection is shown in the vertical tab.
    $this->drupalGet('node/add/' . $this->type);
    $assert->elementExists('xpath', '//div[contains(@class, "form-type-vertical-tabs")]//details[@id = "edit-scheduler-settings"]//div[@id = "edit-scheduler-repeat-wrapper"]');

    // Check that repeat selection is shown in the fieldset.
    $this->nodetype->setThirdPartySetting('scheduler', 'fields_display_mode', 'fieldset')->save();
    $this->drupalGet('node/add/' . $this->type);
    $assert->elementExists('xpath', '//details[@id = "edit-scheduler-settings"]//div[@id = "edit-scheduler-repeat-wrapper"]');

    // Check that repeat selection is shown also when editing node.
    $options = [
      'title' => 'Contains scheduled dates ' . $this->randomMachineName(10),
      'type' => $this->type,
      'publish_on' => strtotime('+1 day'),
      'unpublish_on' => strtotime('+2 day'),
    ];
    $node = $this->drupalCreateNode($options);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $assert->elementExists('xpath', '//details[@id = "edit-scheduler-settings"]//div[@id = "edit-scheduler-repeat-wrapper"]');

    // Check that repeat selection is not shown when no scheduling is enabled.
    $this->drupalGet('node/add/' . $this->nonSchedulerNodeType->id());
    $assert->elementNotExists('xpath', '//div[@id = "edit-scheduler-repeat-wrapper"]');

    // Check that repeat selection is not shown if publishing is not enabled.
    $this->drupalGet('node/add/' . $this->onlyPublishOnNodetype->id());
    $assert->elementNotExists('xpath', '//div[@id = "edit-scheduler-repeat-wrapper"]');

    // Check that repeat selection is not shown if unpublishing is not enabled.
    $this->drupalGet('node/add/' . $this->onlyUnpublishOnNodetype->id());
    $assert->elementNotExists('xpath', '//div[@id = "edit-scheduler-repeat-wrapper"]');

  }

  /**
   * Tests the settings entry in the content type form display.
   *
   * This test covers scheduler_repeat_entity_base_field_info().
   */
  public function testRepeatManageFormDisplay() {
    // Create a custom user with admin permissions but also permission to use
    // the field_ui module 'node form display' tab.
    $this->adminUser2 = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node form display',
    ]);
    $this->drupalLogin($this->adminUser2);

    // Check that the weight input field is displayed when the content type is
    // enabled for scheduling. This field still exists even with tabledrag on.
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/form-display');
    $this->assertSession()->fieldExists('edit-fields-scheduler-repeat-weight');

    // Disabling scheduled publishing and unpublishing has no effect on whether
    // the base fields are displayed in the form. This is the same for the main
    // Scheduler fields.
  }

}
