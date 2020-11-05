<?php

namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * @SchedulerRepeater(
 *   id = "monthly",
 *   label = @Translation("Monthly"),
 *   weight = 4
 * )
 */
class Monthly extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * @param $datetime
   *
   * @return int
   */
  public function calculateNextPublishedOn($datetime) {
    return strtotime("+1 month", $datetime);
  }

  /**
   * @param $datetime
   *
   * @return int
   */
  public function calculateNextUnpublishedOn($datetime) {
    return strtotime("+1 month", $datetime);
  }

}
