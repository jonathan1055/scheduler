<?php

namespace Drupal\scheduler;

use Symfony\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * Base class on which all Scheduler events are extended.
 */
class EventBase extends Event {

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
