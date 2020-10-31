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
   * Determines if given $node should be repeated.
   *
   * @return bool
   */
  public function shouldRepeat();

  /**
   * Validates that publish_on and unpublish_on fields are aligned with this
   * plugin's repeat logic.
   *
   * For example, you can't say "09:00-11:00 repeat every hour", because the
   * periods would overlap.
   *
   * Repeater should have $this->node available for validation.
   *
   * @return bool
   */
  public function validate();

  /**
   * @param $next_publish_on
   *
   * @return mixed
   */
  public function setNextPublishOn($next_publish_on);

  /**
   * Returns next publish on timestamp that was set by setNextPublishOn()
   * 
   * @return mixed
   * @see setNextPublishOn()
   */
  public function getNextPublishOn();

  /**
   * @param $next_unpublish_on
   *
   * @return mixed
   */
  public function setNextUnpublishOn($next_unpublish_on);

  /**
   * Returns next unpublish on timestamp that was set by setNextUnpublishOn()
   *
   * @return mixed
   * @see setNextUnpublishOn()
   */
  public function getNextUnpublishOn();

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
