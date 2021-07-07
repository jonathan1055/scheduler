<?php

namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * Plugin definition for schedule to repeat every year.
 *
 * @SchedulerRepeater(
 *   id = "yearly",
 *   label = @Translation("Yearly"),
 *   weight = 5
 * )
 */
class Yearly extends SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateNextPublishedOn(int $publish_on) {
    return strtotime("+1 year", $publish_on);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateNextUnpublishedOn(int $unpublish_on) {
    return strtotime("+1 year", $unpublish_on);
  }

}
