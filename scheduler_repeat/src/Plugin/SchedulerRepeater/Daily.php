<?php

namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * Plugin definition for schedule to repeat every day.
 *
 * @SchedulerRepeater(
 *   id = "daily",
 *   label = @Translation("Daily"),
 *   weight = 2
 * )
 */
class Daily extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateNextPublishedOn(int $publish_on) {
    return strtotime("+1 day", $publish_on);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateNextUnpublishedOn(int $unpublish_on) {
    return strtotime("+1 day", $unpublish_on);
  }

}
