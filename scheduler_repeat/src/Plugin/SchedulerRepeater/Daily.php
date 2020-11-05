<?php

namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * @SchedulerRepeater(
 *   id = "daily",
 *   label = @Translation("Daily"),
 *   weight = 2
 * )
 */
class Daily extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * @param $datetime
   *
   * @return int
   */
  public function calculateNextPublishedOn($datetime) {
    return strtotime("+1 day", $datetime);
  }

  /**
   * @param $datetime
   *
   * @return int
   */
  public function calculateNextUnpublishedOn($datetime) {
    return strtotime("+1 day", $datetime);
  }

}
