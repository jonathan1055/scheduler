<?php


namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * @SchedulerRepeater(
 *   id = "weekly",
 *   label = @Translation("Weekly"),
 *   weight = 3
 * )
 */
class Weekly extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * @param $datetime
   *
   * @return int
   */
  public function calculateNextPublishedOn($datetime) {
    return strtotime("+7 days", $datetime);
  }

  /**
   * @param $datetime
   *
   * @return int
   */
  public function calculateNextUnpublishedOn($datetime) {
    return strtotime("+7 days", $datetime);
  }

}
