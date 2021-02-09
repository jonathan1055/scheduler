<?php

namespace Drupal\scheduler_rules_integration\Event;

use Drupal\node\NodeInterface;
use Drupal\scheduler\SchedulerEvent;

/**
 * Base class on which all Rules node events are extended.
 */
class NodeEventBase extends SchedulerEvent {

  /**
   * The node which is being processed.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $node;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node which is being processed.
   */
  public function __construct(NodeInterface $node) {
    $this->node = $node;
  }

}
