<?php


namespace Drupal\scheduler_repeat;


interface SchedulerRepeaterInterface {

  /**
   * SchedulerRepeaterInterface constructor.
   *
   * @param array $options
   *   Array of keyed options:
   *   - 'node': The NodeInterface which we operate on.
   */
  public function __construct(array $options);

  /**
   * Calculates the next occurrence of Publish On.
   *
   * @param $publish_on
   *   Timestamp from which to calculate the next publish on value.
   *
   * @return mixed
   */
  public function calculateNextPublishedOn($publish_on);

  /**
   * Calculates the next occurrence of Unpublish On
   *
   * @param $unpublish_on
   *   Timestamp from which to calculate the next unpublish on value.
   *
   * @return mixed
   */
  public function calculateNextUnpublishedOn($unpublish_on);

}
