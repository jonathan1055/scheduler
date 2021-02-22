<?php

/**
 * @file
 * Creates alias for SchedulerEvents class to maintain backwards-compatibility.
 */

/**
 * Lists the six Scheduler events dispatched for Node entities.
 *
 * This class, named 'SchedulerEvents', must remain for backwards-compatibility
 * with existing implementations of event subscribers for Node events. However
 * each supported entity type now has a specific class Scheduler{Type}Events
 * and so the original SchedulerEvents is now an alias for the Node version.
 * This makes it clearer that SchedulerNodeEvents is for nodes, and also
 * simplifies the dispatching process when each class name has the same format.
 */

class_alias('Drupal\scheduler\SchedulerNodeEvents', 'Drupal\scheduler\SchedulerEvents');
