<?php


namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * @SchedulerRepeater(
 *   id = "hourly",
 *   label = @Translation("Hourly")
 * )
 */
class Hourly extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * @param $previous_publish_on
   *
   * @return int
   */
  public function calculateNextPublishedOn($previous_publish_on) {
    return strtotime("+1 hour", $previous_publish_on);
  }

  /**
   * @param $previous_unpublish_on
   *
   * @return int
   */
  public function calculateNextUnpublishedOn($previous_unpublish_on) {
    return strtotime("+1 hour", $previous_unpublish_on);
  }

}
