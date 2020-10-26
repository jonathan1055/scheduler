<?php


namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;

use Drupal\node\NodeInterface;
use Drupal\scheduler_repeat\MissingOptionNodeException;
use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

abstract class SchedulerRepeaterBase implements SchedulerRepeaterInterface {

  /**
   * Holds the node that is being repeated.
   *
   * @var NodeInterface
   */
  protected $node;

  /**
   * @var int
   */
  protected $previous_publish_on;

  /**
   * @var int
   */
  protected $previous_unpublish_on;

  /**
   * Hourly constructor.
   *
   * @param array $options
   *
   * @throws MissingOptionNodeException
   */
  public function __construct(array $options) {
    if (empty($options['node']) || !$options['node'] instanceof NodeInterface) {
      throw new MissingOptionNodeException('Repeater ' . self::class . ' was constructed without $options[\'node\']');
    }
    $this->node = $options['node'];
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRepeat() {
    return $this->hasPreviousScheduleAvailable();
  }

  /**
   * {@inheritdoc}
   */
  protected function hasPreviousScheduleAvailable() {
    return $this->getPreviousPublishOn() || $this->getPreviousUnublishOn();
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviousPublishOn($previous_publish_on) {
    $this->previous_publish_on = $previous_publish_on;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousPublishOn() {
    return $this->previous_publish_on;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviousUnpublishOn($previous_unpublish_on) {
    $this->previous_unpublish_on = $previous_unpublish_on;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousUnpublishOn() {
    return $this->previous_unpublish_on;
  }

}
