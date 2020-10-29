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
