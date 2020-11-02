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
  protected $next_publish_on;

  /**
   * @var int
   */
  protected $next_unpublish_on;

  /**
   * Constructor.
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
    // @todo What exactly is the purpose of this function?
    // It is called when deciding whether to set the next values.
    return $this->getNextPublishOn() || $this->getNextUnpublishOn();
  }

  /**
   * Default validation method.
   *
   * @return bool
   */
  public function validate() {
    return !$this->publishesBeforeUnpublishing() && !$this->publishesAtSameTimeWhenUnpublishing();
  }

  /**
   * Determines if current $this->node tries to publish next time before it has
   * not even being unpublished.
   *
   * @return bool
   */
  protected function publishesBeforeUnpublishing() {
    return $this->calculateNextPublishedOn($this->getPublishOn()) < $this->getUnpublishOn();
  }

  /**
   * Determines if current $this->node tries to publish next time at the same
   * time it should be unpublishing.
   *
   * @return bool
   */
  protected function publishesAtSameTimeWhenUnpublishing() {
    return $this->calculateNextPublishedOn($this->getPublishOn()) == $this->getUnpublishOn();
  }

  /**
   * Gets publish_on that is being used in node given in constructor.
   *
   * @return mixed
   */
  protected function getPublishOn() {
    return $this->node->get('publish_on')->value;
  }

  /**
   * Gets unpublish_on that is being used in node given in constructor.
   *
   * @return mixed
   */
  protected function getUnpublishOn() {
    return $this->node->get('unpublish_on')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNextPublishOn($next_publish_on) {
    $this->next_publish_on = $next_publish_on;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextPublishOn() {
    return $this->next_publish_on;
  }

  /**
   * {@inheritdoc}
   */
  public function setNextUnpublishOn($next_unpublish_on) {
    $this->next_unpublish_on = $next_unpublish_on;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextUnpublishOn() {
    return $this->next_unpublish_on;
  }

}
