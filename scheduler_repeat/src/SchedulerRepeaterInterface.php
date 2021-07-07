<?php

namespace Drupal\scheduler_repeat;

/**
 * Interface for Sheduler Repeat plugins.
 */
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
   * @param int $publish_on
   *   Timestamp from which to calculate the next publish on value.
   *
   * @return int
   *   The calculated next publish_on value.
   */
  public function calculateNextPublishedOn(int $publish_on);

  /**
   * Calculates the next occurrence of Unpublish On.
   *
   * @param int $unpublish_on
   *   Timestamp from which to calculate the next unpublish on value.
   *
   * @return int
   *   The calculated next unpublish_on value.
   */
  public function calculateNextUnpublishedOn(int $unpublish_on);

}
