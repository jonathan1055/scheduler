<?php

/**
 * @file
 * Contains
 *   \Drupal\scheduler\Tests\SchedulerTestBase
 *   \Drupal\scheduler\Tests\SchedulerFunctionalTest
 *   \Drupal\scheduler\Tests\ScedulerDateCombineFunctionalTest
 */

namespace Drupal\scheduler\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the scheduler interface.
 */
class SchedulerFunctionalTest extends SchedulerTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Scheduler functionality',
      'description' => 'Publish/unpublish on time.',
      'group' => 'Scheduler',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp('scheduler');

    // Create a 'Basic Page' content type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => t('Basic page')));

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

    // Add scheduler functionality to the page node type.
    variable_set('scheduler_publish_enable_page', 1);
    variable_set('scheduler_unpublish_enable_page', 1);
  }

  /**
   * Tests basic scheduling of content.
   */
  public function testScheduler() {
    // Create node values. Set time to one hour in the future.
    $edit = array(
      'title' => 'title',
      'publish_on' => format_date(time() + 3600, 'custom', 'Y-m-d H:i:s'),
      'status' => 1,
      'promote' => 1,
    );
    $this->helpTestScheduler($edit);
    $edit['unpublish_on'] = $edit['publish_on'];
    unset($edit['publish_on']);
    $this->helpTestScheduler($edit);
  }

  /**
   * Test the different options for past publication dates.
   */
  public function testSchedulerPastDates() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');

    // Log in.
    $this->drupalLogin($this->adminUser);

    // Create an unpublished page node.
    $node = $this->drupalCreateNode(array('type' => 'page', 'status' => FALSE));

    // Test the default behavior: an error message should be shown when the user
    // enters a publication date that is in the past.
    $edit = array(
      'title' => $this->randomName(),
      'publish_on' => format_date(strtotime('-1 day'), 'custom', 'Y-m-d H:i:s'),
    );
    $this->drupalPost('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertRaw(t("The 'publish on' date must be in the future"), 'An error message is shown when the publication date is in the past and the "error" behavior is chosen.');

    // Test the 'publish' behavior: the node should be published immediately.
    variable_set('scheduler_publish_past_date_page', 'publish');
    $this->drupalPost('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertNoRaw(t("The 'publish on' date must be in the future"), 'No error message is shown when the publication date is in the past and the "publish" behavior is chosen.');
    $this->assertRaw(t('@type %title has been updated.', array('@type' => t('Basic page'), '%title' => SafeMarkup::checkPlain($edit['title']))), 'The node is saved successfully when the publication date is in the past and the "publish" behavior is chosen.');

    // Reload the changed node and check that it is published.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isPublished(), 'The node has been published immediately when the publication date is in the past and the "publish" behavior is chosen.');

    // Test the 'schedule' behavior: the node should be unpublished and become
    // published on the next cron run.
    variable_set('scheduler_publish_past_date_page', 'schedule');
    $this->drupalPost('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertNoRaw(t("The 'publish on' date must be in the future"), 'No error message is shown when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertRaw(t('@type %title has been updated.', array('@type' => t('Basic page'), '%title' => SafeMarkup::checkPlain($edit['title']))), 'The node is saved successfully when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertRaw(t('This post is unpublished and will be published @publish_time.', array('@publish_time' => $edit['publish_on'])), 'The node is scheduled to be published when the publication date is in the past and the "schedule" behavior is chosen.');

    // Reload the node and check that it is unpublished but scheduled correctly.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->isPublished(), 'The node has been unpublished when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertEqual(format_date($node->publish_on->value, 'custom', 'Y-m-d H:i:s'), $edit['publish_on'], 'The node is scheduled for the required date');

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
    variable_set('scheduler_publish_revision_page', 1);
    variable_set('scheduler_unpublish_revision_page', 1);

    // Test scheduled publication with revisioning enabled.
    $node = $this->schedule($node);
    $this->assertRevisionCount($node->id(), 2, 'A new revision was created when revisioning is enabled.');
    $expected_message = t('Node published by Scheduler on @now. Previous creation date was @date.', array(
      '@now' => format_date(REQUEST_TIME, 'short'),
      '@date' => format_date($created, 'short'),
    ));
    $this->assertRevisionLogMessage($node->id(), $expected_message, 'The correct message was found in the node revision log after scheduled publishing.');

    // Test scheduled unpublication with revisioning enabled.
    $node = $this->schedule($node, 'unpublish');
    $this->assertRevisionCount($node->id(), 3, 'A new revision was created when a node was unpublished with revisioning enabled.');
    $expected_message = t('Node unpublished by Scheduler on @now. Previous change date was @date.', array(
      '@now' => format_date(REQUEST_TIME, 'short'),
      '@date' => format_date(REQUEST_TIME, 'short'),
    ));
    $this->assertRevisionLogMessage($node->id(), $expected_message, 'The correct message was found in the node revision log after scheduled unpublishing.');
  }

  /**
   * Tests if options can both be displayed as extra fields and vertical tabs.
   */
  public function testExtraFields() {
    $this->drupalLogin($this->adminUser);

    // Test if the options are shown as vertical tabs by default.
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->xpath('//div[contains(@class, "vertical-tabs-panes")]/fieldset[@id = "edit-scheduler-settings"]'), 'By default the scheduler options are shown as a vertical tab.');

    // Test if the options are shown as extra fields when configured to do so.
    variable_set('scheduler_use_vertical_tabs_page', 0);
    $this->drupalGet('node/add/page');
    $this->assertFalse($this->xpath('//div[contains(@class, "vertical-tabs-panes")]/fieldset[@id = "edit-scheduler-settings"]'), 'The scheduler options are not shown as a vertical tab when they are configured to show as an extra field.');
    $this->assertTrue($this->xpath('//fieldset[@id = "edit-scheduler-settings" and contains(@class, "collapsed")]'), 'The scheduler options are shown as a collapsed fieldset when they are configured to show as an extra field.');

    // Test the option to expand the fieldset.
    variable_set('scheduler_expand_fieldset_page', 1);
    $this->drupalGet('node/add/page');
    $this->assertFalse($this->xpath('//div[contains(@class, "vertical-tabs-panes")]/fieldset[@id = "edit-scheduler-settings"]'), 'The scheduler options are not shown as a vertical tab when they are configured to show as an expanded fieldset.');
    $this->assertTrue($this->xpath('//fieldset[@id = "edit-scheduler-settings" and not(contains(@class, "collapsed"))]'), 'The scheduler options are shown as an expanded fieldset.');
  }

  /**
   * Tests creating and editing nodes with required scheduling enabled.
   */
  public function testRequiredScheduling() {
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

    foreach ($test_cases as $test_case) {
      // Enable required (un)publishing as stipulated by the test case.
      variable_set('scheduler_publish_required_page', $test_case['required'] == 'publish');
      variable_set('scheduler_unpublish_required_page', $test_case['required'] == 'unpublish');

      // Set the default node status, used when creating a new node.
      $node_options_page = !empty($test_case['status']) ? array('status') : array();
      variable_set('node_options_page', $node_options_page);

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
          'title' => $title,
          'type' => 'page',
          'status' => $test_case['status'],
          'publish_on' => !empty($test_case['scheduled']) ? strtotime('+ 1 day') : 0,
        );
        $node = $this->drupalCreateNode($options);
      }

      // Make sure the publication date fields are empty so we can check if they
      // throw form validation errors when they are required.
      $edit = array(
        'title' => $title,
        'publish_on' => '',
        'unpublish_on' => '',
      );
      $path = $test_case['operation'] == 'add' ? 'node/add/page' : 'node/' . $node->id() . '/edit';
      $this->drupalPost($path, $edit, t('Save'));

      // Check for the expected result.
      switch ($test_case['expected']) {
        case 'required':
          $string = t('!name field is required.', array('!name' => ucfirst($test_case['required']) . ' on'));
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
      'title' => $this->randomName(),
    ));
    $node = $this->drupalCreateNode($settings);

    // Set unpublishing to be required.
    variable_set('scheduler_unpublish_required_page', TRUE);

    // Edit the node and check the validation.
    $edit = array(
      'publish_on' => date('Y-m-d H:i:s', strtotime('+1 day', REQUEST_TIME)),
    );
    $this->drupalPost('node/' . $node->id() . '/edit', $edit, t('Save'));
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
    variable_set('scheduler_publish_required_page', TRUE);
    variable_set('scheduler_unpublish_required_page', TRUE);

    // Check that deleting the nodes does not throw form validation errors.
    $this->drupalPost('node/' . $published_node->id() . '/edit', array(), t('Delete'));
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete a published node with no scheduling information.');

    $this->drupalPost('node/' . $unpublished_node->id() . '/edit', array(), t('Delete'));
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete an unpublished node with no scheduling information.');
  }

}
