<?php


namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * @SchedulerRepeater(
 *   id = "tenminutes",
 *   label = @Translation("Every 10 minutes")
 * )
 */
class TenMinutes extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * @param $previous_publish_on
   *
   * @return int
   */
  public function calculateNextPublishedOn($previous_publish_on) {
    return strtotime("+10 mins", $previous_publish_on);
  }

  /**
   * @param $previous_unpublish_on
   *
   * @return int
   */
  public function calculateNextUnpublishedOn($previous_unpublish_on) {
    return strtotime("+10 mins", $previous_unpublish_on);
  }

}
