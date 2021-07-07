<?php

namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * Plugin definition for schedule to repeat every ten minutes.
 *
 * @SchedulerRepeater(
 *   id = "tenminutes",
 *   label = @Translation("Testing: Every 10 minutes"),
 *   weight = 101
 * )
 */
class TenMinutes extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateNextPublishedOn(int $publish_on) {
    return strtotime("+10 mins", $publish_on);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateNextUnpublishedOn(int $unpublish_on) {
    return strtotime("+10 mins", $unpublish_on);
  }

}
