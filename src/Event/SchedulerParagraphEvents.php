<?php

namespace Drupal\scheduler\Event;

/**
 * Lists the six events dispatched by Scheduler relating to Paragraph entities.
 */
final class SchedulerParagraphEvents {

  /**
   * The event triggered after a paragraph is published immediately.
   *
   * This event allows modules to react after an entity is published
   * immediately when being saved after editing. The event listener method
   * receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\Event\SchedulerEvent
   *
   * @var string
   */
  const PUBLISH_IMMEDIATELY = 'scheduler.paragraph_publish_immediately';

  /**
   * The event triggered after a paragraph is published by cron.
   *
   * This event allows modules to react after an entity is published by Cron.
   * The event listener receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\Event\SchedulerEvent
   *
   * @var string
   */
  const PUBLISH = 'scheduler.paragraph_publish';

  /**
   * The event triggered before a paragraph is published immediately.
   *
   * This event allows modules to react before an entity is published
   * immediately when being saved after editing. The event listener method
   * receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\Event\SchedulerEvent
   *
   * @var string
   */
  const PRE_PUBLISH_IMMEDIATELY = 'scheduler.paragraph_pre_publish_immediately';

  /**
   * The event triggered before a paragraph is published by cron.
   *
   * This event allows modules to react before an entity is published by Cron.
   * The event listener receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\Event\SchedulerEvent
   *
   * @var string
   */
  const PRE_PUBLISH = 'scheduler.paragraph_pre_publish';

  /**
   * The event triggered before a paragraph is unpublished by cron.
   *
   * This event allows modules to react before an entity is unpublished by Cron.
   * The event listener receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\Event\SchedulerEvent
   *
   * @var string
   */
  const PRE_UNPUBLISH = 'scheduler.paragraph_pre_unpublish';

  /**
   * The event triggered after a paragraph is unpublished by cron.
   *
   * This event allows modules to react after an entity is unpublished by Cron.
   * The event listener receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\Event\SchedulerEvent
   *
   * @var string
   */
  const UNPUBLISH = 'scheduler.paragraph_unpublish';

}
