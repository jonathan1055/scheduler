<?php

namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * Plugin definition for schedule to repeat every six minutes.
 *
 * @SchedulerRepeater(
 *   id = "sixminutes",
 *   label = @Translation("Testing: Every 6 minutes"),
 *   weight = 100
 * )
 */
class SixMinutes extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateNextPublishedOn(int $publish_on) {
    return strtotime("+6 mins", $publish_on);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateNextUnpublishedOn(int $unpublish_on) {
    return strtotime("+6 mins", $unpublish_on);
  }

}
