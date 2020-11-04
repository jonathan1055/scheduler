<?php


namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * @SchedulerRepeater(
 *   id = "sixminutes",
 *   label = @Translation("Testing: Every 6 minutes"),
 *   weight = 100
 * )
 */
class SixMinutes extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * @param $publish_on
   *
   * @return int
   */
  public function calculateNextPublishedOn($publish_on) {
    return strtotime("+6 mins", $publish_on);
  }

  /**
   * @param $unpublish_on
   *
   * @return int
   */
  public function calculateNextUnpublishedOn($unpublish_on) {
    return strtotime("+6 mins", $unpublish_on);
  }

}
