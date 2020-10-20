<?php


namespace Drupal\scheduler_repeat\Plugin\SchedulerRepeater;


use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\scheduler_repeat\MissingOptionNodeException;
use Drupal\scheduler_repeat\SchedulerRepeaterInterface;

/**
 * @SchedulerRepeater(
 *   id = "hourly",
 *   label = @Translation("Hourly")
 * )
 */
class Hourly implements SchedulerRepeaterInterface {

  /**
   * Holds the node that is being repeated.
   *
   * @var NodeInterface
   */
  protected $node;

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
    return $this->hasUnpublishedOnValue() && $this->hasPublishedOnValue();
  }

  /**
   * {@inheritdoc}
   */
  public function applyNextOccurance(Node &$node) {
    $node->set('publish_on', $this->calculateNextPublishedOn());
    $node->set('unpublish_on', $this->calculateNextUnpublishedOn());
  }

  /**
   * @return bool
   */
  protected function hasPublishedOnValue() {
    return !$this->node->get('publish_on')->isEmpty();
  }

  /**
   * @return bool
   */
  protected function hasUnpublishedOnValue() {
    return !$this->node->get('unpublish_on')->isEmpty();
  }

  /**
   * @return int
   */
  protected function calculateNextPublishedOn() {
    return strtotime("+1 day");
  }

  /**
   * @return int
   */
  protected function calculateNextUnpublishedOn() {
    return strtotime("+2 day");
  }

}
