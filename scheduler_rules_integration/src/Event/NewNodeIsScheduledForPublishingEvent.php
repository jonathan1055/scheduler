<?php

namespace Drupal\scheduler_rules_integration\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a newly created node is saved for the first time
 * and it has a scheduled publishing date.
 */
class NewNodeIsScheduledForPublishingEvent extends Event {

  const EVENT_NAME = 'scheduler_new_node_is_scheduled_for_publishing_event';

  /**
   * The node which is being scheduled and saved.
   */
  public $node;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node which is being scheduled and saved.
   */
  public function __construct($node) {
    $this->node = $node;
  }

}
