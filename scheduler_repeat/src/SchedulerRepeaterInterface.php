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
   * @param $previous_publish_on
   *
   * @return mixed
   */
  public function setPreviousPublishOn($previous_publish_on);

  /**
   * @return mixed
   *   Returns previous publish on timestamp that was set by setPreviousPublishOn()
   * @see setPreviousPublishOn()
   */
  public function getPreviousPublishOn();

  /**
   * @param $previous_unpublish_on
   *
   * @return mixed
   */
  public function setPreviousUnpublishOn($previous_unpublish_on);

  /**
   * @return mixed
   *   Returns previous publish on timestamp that was set by setPreviousUnpublishOn()
   * @see setPreviousUnpublishOn()
   */
  public function getPreviousUnpublishOn();

  /**
   * Calculates the next occurrence based on given $previous_publish_on
   *
   * @param $previous_publish_on
   *   Timestamp of previous publish on.
   *
   * @return mixed
   */
  public function calculateNextPublishedOn($previous_publish_on);

  /**
   * Calculates the next occurrence based on given $previous_unpublish_on
   *
   * @param $previous_publish_on
   *   Timestamp of previous unpublish on.
   *
   * @return mixed
   */
  public function calculateNextUnpublishedOn($previous_unpublish_on);

}
