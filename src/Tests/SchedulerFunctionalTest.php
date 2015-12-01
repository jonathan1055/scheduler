<?php
/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerFunctionalTest.
 */

namespace Drupal\scheduler\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType; ### @TODO Only added for NodeType::load() is there a better way?
use Drupal\node\NodeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the user interface of the Scheduler module.
 *
 * @group scheduler
 */
class SchedulerFunctionalTest extends SchedulerTestBase {

  /**
   * The modules to be loaded for these tests.
   */
  public static $modules = array('node', 'scheduler');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $config = $this->config('scheduler.settings');

    // Create a 'Basic Page' content type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => t('Basic page')));
    ### @TODO the string 'page' is hard-coded eleven times in this file (so far)
    ### @TODO Could make it a variable, which would allow future testing of other entity types?

    // Add scheduler functionality to the page node type.
    $node_type = NodeType::load('page'); ### @TODO Is this the correct/best way?
    $node_type->setThirdPartySetting('scheduler', 'publish_enable', TRUE);
    $node_type->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE);

    // Create an administrator user.
    $this->adminUser = $this->drupalCreateUser(array(
      'access content',
      'administer scheduler',
      'create page content',
      'edit own page content',
      'delete own page content',
      'view own unpublished content',
      'administer nodes',
      'schedule (un)publishing of nodes',
    ));
  }

  /**
   * Tests basic scheduling of content.
   */
  public function testScheduler() {
    // Create node values. Set time to one hour in the future.
    $edit = array(
      'title[0][value]' => $this->randomMachineName(10),
      'publish_on[0][value][date]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'Y-m-d'),
      'publish_on[0][value][time]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'H:i:s'),
      'promote[value]' => 1,
    );
    $this->helpTestScheduler($edit);

    // Remove publish_on and set unpublish_on, then run basic tests again.
    $edit['unpublish_on[0][value][date]'] = $edit['publish_on[0][value][date]'];
    $edit['unpublish_on[0][value][time]'] = $edit['publish_on[0][value][time]'];
    unset($edit['publish_on[0][value][date]']);
    unset($edit['publish_on[0][value][time]']);
    // Need a new title for the new node, as we identify the node by title.
    $edit['title[0][value]'] = $this->randomMachineName(10);
    $this->helpTestScheduler($edit);
  }

  /**
   * Test the different options for past publication dates.
   */
  public function testSchedulerPastDates() {
    $config = $this->config('scheduler.settings');
    $node_storage = $this->container->get('entity.manager')->getStorage('node');

    // Log in.
    $this->drupalLogin($this->adminUser);

    // Create an unpublished page node.
    $node = $this->drupalCreateNode(array('type' => 'page', 'status' => FALSE));

    // Test the default behavior: an error message should be shown when the user
    // enters a publication date that is in the past.
    $edit = array(
      'title[0][value]' => t('Past') . ' ' . $this->randomString(10),
      'publish_on[0][value][date]' => \Drupal::service('date.formatter')->format(strtotime('-1 day'), 'custom', 'Y-m-d'), ### @TODO should use default date part from config, not hardcode
      'publish_on[0][value][time]' => \Drupal::service('date.formatter')->format(strtotime('-1 day'), 'custom', 'H:i:s'), ### @TODO should use default time part from config, not hardcode
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and publish'));
    $this->assertRaw(t("The 'publish on' date must be in the future"), 'An error message is shown when the publication date is in the past and the "error" behavior is chosen.');

    // Test the 'publish' behavior: the node should be published immediately.
    $entity = $node->type->entity;
    $entity->setThirdPartySetting('scheduler', 'publish_past_date', 'publish');
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and publish'));
    $this->assertNoRaw(t("The 'publish on' date must be in the future"), 'No error message is shown when the publication date is in the past and the "publish" behavior is chosen.');
    $this->assertRaw(t('@type %title has been updated.', array('@type' => t('Basic page'), '%title' => SafeMarkup::checkPlain($edit['title[0][value]']))), 'The node is saved successfully when the publication date is in the past and the "publish" behavior is chosen.');

    // Reload the changed node and check that it is published.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isPublished(), 'The node has been published immediately when the publication date is in the past and the "publish" behavior is chosen.');

    // Test the 'schedule' behavior: the node should be unpublished and become
    // published on the next cron run.
    $entity->setThirdPartySetting('scheduler', 'publish_past_date', 'schedule');
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and publish'));
    $publish_time = $edit['publish_on[0][value][date]'] . ' ' . $edit['publish_on[0][value][time]']; ### @TODO should use date format from config
    $this->assertNoRaw(t("The 'publish on' date must be in the future"), 'No error message is shown when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertRaw(t('@type %title has been updated.', array('@type' => t('Basic page'), '%title' => SafeMarkup::checkPlain($edit['title[0][value]']))), 'The node is saved successfully when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertRaw(t('This post is unpublished and will be published @publish_time.', array('@publish_time' => $publish_time)), 'The node is scheduled to be published when the publication date is in the past and the "schedule" behavior is chosen.');

    // Reload the node and check that it is unpublished but scheduled correctly.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->isPublished(), 'The node has been unpublished when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertEqual(\Drupal::service('date.formatter')->format($node->publish_on->value, 'custom', 'Y-m-d H:i:s'), $publish_time, 'The node is scheduled for the required date');

    // Simulate a cron run and check that the node is published.
    scheduler_cron();
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isPublished(), 'The node with publication date in the past and the "schedule" behavior has now been published by cron.');
  }

  /**
   * Tests the creation of new revisions on scheduling.
   */
  public function testRevisioning() {
    // Create a scheduled node that is not automatically revisioned.
    $created = strtotime('-2 day');
    $settings = array(
      'revision' => 0,
      'created' => $created,
    );
    $node = $this->drupalCreateNode($settings);

    // First test scheduled publication with revisioning disabled.
    $node = $this->schedule($node);
    $this->assertRevisionCount($node->id(), 1, 'No new revision was created when a node was published with revisioning disabled.');

    // Test scheduled unpublication.
    $node = $this->schedule($node, 'unpublish');
    $this->assertRevisionCount($node->id(), 1, 'No new revision was created when a node was unpublished with revisioning disabled.');

    // Enable revisioning.
    $entity = $node->type->entity;
    $entity->setThirdPartySetting('scheduler', 'publish_revision', TRUE);
    $entity->setThirdPartySetting('scheduler', 'unpublish_revision', TRUE);

    // Test scheduled publication with revisioning enabled.
    $node = $this->schedule($node);
    $this->assertRevisionCount($node->id(), 2, 'A new revision was created when revisioning is enabled.');
    $expected_message = t('Node published by Scheduler on @now. Previous creation date was @date.', array(
      '@now' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'),
      '@date' => \Drupal::service('date.formatter')->format($created, 'short'),
    ));
    $this->assertRevisionLogMessage($node->id(), $expected_message, 'The correct message was found in the node revision log after scheduled publishing.');

    // Test scheduled unpublication with revisioning enabled.
    $node = $this->schedule($node, 'unpublish');
    $this->assertRevisionCount($node->id(), 3, 'A new revision was created when a node was unpublished with revisioning enabled.');
    $expected_message = t('Node unpublished by Scheduler on @now. Previous change date was @date.', array(
      '@now' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'),
      '@date' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'),
    ));
    $this->assertRevisionLogMessage($node->id(), $expected_message, 'The correct message was found in the node revision log after scheduled unpublishing.');
  }

  /**
   * Tests if options can both be displayed as extra fields and vertical tabs.
   */
  public function testExtraFields() {
    $node_type = NodeType::load('page');
    $this->drupalLogin($this->adminUser);

    // Test if the options are shown as vertical tabs by default.
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->xpath('//div[contains(@class, "vertical-tabs-panes")]/fieldset[@id = "edit-scheduler-settings"]'), 'By default the scheduler options are shown as a vertical tab.');

    // Test if the options are shown as extra fields when configured to do so.
    $node_type->setThirdPartySetting('scheduler', 'use_vertical_tabs', FALSE);
    $this->drupalGet('node/add/page');
    $this->assertFalse($this->xpath('//div[contains(@class, "vertical-tabs-panes")]/fieldset[@id = "edit-scheduler-settings"]'), 'The scheduler options are not shown as a vertical tab when they are configured to show as an extra field.');
    $this->assertTrue($this->xpath('//fieldset[@id = "edit-scheduler-settings" and contains(@class, "collapsed")]'), 'The scheduler options are shown as a collapsed fieldset when they are configured to show as an extra field.');

    // Test the option to expand the fieldset.
    $node_type->setThirdPartySetting('scheduler', 'expand_fieldset', TRUE);
    $this->drupalGet('node/add/page');
    $this->assertFalse($this->xpath('//div[contains(@class, "vertical-tabs-panes")]/fieldset[@id = "edit-scheduler-settings"]'), 'The scheduler options are not shown as a vertical tab when they are configured to show as an expanded fieldset.');
    $this->assertTrue($this->xpath('//fieldset[@id = "edit-scheduler-settings" and not(contains(@class, "collapsed"))]'), 'The scheduler options are shown as an expanded fieldset.');
  }

  /**
   * Tests creating and editing nodes with required scheduling enabled.
   */
  public function testRequiredScheduling() {
    $config = $this->config('scheduler.settings');
    $this->drupalLogin($this->adminUser);

    // Define test scenarios with expected results.
    $test_cases = array(
      // The 1-10 numbering used below matches the test cases described in
      // http://drupal.org/node/1198788#comment-7816119
      //
      // A. Test scenarios that require scheduled publishing.
      // When creating a new unpublished node it is required to enter a
      // publication date.
      array(
        'id' => 1,
        'required' => 'publish',
        'operation' => 'add',
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled publishing is required and a new unpublished node is created, entering a date in the publish on field is required.',
      ),

      // When creating a new published node it is required to enter a
      // publication date. The node will be unpublished on form submit.
      array(
        'id' => 2,
        'required' => 'publish',
        'operation' => 'add',
        'status' => 1,
        'expected' => 'required',
        'message' => 'When scheduled publishing is required and a new published node is created, entering a date in the publish on field is required.',
      ),

      // When editing a published node it is not needed to enter a publication
      // date since the node is already published.
      array(
        'id' => 3,
        'required' => 'publish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 1,
        'expected' => 'not required',
        'message' => 'When scheduled publishing is required and an existing published, unscheduled node is edited, entering a date in the publish on field is not required.',
      ),

      // When editing an unpublished node that is scheduled for publication it
      // is required to enter a publication date.
      array(
        'id' => 4,
        'required' => 'publish',
        'operation' => 'edit',
        'scheduled' => 1,
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled publishing is required and an existing unpublished, scheduled node is edited, entering a date in the publish on field is required.',
      ),

      // When editing an unpublished node that is not scheduled for publication
      // it is not required to enter a publication date since this means that
      // the node has already gone through a publication > unpublication cycle.
      array(
        'id' => 5,
        'required' => 'publish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 0,
        'expected' => 'not required',
        'message' => 'When scheduled publishing is required and an existing unpublished, unscheduled node is edited, entering a date in the publish on field is not required.',
      ),

      // B. Test scenarios that require scheduled unpublishing.
      // When creating a new unpublished node it is required to enter an
      // unpublication date since it is to be expected that the node will be
      // published at some point and should subsequently be unpublished.
      array(
        'id' => 6,
        'required' => 'unpublish',
        'operation' => 'add',
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and a new unpublished node is created, entering a date in the unpublish on field is required.',
      ),

      // When creating a new published node it is required to enter an
      // unpublication date.
      array(
        'id' => 7,
        'required' => 'unpublish',
        'operation' => 'add',
        'status' => 1,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and a new published node is created, entering a date in the unpublish on field is required.',
      ),

      // When editing a published node it is required to enter an unpublication
      // date.
      array(
        'id' => 8,
        'required' => 'unpublish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 1,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and an existing published, unscheduled node is edited, entering a date in the unpublish on field is required.',
      ),

      // When editing an unpublished node that is scheduled for publication it
      // it is required to enter an unpublication date.
      array(
        'id' => 9,
        'required' => 'unpublish',
        'operation' => 'edit',
        'scheduled' => 1,
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and an existing unpublished, scheduled node is edited, entering a date in the unpublish on field is required.',
      ),

      // When editing an unpublished node that is not scheduled for publication
      // it is not required to enter an unpublication date since this means that
      // the node has already gone through a publication - unpublication cycle.
      array(
        'id' => 10,
        'required' => 'unpublish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 0,
        'expected' => 'not required',
        'message' => 'When scheduled unpublishing is required and an existing unpublished, unscheduled node is edited, entering a date in the unpublish on field is not required.',
      ),
    );

    $node_type = NodeType::load('page');
    foreach ($test_cases as $key => $test_case) {
      // Enable required (un)publishing as stipulated by the test case.
      $node_type->setThirdPartySetting('scheduler', 'publish_required', $test_case['required'] == 'publish');
      $node_type->setThirdPartySetting('scheduler', 'unpublish_required', $test_case['required'] == 'unpublish');


      // Set the default node status, used when creating a new node.
      $node_options_page = !empty($test_case['status']) ? array('status' => TRUE) : array(); // existing.
      $node_options_page = array('status' => $test_case['status']);
      $config->set('node_options_page', $node_options_page); ### @TODO check this. It does not look right.
      $existing_options = $node_type->getThirdPartySetting('node', 'options', 'nothing');
      debug($existing_options, $key . ' $existing_options');
      $node_type->setThirdPartySetting('node', 'options', $node_options_page); ### @TODO but this is only my guess! Does not affect the results.
      $updated_options = $node_type->getThirdPartySetting('node', 'options', 'nothing');
      debug($updated_options, $key . ' $updated_options');

      // To assist viewing and analysing the generated test result pages create
      // a text string showing all the test case parameters.
      $title_data = array();
      foreach ($test_case as $key => $value) {
        if ($key != 'message') {
          $title_data[] = $key . ' = ' . $value;
        }
      }
      $title = implode(', ', $title_data);

      // If the test case requires editing a node, we need to create one first.
      if ($test_case['operation'] == 'edit') {
        $options = array(
          'title[0][value]' => $title,
          'type' => 'page',
          'status' => $test_case['status'],
           ### @TODO should use default date part from config, not hardcode
          'publish_on[0][value][date]' => !empty($test_case['scheduled']) ? \Drupal::service('date.formatter')->format(strtotime('+1 day'), 'custom', 'Y-m-d') : NULL,
          'publish_on[0][value][time]' => !empty($test_case['scheduled']) ? \Drupal::service('date.formatter')->format(strtotime('+1 day'), 'custom', 'H-i-s') : NULL,
        );
//        debug($options, 'creating node with $options');
        $node = $this->drupalCreateNode($options);
      }

      // Make sure the publication date fields are empty so we can check if they
      // throw form validation errors when they are required.
      $edit = array(
        'title[0][value]' => $title,
        'publish_on[0][value][date]' => '',
        'publish_on[0][value][time]' => '',
        'unpublish_on[0][value][date]' => '',
        'unpublish_on[0][value][time]' => '',
      );
      $path = $test_case['operation'] == 'add' ? 'node/add/page' : 'node/' . $node->id() . '/edit';
//      debug($path, '$path');
      if ($test_case['operation'] == 'edit') debug($node->status->value, 'Editing ' . $node->id() . ' $node->status->value');
      $button_text = $test_case['operation'] == 'add' ? t('Save and publish') : ($node->status->value ? t('Save and keep published') : t('Save and keep unpublished'));
//      debug($button_text, '$button_text'); // big translation object
      $this->drupalPostForm($path, $edit, $button_text);

      // Check for the expected result.
      switch ($test_case['expected']) {
        case 'required':
          $string = t('%name field is required.', array('%name' => ucfirst($test_case['required']) . ' on'));
          $this->assertRaw($string, $test_case['id'] . '. ' . $test_case['message']);
          break;

        case 'not required':
          $string = '@type %title has been ' . ($test_case['operation'] == 'add' ? 'created' : 'updated') . '.';
          $args = array('@type' => 'Basic page', '%title' => $title);
          // @codingStandardsIgnoreStart
          $this->assertRaw(t($string, $args), $test_case['id'] . '. ' . $test_case['message']);
          // @codingStandardsIgnoreEnd
          break;
      }
    }
  }

  /**
   * Tests the validation when editing a node.
   */
  public function testValidationDuringEdit() {
    $this->drupalLogin($this->adminUser);

    // Create an unpublished page node.
    $settings = array(
      'type' => 'page',
      'status' => FALSE,
      'title[0][value]' => $this->randomString(15),
    );
    $node = $this->drupalCreateNode($settings);

    // Set unpublishing to be required.
    $node->type->entity->setThirdPartySetting('scheduler', 'unpublish_required', TRUE);

    // Edit the node and check the validation.
    $edit = array(
      'publish_on[0][value][date]' => date('Y-m-d', strtotime('+1 day', REQUEST_TIME)), ### @TODO should we get the default format? not hard-code.
      'publish_on[0][value][time]' => date('H:i:s', strtotime('+1 day', REQUEST_TIME)),
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep unpublished'));
    $this->assertRaw(t("If you set a 'publish-on' date then you must also set an 'unpublish-on' date."), 'Validation prevents entering a publish-on date with no unpublish-on date if unpublishing is required.');
  }

  /**
   * Tests the deletion of a scheduled node.
   *
   * This tests if it is possible to delete a node that does not have a
   * publication date set, when scheduled publishing is required.
   *
   * @see https://drupal.org/node/1614880
   */
  public function testScheduledNodeDelete() {
    // Log in.
    $this->drupalLogin($this->adminUser);

    // Create a published and an unpublished node, both without scheduling.
    $unpublished_node = $this->drupalCreateNode(array('type' => 'page', 'status' => 0));
    $published_node = $this->drupalCreateNode(array('type' => 'page', 'status' => 1));

    // Make scheduled publishing and unpublishing required.
    $node_type = NodeType::load('page');
    $node_type->setThirdPartySetting('scheduler', 'publish_required', TRUE);
    $node_type->setThirdPartySetting('scheduler', 'unpublish_required', TRUE);

    // Check that deleting the nodes does not throw form validation errors.
    ### @TODO Old: $this->drupalPostForm('node/' . $published_node->id() . '/edit', array(), t('Delete'));
    ### @TODO Delete is not a button but a separate link node/<nid>/delete.
    ### Is the previous validation (that we had to avoid on delete) still done now in D8, given that there is no form?
    ### Maybe this test is not actually checking anything useful? Can it be altered to do something testable?
    $this->drupalGet('node/' . $published_node->id() . '/delete');
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete a published node with no scheduling information.');

    $this->drupalGet('node/' . $unpublished_node->id() . '/delete');
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete an unpublished node with no scheduling information.');
  }

   /**
   * Tests that a non-enabled node type cannot be scheduled.
   *
   * This checks that the scheduler input date/time fields are not displayed
   * if the node type has not been enabled for scheduling.
   */
  public function testNonEnabledNodeType() {
    // Create a 'Not for scheduler' content type.
    $name = 'not_for_scheduler';
    $this->drupalCreateContentType(array('type' => $name, 'name' => t('Not-for-Scheduler')));
    $node_type = NodeType::load($name);

    // Create an administrator user.
    ### @TODO There should be a way to add the new permissions to the existing
    ### adminUser instead of creating a new user?
    $this->adminUser2 = $this->drupalCreateUser(array(
      'access content',
      'administer scheduler',
      'create ' . $name . ' content',
      'edit own ' . $name . ' content',
      'delete own ' . $name . ' content',
      'view own unpublished content',
      'administer nodes',
      'schedule (un)publishing of nodes',
    ));

    // Log in.
    $this->drupalLogin($this->adminUser2);

    // By default check that the scheduler options are not enabled.
    $this->drupalGet('node/add/' . $name);
    $this->assertNoFieldByName('publish_on[0][value][date]', '', 'The Publish-on field is not shown by default when the content type is not enabled for Scheduler.');
    $this->assertNoFieldByName('unpublish_on[0][value][date]', '', 'The Unpublish-on field is not shown by default when the content type is not enabled for Scheduler.');

    // Explicitly disable this content type for scheduler, and test again.
    $node_type->setThirdPartySetting('scheduler', 'publish_enable', FALSE);
    $node_type->setThirdPartySetting('scheduler', 'unpublish_enable', FALSE);

    $this->drupalGet('node/add/' . $name);
    $this->assertNoFieldByName('publish_on[0][value][date]', '', 'The Publish-on field is not shown after setting the content type to not enabled for Scheduler.');
    $this->assertNoFieldByName('unpublish_on[0][value][date]', '', 'The Unpublish-on field is not shown after setting the content type to not enabled for Scheduler.');

    // Attempt to create a node with a scheduled publishing date in the future.
    $body = $this->randomMachineName(30);
    $edit = array(
      'title[0][value]' => $this->randomMachineName(10),
      'publish_on[0][value][date]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'Y-m-d'),
      'publish_on[0][value][time]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'H:i:s'),
      'unpublish_on[0][value][date]' => \Drupal::service('date.formatter')->format(time() + 7200, 'custom', 'Y-m-d'),
      'unpublish_on[0][value][time]' => \Drupal::service('date.formatter')->format(time() + 7200, 'custom', 'H:i:s'),
      'promote[value]' => 1,
      'body[0][value]' => $body,
    );

    $this->drupalPostForm('node/add/' . $name, $edit, t('Save and publish'));
    // Show the site front page for an anonymous visitor, then assert that the
    // node is correctly published.
    $this->drupalLogout();
    $this->drupalGet('node');
    $this->assertText($body, t('The %name node is not scheduled and is published immediately.', array('%name' => $name)));

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Check that no data has been saved for the scheduler fields.
    $field_data = db_select('node_field_data', 'f')
      ->fields('f')
      ->condition('nid', $node->id())
      ->execute()
      ->fetchAll();
    $this->assertNull($field_data[0]->publish_on, t('There is no publish_on date stored for this node in node_field_data.'));
    $this->assertNull($field_data[0]->unpublish_on, t('There is no unpublish_on date stored for this node in node_field_data.'));
  }
}
