<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\Tests\scheduler\Traits\SchedulerMediaSetupTrait;

/**
 * Tests the Scheduler interaction with Devel Generate module.
 *
 * @group scheduler
 * @group legacy
 * @todo Remove the 'legacy' tag when Devel no longer uses the deprecated
 * $published parameter for setPublished(), and does not use functions
 * drupal_set_message(), format_date() and db_query_range().
 */
class SchedulerDevelGenerateTest extends SchedulerBrowserTestBase {

  use SchedulerMediaSetupTrait;

  /**
   * Additional modules required.
   *
   * @var array
   */
  protected static $modules = ['devel_generate', 'media'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user with devel permission. Only 'administer devel_generate' is
    // actually required for these tests, but the others are useful for debug.
    // 'access content overview' is needed for /admin/content
    // 'access media overview' is needed for /admin/content/media
    // 'view scheduled content' is required for /admin/content/scheduled.
    // @todo update this when there is a url for scheduled media.
    $this->develUser = $this->drupalCreateUser([
      'administer devel_generate',
      'view scheduled content',
      'access content overview',
      'access media overview',
    ]);

    // Run the setup routine for Media entities.
    $this->SchedulerMediaSetup();

  }

  /**
   * Helper function to count scheduled nodes and assert the expected number.
   *
   * @param string $type
   *   The machine-name for the entity type to be checked.
   * @param string $bundle
   *   The machine-name for the bundle/content type to be checked.
   * @param string $field
   *   The field name to count, either 'publish_on' or 'unpublish_on'.
   * @param int $num_total
   *   The total number of entities that should exist.
   * @param int $num_scheduled
   *   The number of those nodes which should be scheduled with a $field.
   * @param int $time_range
   *   Optional time range from the devel form. The generated scheduler dates
   *   should be in a range of +/- this value from the current time.
   */
  protected function countScheduledEntities($type, $bundle, $field, $num_total, $num_scheduled, $time_range = NULL) {
    $storage = ($type == 'media') ? $this->mediaStorage : $this->nodeStorage;
    $type_field = ($type == 'media') ? 'bundle' : 'type';
    // Check that the expected number of entities have been created.
    $count = $storage->getQuery()
      ->condition($type_field, $bundle)
      ->count()
      ->execute();
    $this->assertEquals($num_total, $count, sprintf('The expected number of %s %s is %s, found %s', $bundle, $type, $num_total, $count));

    // Check that the expected number of entities have been scheduled.
    $count = $storage->getQuery()
      ->condition($type_field, $bundle)
      ->exists($field)
      ->count()
      ->execute();
    $this->assertEquals($num_scheduled, $count, sprintf('The expected number of %s %s with scheduled %s is %s, found %s', $bundle, $type, $field, $num_total, $count));

    if (isset($time_range) && $num_scheduled > 0) {
      // Define the minimum and maximum times that we expect the scheduled dates
      // to be within. REQUEST_TIME remains static for the duration of this test
      // but even though devel_generate also uses uses REQUEST_TIME this will
      // slowly creep forward during sucessive calls. Tests can fail incorrectly
      // for this reason, hence the best approximation is to use time() when
      // calculating the upper end of the range.
      $min = $this->requestTime - $time_range;
      $max = time() + $time_range;

      $query = $storage->getAggregateQuery();
      $result = $query
        ->condition($type_field, $bundle)
        ->aggregate($field, 'min')
        ->aggregate($field, 'max')
        ->execute();
      $min_found = $result[0]["{$field}_min"];
      $max_found = $result[0]["{$field}_max"];

      // Assert that the found values are within the expected range.
      $this->assertGreaterThanOrEqual($min, $min_found, sprintf('The minimum value found for %s is %s, earlier than the expected %s', $field, $this->dateFormatter->format($min_found, 'custom', 'j M, H:i:s'), $this->dateFormatter->format($min, 'custom', 'j M, H:i:s')));
      $this->assertLessThanOrEqual($max, $max_found, sprintf('The maximum value found for %s is %s, later than the expected %s', $field, $this->dateFormatter->format($max_found, 'custom', 'j M, H:i:s'), $this->dateFormatter->format($max, 'custom', 'j M, H:i:s')));
    }
  }

  /**
   * Test the functionality that Scheduler adds during entity generation.
   *
   * @dataProvider dataDevelGenerate()
   */
  public function testDevelGenerate($entityType, $bundle, $url_part, $enabled) {
    $this->drupalLogin($this->develUser);

    // Use the minimum required settings to see what happens when everything
    // else is left as default.
    $generate_settings = [
      "{$entityType}_types[$bundle]" => TRUE,
    ];
    $this->drupalPostForm("admin/config/development/generate/$url_part", $generate_settings, 'Generate');
    // Display the full content list and the scheduled list. Calls to these
    // pages are for information and debug only. They could be removed.
    $this->drupalGet('admin/content');
    $this->drupalGet('admin/content/scheduled');
    $this->drupalGet('admin/content/media');

    // Delete all content for this type and generate new content with only
    // publish-on dates. Use 100% as this is how we can count the expected
    // number of scheduled nodes. The time range of 3600 is one hour.
    // The number of nodes has to be lower than 50 until Devel issue with
    // undefined index 'users' is available and we switch to using 8.x-3.0
    // See https://www.drupal.org/project/devel/issues/3076613
    $generate_settings = [
      "{$entityType}_types[$bundle]" => TRUE,
      'num' => 40,
      'kill' => TRUE,
      'time_range' => 3600,
      'scheduler_publishing' => 100,
      'scheduler_unpublishing' => 0,
    ];
    $this->drupalPostForm("admin/config/development/generate/$url_part", $generate_settings, 'Generate');
    // @todo Make this more specific when we have a scheduled media view.
    $this->drupalGet('admin/content');
    $this->drupalGet('admin/content/scheduled');
    $this->drupalGet('admin/content/media');

    // Check we have the expected number of nodes scheduled for publishing only
    // and verify that that the dates are within the time range specified.
    $this->countScheduledEntities($entityType, $bundle, 'publish_on', 40, $enabled ? 40 : 0, $generate_settings['time_range']);
    $this->countScheduledEntities($entityType, $bundle, 'unpublish_on', 40, 0);

    // Do similar for unpublish_on date. Delete all then generate new content
    // with only unpublish-on dates. Time range 86400 is one day.
    $generate_settings = [
      "{$entityType}_types[$bundle]" => TRUE,
      'num' => 30,
      'kill' => TRUE,
      'time_range' => 86400,
      'scheduler_publishing' => 0,
      'scheduler_unpublishing' => 100,
    ];
    $this->drupalPostForm("admin/config/development/generate/$url_part", $generate_settings, 'Generate');
    $this->drupalGet('admin/content');
    $this->drupalGet('admin/content/scheduled');
    $this->drupalGet('admin/content/media');

    // Check we have the expected number of nodes scheduled for unpublishing
    // only, and verify that that the dates are within the time range specified.
    $this->countScheduledEntities($entityType, $bundle, 'publish_on', 30, 0);
    $this->countScheduledEntities($entityType, $bundle, 'unpublish_on', 30, $enabled ? 30 : 0, $generate_settings['time_range']);

  }

  /**
   * Provides data for testDevelGenerate().
   *
   * @return array
   *   Each array item has the values:
   *     [entity type, bundle/type id, url part, enabled for Scheduler].
   */
  public function dataDevelGenerate() {
    // The data provider does not have acces to $this so we have to hard-code
    // the entity bundle id.
    $data = [
      'Enabled node' => ['node', 'testpage', 'content', TRUE],
      'Non-enabled node' => ['node', 'not-for-scheduler', 'content', FALSE],
      'Media' => ['media', 'test_media_image', 'media', TRUE],
    ];
    return $data;
  }

}
