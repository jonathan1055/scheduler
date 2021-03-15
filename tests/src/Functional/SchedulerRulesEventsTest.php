<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\rules\Context\ContextConfig;

/**
 * Tests the six events that Scheduler provides for use in Rules module.
 *
 * phpcs:set Drupal.Arrays.Array lineLimit 140
 *
 * @group scheduler
 */
class SchedulerRulesEventsTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   */
  protected static $modules = ['scheduler_rules_integration'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->rulesStorage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');
    $this->expressionManager = $this->container->get('plugin.manager.rules_expression');

    // Create a reaction rule for each of the events that Scheduler triggers.
    // These rules are all active throughout all of the tests, which makes the
    // tests stronger, because it will show not only that the correct events are
    // triggered in the right places, but also that they are not triggered in
    // the wrong places.
    $rule_data = [
      // The first six events are the originals, only dispatched for Nodes.
      1 => ['scheduler_new_node_is_scheduled_for_publishing_event', 'A new node is created and is scheduled for publishing.'],
      2 => ['scheduler_existing_node_is_scheduled_for_publishing_event', 'An existing node is saved and is scheduled for publishing.'],
      3 => ['scheduler_has_published_this_node_event', 'Scheduler has published this node during cron.'],
      4 => ['scheduler_new_node_is_scheduled_for_unpublishing_event', 'A new node is created and is scheduled for unpublishing.'],
      5 => ['scheduler_existing_node_is_scheduled_for_unpublishing_event', 'An existing node is saved and is scheduled for unpublishing.'],
      6 => ['scheduler_has_unpublished_this_node_event', 'Scheduler has unpublished this node during cron.'],
      // The next events are dispatched only for Media entities.
      7 => ['scheduler:new_media_is_scheduled_for_publishing', 'A new media item is created and scheduled for publishing.'],
      8 => ['scheduler:existing_media_is_scheduled_for_publishing', 'An existing media item is saved and scheduled for publishing.'],
      9 => ['scheduler:media_has_been_published_via_cron', 'Scheduler has published this media item during cron.'],
      10 => ['scheduler:new_media_is_scheduled_for_unpublishing', 'A new media item is created and scheduled for unpublishing.'],
      11 => ['scheduler:existing_media_is_scheduled_for_unpublishing', 'An existing media item is saved and scheduled for unpublishing.'],
      12 => ['scheduler:media_has_been_unpublished_via_cron', 'Scheduler has unpublished this media item during cron.'],
    ];

    // PHPCS throws a false-positive 'variable $var is undefined' message when
    // the variable is defined by list( ) syntax. To avoid the unwanted warnings
    // we can put phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis
    // before each line that produces a warning of this type.
    // This has been fixed in Coder 8.3.10 which is used in Core 9.1.
    // @see https://www.drupal.org/project/coder/issues/2876245
    // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis
    foreach ($rule_data as $i => list($event_name, $description)) {
      $rule[$i] = $this->expressionManager->createRule();
      // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis
      $this->message[$i] = 'RULES message ' . $i . '. ' . $description;
      $rule[$i]->addAction('rules_system_message', ContextConfig::create()
        ->setValue('message', $this->message[$i])
        ->setValue('type', 'status')
        );
      $config_entity = $this->rulesStorage->create([
        'id' => 'rule' . $i,
        // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis
        'events' => [['event_name' => $event_name]],
        'expression' => $rule[$i]->getConfiguration(),
      ]);
      $config_entity->save();
    }

    $this->drupalLogin($this->schedulerUser);
  }

  /**
   * Check the presence or absence of expected message texts on the page.
   *
   * @param array $expectedMessages
   *   The ids of the messages that should be showing on the current page. All
   *   other messsages should not be displayed.
   */
  public function checkMessages(array $expectedMessages = []) {
    // Check that all the expected messages are shown.
    foreach ($expectedMessages as $i) {
      $this->assertSession()->pageTextContains($this->message[$i]);
    }
    // Check that none of the other messages are shown.
    $notExpecting = array_diff(array_keys($this->message), $expectedMessages);
    foreach ($notExpecting as $i) {
      $this->assertSession()->pageTextNotContains($this->message[$i]);
    }
  }

  /**
   * Tests that no events are triggered when there are no scheduling dates.
   */
  public function testRulesNodeEventsNone() {
    // Create a node without any scheduled dates, using node/add/ not
    // drupalCreateNode(), and check that no events are triggered.
    $title = 'A. Create node with no dates';
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm(['title[0][value]' => $title], 'Save');
    $this->checkMessages();

    // Edit the node and check that no events are triggered.
    $node = $this->drupalGetNodeByTitle($title);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm(['title[0][value]' => 'B. Edit node with no dates'], 'Save');
    $this->checkMessages();
  }

  /**
   * Tests the three events related to publishing a node.
   */
  public function testRulesNodeEventsPublish() {
    // Create a node with a publish-on date, and check that only event 1 is
    // triggered.
    $edit = [
      'title[0][value]' => 'C. Create node with publish-on date',
      'publish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'publish_on[0][value][time]' => date('H:i:s', time() + 3),
    ];
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    $this->checkMessages([1]);

    // Edit this node and check that only event 2 is triggered.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm(['title[0][value]' => 'D. Edit node with publish-on date'], 'Save');
    $this->checkMessages([2]);

    // Delay to ensure that the date entered is now in the past so that the node
    // will be processed during cron, and assert that only event 3 is triggered.
    sleep(5);
    $this->cronRun();
    $this->drupalGet('node/' . $node->id());
    $this->checkMessages([3]);
  }

  /**
   * Tests the three events related to unpublishing a node.
   */
  public function testRulesNodeEventsUnpublish() {
    // Create a node with an unpublish-on date, and check that only event 4 is
    // triggered.
    $edit = [
      'title[0][value]' => 'E. Create node with unpublish-on date',
      'unpublish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'unpublish_on[0][value][time]' => date('H:i:s', time() + 3),
    ];
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    $this->checkMessages([4]);

    // Edit this node and check that only event 5 is triggered.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $edit = [
      'title[0][value]' => 'F. Edit node with unpublish-on date',
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->checkMessages([5]);

    // Delay to ensure that the date entered is now in the past so that the node
    // will be processed during cron, and assert that only event 6 is triggered.
    sleep(5);
    $this->cronRun();
    $this->drupalGet('node/' . $node->id());
    $this->checkMessages([6]);
  }

  /**
   * Tests all six events related to publishing and unpublishing a node.
   */
  public function testRulesNodeEventsBoth() {
    // Create a node with both publish-on and unpublish-on dates, and check that
    // both event 1 and event 4 are triggered.
    $edit = [
      'title[0][value]' => 'G. Create node with both dates',
      'publish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'publish_on[0][value][time]' => date('H:i:s', time() + 3),
      'unpublish_on[0][value][date]' => date('Y-m-d', time() + 4),
      'unpublish_on[0][value][time]' => date('H:i:s', time() + 4),
    ];
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    $this->checkMessages([1, 4]);

    // Edit this node and check that events 2 and 5 are triggered.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $edit = [
      'title[0][value]' => 'H. Edit node with both dates',
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->checkMessages([2, 5]);

    // Delay to ensure that the dates are now in the past so that the node will
    // be processed during cron, and assert that events 3, 5 & 6 are triggered.
    sleep(6);
    $this->cronRun();
    $this->drupalGet('node/' . $node->id());
    $this->checkMessages([3, 5, 6]);
  }

  /**
   * Tests that no media events are triggered when there are no dates.
   */
  public function testRulesMediaEventsNone() {
    // Create a media item with no scheduled dates and check that no events are
    // triggered.
    $this->drupalGet('media/add/' . $this->mediaTypeName);
    $this->submitForm(['name[0][value]' => 'I. Create media with no dates'], 'Save');
    $this->checkMessages();

    // Edit the media and check that no events are triggered.
    $media = $this->getMediaItem();
    $this->drupalGet('media/' . $media->id() . '/edit');
    $this->submitForm(['name[0][value]' => 'J. Edit media with no dates'], 'Save');
    $this->checkMessages();
  }

  /**
   * Tests the three events related to publishing a media item.
   */
  public function testRulesMediaEventsPublish() {
    // Create a media item with a publish-on date, and check that only event 7
    // is triggered.
    $edit = [
      'name[0][value]' => 'K. Create media with publish-on date',
      'publish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'publish_on[0][value][time]' => date('H:i:s', time() + 3),
    ];
    $this->drupalGet('media/add/' . $this->mediaTypeName);
    $this->submitForm($edit, 'Save');
    $this->checkMessages([7]);

    // Edit the media and check that only event 8 is triggered.
    $media = $this->getMediaItem();
    $this->drupalGet('media/' . $media->id() . '/edit');
    $this->submitForm(['name[0][value]' => 'L. Edit media with publish-on date'], 'Save');
    $this->checkMessages([8]);

    // Delay to ensure that the date is now in the past so that the media item
    // will be processed during cron, and assert that only event 9 is triggered.
    sleep(5);
    $this->cronRun();
    $this->drupalGet('media/' . $media->id());
    $this->checkMessages([9]);
  }

  /**
   * Tests the three events related to unpublishing a media item.
   */
  public function testRulesMediaEventsUnpublish() {
    // Create a media item with an unpublish-on date, and check that only event
    // 10 is triggered.
    $edit = [
      'name[0][value]' => 'M. Create media with unpublish-on date',
      'unpublish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'unpublish_on[0][value][time]' => date('H:i:s', time() + 3),
    ];
    $this->drupalGet('media/add/' . $this->mediaTypeName);
    $this->submitForm($edit, 'Save');
    $this->checkMessages([10]);

    // Edit the media and check that only event 11 is triggered.
    $media = $this->getMediaItem();
    $this->drupalGet('media/' . $media->id() . '/edit');
    $this->submitForm(['name[0][value]' => 'N. Edit media with unpublish-on date'], 'Save');
    $this->checkMessages([11]);

    // Delay to ensure that the date is now in the past so that the media item
    // will be processed during cron, and assert only event 12 is triggered.
    sleep(5);
    $this->cronRun();
    $this->drupalGet('media/' . $media->id());
    $this->checkMessages([12]);
  }

  /**
   * Tests all six events related to publishing and unpublishing a media item.
   */
  public function testRulesMediaEventsBoth() {
    // Create a media item with both publish-on and unpublish-on dates, and
    // check that event 7 and event 10 are triggered.
    $edit = [
      'name[0][value]' => 'M. Create media with both dates',
      'publish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'publish_on[0][value][time]' => date('H:i:s', time() + 3),
      'unpublish_on[0][value][date]' => date('Y-m-d', time() + 4),
      'unpublish_on[0][value][time]' => date('H:i:s', time() + 4),
    ];
    $this->drupalGet('media/add/' . $this->mediaTypeName);
    $this->submitForm($edit, 'Save');
    $this->checkMessages([7, 10]);

    // Edit the media and check that only events 8 and 11 are triggered.
    $media = $this->getMediaItem();
    $this->drupalGet('media/' . $media->id() . '/edit');
    $this->submitForm(['name[0][value]' => 'N. Edit media with publish-on date'], 'Save');
    $this->checkMessages([8, 11]);

    // Delay to ensure that the dates are in the past so that the media will be
    // processed during cron, and assert that events 9, 11 and 12 are triggered.
    sleep(5);
    $this->cronRun();
    $this->drupalGet('media/' . $media->id());
    $this->checkMessages([9, 11, 12]);
  }

}
