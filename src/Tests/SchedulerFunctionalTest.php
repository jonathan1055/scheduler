<?php
/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerFunctionalTest.
 */

namespace Drupal\scheduler\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
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
  public static $modules = ['scheduler'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // @todo The $config variable is currently unused.
    $config = $this->config('scheduler.settings');

    // Create a 'Basic Page' content type.
    /** @var NodeTypeInterface $node_type */
    $this->nodetype = $this->drupalCreateContentType(['type' => 'page', 'name' => t('Basic page')]);
    ### @TODO Remove all NodeType::load('page') and use $this->nodetype
    ### @TODO Remove all 'page' and use $this->nodetype->get('type')
    ### @TODO Remove all 'Basic page' and use $this->nodetype->get('name')

    // Add scheduler functionality to the node type.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Create an administrator user.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer scheduler',
      'create page content',
      'edit own page content',
      'delete own page content',
      'view own unpublished content',
      'administer nodes',
      'schedule publishing of nodes',
    ]);
  }

  /**
   * Tests basic scheduling of content.
   */
  public function testScheduler() {
    // Login to admin user. This is required here before creating the publish_on
    // date and time values so that date.formatter can utilise the current users
    // timezone. The constraints receive values which have been converted using
    // the users timezone so they need to be consistent.
    $this->drupalLogin($this->adminUser);

    // Create node values. Set time to one hour in the future.
    $edit = [
      'title[0][value]' => $this->randomMachineName(10),
      'publish_on[0][value][date]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'Y-m-d'),
      'publish_on[0][value][time]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'H:i:s'),
      'promote[value]' => 1,
    ];
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
    // @todo The $config variable is currently unused.
    $config = $this->config('scheduler.settings');
    /** @var EntityStorageInterface $node_storage */
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Log in.
    $this->drupalLogin($this->adminUser);

    // Create an unpublished page node.
    $node = $this->drupalCreateNode(['type' => 'page', 'status' => FALSE]);

    // Test the default behavior: an error message should be shown when the user
    // enters a publication date that is in the past.
    $edit = [
      'title[0][value]' => t('Past') . ' ' . $this->randomString(10),
      'publish_on[0][value][date]' => \Drupal::service('date.formatter')->format(strtotime('-1 day'), 'custom', 'Y-m-d'), ### @TODO should use default date part from config, not hardcode
      'publish_on[0][value][time]' => \Drupal::service('date.formatter')->format(strtotime('-1 day'), 'custom', 'H:i:s'), ### @TODO should use default time part from config, not hardcode
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and publish'));
    $this->assertRaw(t("The 'publish on' date must be in the future"), 'An error message is shown when the publication date is in the past and the "error" behavior is chosen.');

    // Test the 'publish' behavior: the node should be published immediately.
    /** @var NodeTypeInterface $entity */
    $entity = $node->type->entity;
    $entity->setThirdPartySetting('scheduler', 'publish_past_date', 'publish')->save();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and publish'));
    $this->assertNoRaw(t("The 'publish on' date must be in the future"), 'No error message is shown when the publication date is in the past and the "publish" behavior is chosen.');
    $this->assertRaw(t('@type %title has been updated.', ['@type' => t('Basic page'), '%title' => SafeMarkup::checkPlain($edit['title[0][value]'])]), 'The node is saved successfully when the publication date is in the past and the "publish" behavior is chosen.');

    // Reload the changed node and check that it is published.
    $node_storage->resetCache([$node->id()]);

    /** @var NodeInterface $node */
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isPublished(), 'The node has been published immediately when the publication date is in the past and the "publish" behavior is chosen.');

    // Test the 'schedule' behavior: the node should be unpublished and become
    // published on the next cron run.
    $entity->setThirdPartySetting('scheduler', 'publish_past_date', 'schedule')->save();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $publish_time = $edit['publish_on[0][value][date]'] . ' ' . $edit['publish_on[0][value][time]']; ### @TODO should use date format from config
    $this->assertNoRaw(t("The 'publish on' date must be in the future"), 'No error message is shown when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertRaw(t('@type %title has been updated.', ['@type' => t('Basic page'), '%title' => SafeMarkup::checkPlain($edit['title[0][value]'])]), 'The node is saved successfully when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertRaw(t('This post is unpublished and will be published @publish_time.', ['@publish_time' => $publish_time]), 'The node is scheduled to be published when the publication date is in the past and the "schedule" behavior is chosen.');

    // Reload the node and check that it is unpublished but scheduled correctly.
    $node_storage->resetCache([$node->id()]);
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->isPublished(), 'The node has been unpublished when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertEqual(\Drupal::service('date.formatter')->format($node->publish_on->value, 'custom', 'Y-m-d H:i:s'), $publish_time, 'The node is scheduled for the required date');

    // Simulate a cron run and check that the node is published.
    scheduler_cron();
    $node_storage->resetCache([$node->id()]);
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isPublished(), 'The node with publication date in the past and the "schedule" behavior has now been published by cron.');

    // Check that an Unpublish date in the past fails validation.
    $edit = [
      'title[0][value]' => t('Unpublish in the past') . ' ' . $this->randomString(10),
      'unpublish_on[0][value][date]' => \Drupal::service('date.formatter')->format(REQUEST_TIME - 3600, 'custom', 'Y-m-d'),
      'unpublish_on[0][value][time]' => \Drupal::service('date.formatter')->format(REQUEST_TIME - 3600, 'custom', 'H:i:s'),
    ];
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));
    $this->assertRaw(t("The 'unpublish on' date must be in the future"), 'An error message is shown when the unpublish date is in the past.');

  }

  /**
   * Tests the creation of new revisions on scheduling.
   */
  public function testRevisioning() {
    // Create a scheduled node that is not automatically revisioned.
    $created = strtotime('-2 day');
    $settings = [
      'revision' => 0,
      'created' => $created,
    ];
    $node = $this->drupalCreateNode($settings);

    // First test scheduled publication with revisioning disabled by default.
    $node = $this->schedule($node);
    $this->assertRevisionCount($node->id(), 1, 'No new revision was created when a node was published with revisioning disabled.');

    // Test scheduled unpublication.
    $node = $this->schedule($node, 'unpublish');
    $this->assertRevisionCount($node->id(), 1, 'No new revision was created when a node was unpublished with revisioning disabled.');

    // Enable revisioning.
    $entity = $node->type->entity;
    $entity->setThirdPartySetting('scheduler', 'publish_revision', TRUE);
    $entity->setThirdPartySetting('scheduler', 'unpublish_revision', TRUE);
    $entity->save();

    // Test scheduled publication with revisioning enabled.
    $node = $this->schedule($node);
    $this->assertRevisionCount($node->id(), 2, 'A new revision was created when revisioning is enabled.');
    $expected_message = t('Node published by Scheduler on @now. Previous creation date was @date.', [
      '@now' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'),
      '@date' => \Drupal::service('date.formatter')->format($created, 'short'),
    ]);
    $this->assertRevisionLogMessage($node->id(), $expected_message, 'The correct message was found in the node revision log after scheduled publishing.');

    // Test scheduled unpublication with revisioning enabled.
    $node = $this->schedule($node, 'unpublish');
    $this->assertRevisionCount($node->id(), 3, 'A new revision was created when a node was unpublished with revisioning enabled.');
    $expected_message = t('Node unpublished by Scheduler on @now. Previous change date was @date.', [
      '@now' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'),
      '@date' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'),
    ]);
    $this->assertRevisionLogMessage($node->id(), $expected_message, 'The correct message was found in the node revision log after scheduled unpublishing.');
  }

  /**
   * Tests date input is displayed as vertical tab or an expandable fieldset.
   */
  public function testFieldsDisplay() {
    /** @var NodeTypeInterface $node_type */
    $node_type = NodeType::load('page');
    $this->drupalLogin($this->adminUser);

    // Check that the dates are shown in a vertical tab by default.
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->xpath('//div[contains(@class, "form-type-vertical-tabs")]//details[@id = "edit-scheduler-settings"]'), 'By default the scheduler dates are shown in a vertical tab.');

    // Check that the dates are shown as a fieldset when configured to do so.
    $node_type->setThirdPartySetting('scheduler', 'fields_display_mode', 'fieldset')->save();
    $this->drupalGet('node/add/page');
    $this->assertFalse($this->xpath('//div[contains(@class, "form-type-vertical-tabs")]//details[@id = "edit-scheduler-settings"]'), 'The scheduler dates are not shown in a vertical tab when they are configured to show as a fieldset.');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and not(@open = "open")]'), 'The scheduler dates are shown in a collapsed fieldset when they are configured to show as a fieldset.');

    // Check that the fieldset is expanded if either of the scheduling dates
    // are required.
    $node_type->setThirdPartySetting('scheduler', 'publish_required', TRUE)->save();
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and @open = "open"]'), 'The scheduler dates are shown in an expanded fieldset when the publish-on date is required.');

    $node_type->setThirdPartySetting('scheduler', 'publish_required', FALSE)
              ->setThirdPartySetting('scheduler', 'unpublish_required', TRUE)->save();
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and @open = "open"]'), 'The scheduler dates are shown in an expanded fieldset when the unpublish-on date is required.');

    // Check that the fieldset is expanded if the 'always' option is set.
    $node_type->setThirdPartySetting('scheduler', 'publish_required', FALSE)
              ->setThirdPartySetting('scheduler', 'unpublish_required', FALSE)
              ->setThirdPartySetting('scheduler', 'expand_fieldset', 'always')->save();
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and @open = "open"]'), 'The scheduler dates are shown in an expanded fieldset when the option to always expand is turned on.');

    // Check that the fieldset is expanded if the node already has a publish-on
    // date. This requires editing an existing scheduled node.
    $node_type->setThirdPartySetting('scheduler', 'expand_fieldset', 'when_required')->save();
    $options = [
      'title' => 'Contains Publish-on date ' . $this->randomMachineName(10),
      'type' => 'page',
      'publish_on' => strtotime('+1 day'),
    ];
    $node = $this->drupalCreateNode($options);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and @open = "open"]'), 'The scheduler dates are shown in an expanded fieldset when a publish-on date already exists.');

    // Check that the fieldset is expanded if the node has an unpublish-on date.
    $options = [
      'title' => 'Contains Unpublish-on date ' . $this->randomMachineName(10),
      'type' => 'page',
      'unpublish_on' => strtotime('+1 day'),
    ];
    $node = $this->drupalCreateNode($options);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and @open = "open"]'), 'The scheduler dates are shown in an expanded fieldset when an unpublish-on date already exists.');
  }

  /**
   * Tests creating and editing nodes with required scheduling enabled.
   */
  public function testRequiredScheduling() {
    $this->drupalLogin($this->adminUser);

    // Define test scenarios with expected results.
    $test_cases = [
      // The 1-10 numbering used below matches the test cases described in
      // http://drupal.org/node/1198788#comment-7816119
      //
      // A. Test scenarios that require scheduled publishing.
      // When creating a new unpublished node it is required to enter a
      // publication date.
      [
        'id' => 1,
        'required' => 'publish',
        'operation' => 'add',
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled publishing is required and a new unpublished node is created, entering a date in the publish on field is required.',
      ],

      // When creating a new published node it is required to enter a
      // publication date. The node will be unpublished on form submit.
      [
        'id' => 2,
        'required' => 'publish',
        'operation' => 'add',
        'status' => 1,
        'expected' => 'required',
        'message' => 'When scheduled publishing is required and a new published node is created, entering a date in the publish on field is required.',
      ],

      // When editing a published node it is not needed to enter a publication
      // date since the node is already published.
      [
        'id' => 3,
        'required' => 'publish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 1,
        'expected' => 'not required',
        'message' => 'When scheduled publishing is required and an existing published, unscheduled node is edited, entering a date in the publish on field is not required.',
      ],

      // When editing an unpublished node that is scheduled for publication it
      // is required to enter a publication date.
      [
        'id' => 4,
        'required' => 'publish',
        'operation' => 'edit',
        'scheduled' => 1,
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled publishing is required and an existing unpublished, scheduled node is edited, entering a date in the publish on field is required.',
      ],

      // When editing an unpublished node that is not scheduled for publication
      // it is not required to enter a publication date since this means that
      // the node has already gone through a publication > unpublication cycle.
      [
        'id' => 5,
        'required' => 'publish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 0,
        'expected' => 'not required',
        'message' => 'When scheduled publishing is required and an existing unpublished, unscheduled node is edited, entering a date in the publish on field is not required.',
      ],

      // B. Test scenarios that require scheduled unpublishing.
      // When creating a new unpublished node it is required to enter an
      // unpublication date since it is to be expected that the node will be
      // published at some point and should subsequently be unpublished.
      [
        'id' => 6,
        'required' => 'unpublish',
        'operation' => 'add',
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and a new unpublished node is created, entering a date in the unpublish on field is required.',
      ],

      // When creating a new published node it is required to enter an
      // unpublication date.
      [
        'id' => 7,
        'required' => 'unpublish',
        'operation' => 'add',
        'status' => 1,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and a new published node is created, entering a date in the unpublish on field is required.',
      ],

      // When editing a published node it is required to enter an unpublication
      // date.
      [
        'id' => 8,
        'required' => 'unpublish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 1,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and an existing published, unscheduled node is edited, entering a date in the unpublish on field is required.',
      ],

      // When editing an unpublished node that is scheduled for publication it
      // it is required to enter an unpublication date.
      [
        'id' => 9,
        'required' => 'unpublish',
        'operation' => 'edit',
        'scheduled' => 1,
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and an existing unpublished, scheduled node is edited, entering a date in the unpublish on field is required.',
      ],

      // When editing an unpublished node that is not scheduled for publication
      // it is not required to enter an unpublication date since this means that
      // the node has already gone through a publication - unpublication cycle.
      [
        'id' => 10,
        'required' => 'unpublish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 0,
        'expected' => 'not required',
        'message' => 'When scheduled unpublishing is required and an existing unpublished, unscheduled node is edited, entering a date in the unpublish on field is not required.',
      ],
    ];

    $node_type = NodeType::load('page');
    $fields = \Drupal::entityManager()->getFieldDefinitions('node', 'page');

    foreach ($test_cases as $test_case) {
      // Set required (un)publishing as stipulated by the test case.
      $node_type->setThirdPartySetting('scheduler', 'publish_required', $test_case['required'] == 'publish');
      $node_type->setThirdPartySetting('scheduler', 'unpublish_required', $test_case['required'] == 'unpublish');
      $node_type->save();

      // To assist viewing and analysing the generated test result pages create
      // a text string showing all the test case parameters.
      $title_data = [];
      foreach ($test_case as $key => $value) {
        if ($key != 'message') {
          $title_data[] = $key . ' = ' . $value;
        }
      }
      $title = implode(', ', $title_data);

      // If the test case requires editing a node, we need to create one first.
      if ($test_case['operation'] == 'edit') {
        // Note: The key names in the $options parameter for drupalCreateNode()
        // are the plain field names i.e. 'title' not title[0][value]
        $options = [
          'title' => $title,
          'type' => 'page',
          'status' => $test_case['status'],
          'publish_on' => !empty($test_case['scheduled']) ? strtotime('+1 day') : NULL,
        ];
        $node = $this->drupalCreateNode($options);
        // Define the path and button to use for editing the node.
        $path = 'node/' . $node->id() . '/edit';
        $button_text = $node->status->value ? t('Save and keep published') : t('Save and keep unpublished');
      }
      else {
        // Set the default status, used when testing creation of the new node.
        $fields['status']->getConfig('page')
          ->setDefaultValue($test_case['status'])
          ->save();
        // Define the path and button to use for creating the node.
        $path = 'node/add/page';
        $button_text = t('Save and publish');
      }

      // Make sure that both date fields are empty so we can check if they throw
      // validation errors when the fields are required.
      $edit = [
        'title[0][value]' => $title,
        'publish_on[0][value][date]' => '',
        'publish_on[0][value][time]' => '',
        'unpublish_on[0][value][date]' => '',
        'unpublish_on[0][value][time]' => '',
      ];
      $this->drupalPostForm($path, $edit, $button_text);

      // Check for the expected result.
      switch ($test_case['expected']) {
        case 'required':
          $string = t('The %name date is required.', ['%name' => ucfirst($test_case['required']) . ' on']);
          $this->assertRaw($string, $test_case['id'] . '. ' . $test_case['message']);
          break;

        case 'not required':
          $string = '@type %title has been ' . ($test_case['operation'] == 'add' ? 'created' : 'updated') . '.';
          $args = ['@type' => 'Basic page', '%title' => $title];
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
    $published_node = $this->drupalCreateNode(['type' => 'page', 'status' => 1]);
    $unpublished_node = $this->drupalCreateNode(['type' => 'page', 'status' => 0]);

    // Make scheduled publishing and unpublishing required.
    $node_type = NodeType::load('page');
    $node_type->setThirdPartySetting('scheduler', 'publish_required', TRUE);
    $node_type->setThirdPartySetting('scheduler', 'unpublish_required', TRUE);
    $node_type->save();

    // Check that deleting the nodes does not throw form validation errors.
    ### @TODO Delete was a button in 7.x but a separate link node/<nid>/delete in 8.x
    ### Is the previous validation (that we had to avoid on delete) still done now in D8, given that there is no form?
    ### Maybe this test is not actually checking anything useful? Can it be altered to do something testable?
    $this->drupalGet('node/' . $published_node->id() . '/delete');
    // Note that the text 'error message' is used in a header h2 html tag which
    // is normally made hidden from browsers but will be in the page source.
    // It is also good when testing for the absense of somthing to also test
    // for the presence of text, hence the second assertion for each check.
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete a published node with no scheduling information.');
    $this->assertRaw(t('Are you sure you want to delete the content'), 'The deletion warning message is shown immediately when trying to delete a published node with no scheduling information.');

    $this->drupalGet('node/' . $unpublished_node->id() . '/delete');
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete an unpublished node with no scheduling information.');
    $this->assertRaw(t('Are you sure you want to delete the content'), 'The deletion warning message is shown immediately when trying to delete an unpublished node with no scheduling information.');
  }

  /**
   * Tests that users without permission do not see the scheduler date fields.
   */
  public function testUserPermissions() {
    // Create a user who can add the 'page' content type but who does not have
    // the permission to use the scheduler functionality.
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'create ' . $this->nodetype->get('type') . ' content',
      'edit own ' . $this->nodetype->get('type') . ' content',
      'delete own ' . $this->nodetype->get('type') . ' content',
      'view own unpublished content',
    ]);
    $this->drupalLogin($this->webUser);

    // Set publishing and unpublishing to required, to make it a stronger test.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', TRUE)
      ->save();

    // Check that neither of the fields are displayed when creating a node.
    $this->drupalGet('node/add/page');
    $this->assertNoFieldByName('publish_on[0][value][date]', '', 'The Publish-on field is not shown for users who do not have permission to schedule content');
    $this->assertNoFieldByName('unpublish_on[0][value][date]', '', 'The Unpublish-on field is not shown for users who do not have permission to schedule content');

    // Check that the new node can be created and saved.
    $title = $this->randomString(15);
    $this->drupalPostForm('node/add/page', ['title[0][value]' => $title], t('Save'));
    $this->assertRaw(t('@type %title has been created.', array('@type' => $this->nodetype->get('name'), '%title' => $title)), 'The node was created and saved when the user does not have scheduler permissions.');
  }
}
