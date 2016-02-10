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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
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

}
