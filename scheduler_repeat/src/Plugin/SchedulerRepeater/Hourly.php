<?php

namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * Plugin definition for schedule to repeat every hour.
 *
 * @SchedulerRepeater(
 *   id = "hourly",
 *   label = @Translation("Hourly"),
 *   weight = 1
 * )
 */
class Hourly extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateNextPublishedOn(int $publish_on) {
    return strtotime("+1 hour", $publish_on);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateNextUnpublishedOn(int $unpublish_on) {
    return strtotime("+1 hour", $unpublish_on);
  }

}
