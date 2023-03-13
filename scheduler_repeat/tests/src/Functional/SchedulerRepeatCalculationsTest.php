<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the repeat calculations on node creation, edit and during cron.
 *
 * @group scheduler
 */
class SchedulerRepeatCalculationsTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   */
  protected static $modules = ['scheduler_repeat'];

  /**
   * Tests that the next dates are calculated during node creation and editing.
   *
   * @dataProvider dataRepeatPlugins()
   */
  public function testRepeatCreateEdit($plugin, $calculation) {
    $this->drupalLogin($this->adminUser);

    // Create a node with a repeating schedule.
    $options = [
      'type' => $this->type,
      'title' => 'Repeat ' . $plugin,
      'status' => FALSE,
      'publish_on' => strtotime('+15 mins', $this->requestTime),
      'unpublish_on' => strtotime('+30 mins', $this->requestTime),
      'scheduler_repeat' => ['plugin' => $plugin],
    ];
    $node = $this->drupalCreateNode($options);
    $nid = $node->id();

    // Check that the initial next dates have been created correctly.
    $expected_next_publish = strtotime($calculation, $options['publish_on']);
    $expected_next_unpublish = strtotime($calculation, $options['unpublish_on']);
    $this->assertEquals($expected_next_publish, $node->get('scheduler_repeat')->next_publish_on);
    $this->assertEquals($expected_next_unpublish, $node->get('scheduler_repeat')->next_unpublish_on);

    // Edit and save.
    $editted_publish_on = strtotime('+20 mins', $this->requestTime);
    $editted_unpublish_on = strtotime('+45 mins', $this->requestTime);
    $edit = [
      'body[0][value]' => "plugin = $plugin\nncalculation = $calculation",
      'publish_on[0][value][date]' => date('Y-m-d', $editted_publish_on),
      'publish_on[0][value][time]' => date('H:i:s', $editted_publish_on),
      'unpublish_on[0][value][date]' => date('Y-m-d', $editted_unpublish_on),
      'unpublish_on[0][value][time]' => date('H:i:s', $editted_unpublish_on),
    ];
    $this->drupalPostForm('node/' . $nid . '/edit', $edit, 'Save');

    // Reload the node.
    $node = $this->nodeStorage->load($nid);

    // Check that the updated next dates have been created correctly.
    $expected_next_publish = strtotime($calculation, $editted_publish_on);
    $expected_next_unpublish = strtotime($calculation, $editted_unpublish_on);
    $this->assertEquals($expected_next_publish, $node->get('scheduler_repeat')->next_publish_on);
    $this->assertEquals($expected_next_unpublish, $node->get('scheduler_repeat')->next_unpublish_on);
  }

  /**
   * Tests that the next dates are calculated after unpublishing via cron.
   *
   * @dataProvider dataRepeatPlugins()
   */
  public function testRepeatCron($plugin, $calculation) {
    $this->drupalLogin($this->adminUser);

    // Allow publishing dates in the past, so they can be processed by cron
    // without waiting.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'schedule')->save();

    // Create a node with a repeating schedule.
    $options = [
      'type' => $this->type,
      'title' => 'Repeat ' . $plugin,
      'status' => FALSE,
      'publish_on' => strtotime('-30 mins', $this->requestTime),
      'unpublish_on' => strtotime('-10 mins', $this->requestTime),
      'scheduler_repeat' => ['plugin' => $plugin],
    ];
    $node = $this->drupalCreateNode($options);
    $nid = $node->id();

    // Call the main scheduler function that executed during a cron run, then
    // reset the cache and reload the node.
    scheduler_cron();
    $this->nodeStorage->resetCache([$nid]);
    $node = $this->nodeStorage->load($nid);

    // Check that the node has been re-scheduled for the next dates.
    $expected_publish = strtotime($calculation, $options['publish_on']);
    $expected_unpublish = strtotime($calculation, $options['unpublish_on']);
    $this->assertEquals($expected_publish, $node->publish_on->value);
    $this->assertEquals($expected_unpublish, $node->unpublish_on->value);

    // Check that the node has been re-scheduled for the next dates.
    $expected_next_publish = strtotime($calculation, $expected_publish);
    $expected_next_unpublish = strtotime($calculation, $expected_unpublish);
    $this->assertEquals($expected_next_publish, $node->get('scheduler_repeat')->next_publish_on);
    $this->assertEquals($expected_next_unpublish, $node->get('scheduler_repeat')->next_unpublish_on);
  }

  /**
   * Provides data for testRepeatCreateEdit() and testRepeatCron().
   *
   * @return array
   *   This is a nested array. The top-level keys are not available in the test
   *   so can be anything, but we use the plugin id for clarity. Each value is
   *   an associative array with the following key-value pairs:
   *     plugin             - the plugin id
   *     calculation        - a string to use in strToTime() calculation.
   */
  public function dataRepeatPlugins() {

    $data = [
      'hourly' => [
        'plugin' => 'hourly',
        'calculation' => '+60 mins',
      ],
      'daily' => [
        'plugin' => 'daily',
        'calculation' => '+24 hours',
      ],
      'weekly' => [
        'plugin' => 'weekly',
        'calculation' => '+7 days',
      ],
      'monthly' => [
        'plugin' => 'monthly',
        'calculation' => '+1 month',
      ],
      'yearly' => [
        'plugin' => 'yearly',
        'calculation' => '+12 months',
      ],
    ];

    // Use unset($data[x]) to remove a temporarily unwanted item, use
    // return [$data[x], $data[y]] to selectively test just some items, or have
    // the default return $data to test everything.
    return $data;
  }

}
