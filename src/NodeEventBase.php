<?php

namespace Drupal\scheduler;

use Drupal\node\NodeInterface;

/**
 * Base class on which all node events are extended.
 *
 * This is used for Scheduler events and scheduler_rules_integration events.
 *
 * @todo Can this be moved into scheduler_rules_integration? It may not be
 * needed for Scheduler's own events.
 */
class NodeEventBase extends EventBase {

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
