<?php

namespace Drupal\scheduler_rules_integration\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when Scheduler publishes a node.
 */
class SchedulerHasUnpublishedThisNodeEvent extends Event {

  const EVENT_NAME = 'scheduler_has_unpublished_this_node_event';

  /**
   * The node which has been processed..
   */
  public $node;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node which has been unpublished by Scheduler.
   */
  public function __construct($node) {
    $this->node = $node;
  }

}
