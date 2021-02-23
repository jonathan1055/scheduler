<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the six generic events that Scheduler dispatches.
 *
 * @group scheduler
 */
class SchedulerEventsTest extends SchedulerBrowserTestBase {

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
   * Covers six events for nodes.
   *
   * The events allow other modules to react to the Scheduler process being run.
   * The API test implementations of the event listeners alter the nodes
   * 'promote' and 'sticky' settings and changes the title.
   */
  public function testNodeEvents() {
    $this->drupalLogin($this->schedulerUser);

    // Create a test node.
    $settings = [
      'publish_on' => strtotime('-1 day'),
      'type' => $this->type,
      'promote' => FALSE,
      'sticky' => FALSE,
      'title' => 'API TEST node action',
    ];
    $node = $this->drupalCreateNode($settings);

    // Check that the 'sticky' and 'promote' fields are off for the new node.
    $this->assertFalse($node->isSticky(), 'The unpublished node is not sticky.');
    $this->assertFalse($node->isPromoted(), 'The unpublished node is not promoted.');

    // Run cron and check that the events have been dispatched correctly, by
    // verifying that the node is now sticky and has been promoted.
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertTrue($node->isSticky(), 'API event "PRE_PUBLISH" has changed the node to sticky.');
    $this->assertTrue($node->isPromoted(), 'API event "PUBLISH" has changed the node to promoted.');

    // Now set a date for unpublishing the node. Ensure 'sticky' and 'promote'
    // are set, so that the assertions are not affected by any failures above.
    $node->set('unpublish_on', strtotime('-1 day'))
      ->set('sticky', TRUE)->set('promote', TRUE)->save();

    // Run cron and check that the events have been dispatched correctly, by
    // verifying that the node is no longer sticky and not promoted.
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertFalse($node->isSticky(), 'API event "PRE_UNPUBLISH" has changed the node to not sticky.');
    $this->assertFalse($node->isPromoted(), 'API event "UNPUBLISH" has changed the node to not promoted.');

    // Turn on immediate publication when a publish date is in the past.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'publish')->save();

    // Ensure 'sticky' and 'promote' are not set, so that the assertions are not
    // affected by any failures above.
    $node->set('sticky', FALSE)->set('promote', FALSE)->save();

    // Edit the node and set a publish-on date in the past.
    $edit = [
      'publish_on[0][value][date]' => date('Y-m-d', strtotime('-2 day', $this->requestTime)),
      'publish_on[0][value][time]' => date('H:i:s', strtotime('-2 day', $this->requestTime)),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    // Verify that the values have been altered as expected.
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertTrue($node->isSticky(), 'API event "PRE_PUBLISH_IMMEDIATELY" has changed the node to sticky.');
    $this->assertTrue($node->isPromoted(), 'API event "PUBLISH_IMMEDIATELY" has changed the node to promoted.');
    $this->assertEquals('Published immediately', $node->title->value, 'API action "PUBLISH_IMMEDIATELY" has changed the node title correctly.');
  }

  /**
   * Covers six events for media entities.
   */
  public function testMediaEvents() {
    $this->drupalLogin($this->schedulerUser);

    // Create a media item.
    $media = $this->createMediaItem([
      'name' => 'API TEST MEDIA',
      'publish_on' => strtotime('-1 day'),
    ]);
    // Run cron and check that the events have been dispatched correctly. The
    // name is first changed by a PRE_PUBLISH event subscriber, then a second
    // time by a PUBLISH event watcher. Checking the final value tests both.
    scheduler_cron();
    $this->mediaStorage->resetCache([$media->id()]);
    $media = $this->mediaStorage->load($media->id());
    $this->assertEquals($media->label(), 'API TEST MEDIA - altered a second time by "PUBLISH" event');

    // Create a media item with an unpublish-on date.
    $media = $this->createMediaItem([
      'name' => 'API TEST MEDIA',
      'unpublish_on' => strtotime('-1 day'),
    ]);
    // Run cron and check that the events have been dispatched correctly.
    scheduler_cron();
    $this->mediaStorage->resetCache([$media->id()]);
    $media = $this->mediaStorage->load($media->id());
    $this->assertEquals($media->label(), 'API TEST MEDIA - altered a second time by "UNPUBLISH" event');

    // Turn on immediate publishing when a publish date is in the past.
    $this->mediaType->setThirdPartySetting('scheduler', 'publish_past_date', 'publish')->save();

    // Create an unpublished media item.
    $media = $this->createMediaItem([
      'name' => 'API TEST MEDIA',
      'status' => FALSE,
    ]);
    // Edit the media item, setting a publish-on date in the past.
    $edit = [
      'publish_on[0][value][date]' => date('Y-m-d', strtotime('-2 day', $this->requestTime)),
      'publish_on[0][value][time]' => date('H:i:s', strtotime('-2 day', $this->requestTime)),
    ];
    $this->drupalGet('media/' . $media->id() . '/edit');
    $this->submitForm($edit, 'Save');
    // Verify that the values have been altered as expected, without cron.
    $this->mediaStorage->resetCache([$media->id()]);
    $media = $this->mediaStorage->load($media->id());
    $this->assertEquals($media->label(), 'API TEST MEDIA - altered a second time by "PUBLISH_IMMEDIATELY" event');
  }

}
