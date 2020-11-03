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

}
