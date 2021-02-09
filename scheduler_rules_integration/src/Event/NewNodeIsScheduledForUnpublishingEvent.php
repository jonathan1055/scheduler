<?php

namespace Drupal\scheduler_rules_integration\Event;

/**
 * A new node is scheduled for unpublishing.
 *
 * This event is fired when a newly created node is saved for the first time
 * and it has a scheduled unpublishing date.
 */
class NewNodeIsScheduledForUnpublishingEvent extends NodeEventBase {

  const EVENT_NAME = 'scheduler_new_node_is_scheduled_for_unpublishing_event';

}
