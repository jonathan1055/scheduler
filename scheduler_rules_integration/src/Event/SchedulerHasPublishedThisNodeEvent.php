<?php

namespace Drupal\scheduler_rules_integration\Event;

/**
 * A node is published by Scheduler.
 *
 * This event is fired when Scheduler publishes a node via cron.
 */
class SchedulerHasPublishedThisNodeEvent extends NodeEventBase {

  const EVENT_NAME = 'scheduler_has_published_this_node_event';

}
