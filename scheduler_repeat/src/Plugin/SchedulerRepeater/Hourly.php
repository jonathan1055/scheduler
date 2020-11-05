<?php

namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * @SchedulerRepeater(
 *   id = "hourly",
 *   label = @Translation("Hourly"),
 *   weight = 1
 * )
 */
class Hourly extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * @param $datetime
   *
   * @return int
   */
  public function calculateNextPublishedOn($datetime) {
    return strtotime("+1 hour", $datetime);
  }

  /**
   * @param $datetime
   *
   * @return int
   */
  public function calculateNextUnpublishedOn($datetime) {
    return strtotime("+1 hour", $datetime);
  }

}
