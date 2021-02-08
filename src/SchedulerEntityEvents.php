<?php

namespace Drupal\scheduler;

/**
 * Lists the six events dispatched by Scheduler for all supported entity types.
 *
 * These events are dispatched for all types of entity not just node entities.
 * The original node-only events remain in class SchedulerEvents.
 */
final class SchedulerEntityEvents {

  /**
   * The event triggered after an entity is published immediately.
   *
   * This event allows modules to react after an entity is published
   * immediately when being saved after editing. The event listener method
   * receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEntityEvent
   *
   * @var string
   */
  const PUBLISH_IMMEDIATELY = 'scheduler.entity_publish_immediately';

  /**
   * The event triggered after an entity is published by cron.
   *
   * This event allows modules to react after an entity is published by Cron.
   * The event listener receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEntityEvent
   *
   * @var string
   */
  const PUBLISH = 'scheduler.entity_publish';

  /**
   * The event triggered before an entity is published immediately.
   *
   * This event allows modules to react before an entity is published
   * immediately when being saved after editing. The event listener method
   * receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEntityEvent
   *
   * @var string
   */
  const PRE_PUBLISH_IMMEDIATELY = 'scheduler.entity_pre_publish_immediately';

  /**
   * The event triggered before an entity is published by cron.
   *
   * This event allows modules to react before an entity is published by Cron.
   * The event listener receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEntityEvent
   *
   * @var string
   */
  const PRE_PUBLISH = 'scheduler.entity_pre_publish';

  /**
   * The event triggered before an entity is unpublished by cron.
   *
   * This event allows modules to react before an entity is unpublished by Cron.
   * The event listener receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEntityEvent
   *
   * @var string
   */
  const PRE_UNPUBLISH = 'scheduler.entity_pre_unpublish';

  /**
   * The event triggered after an entity is unpublished by cron.
   *
   * This event allows modules to react after an entity is unpublished by Cron.
   * The event listener receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEntityEvent
   *
   * @var string
   */
  const UNPUBLISH = 'scheduler.entity_unpublish';

}
