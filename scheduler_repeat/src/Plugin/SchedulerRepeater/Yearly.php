<?php


namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * @SchedulerRepeater(
 *   id = "yearly",
 *   label = @Translation("Yearly"),
 *   weight = 5
 * )
 */
class Yearly extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * @param $datetime
   *
   * @return int
   */
  public function calculateNextPublishedOn($datetime) {
    return strtotime("+1 year", $datetime);
  }

  /**
   * @param $datetime
   *
   * @return int
   */
  public function calculateNextUnpublishedOn($datetime) {
    return strtotime("+1 year", $datetime);
  }

}
