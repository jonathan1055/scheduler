<?php

namespace Drupal\scheduler_rules_integration\Event;

use Drupal\media\MediaInterface;
use Drupal\scheduler\SchedulerEvent;

/**
 * Class for all Rules media events.
 */
class RulesMediaEvent extends SchedulerEvent {

  /**
   * Define constants to convert the event identifier into the full event name.
   *
   * The final event names here are defined in the event deriver and are
   * different in format from the event names for node events, as originally
   * coded long-hand in scheduler_rules_integration.rules.events.yml.
   * However, the identifiers (PUBLISH, NEW_FOR_PUBLISHING, etc) are the same
   * for all types and this is how the actual event names are retrieved.
   */
  const PUBLISH = 'scheduler:media_has_been_published_via_cron';
  const UNPUBLISH = 'scheduler:media_has_been_unpublished_via_cron';
  const NEW_FOR_PUBLISHING = 'scheduler:new_media_is_scheduled_for_publishing';
  const NEW_FOR_UNPUBLISHING = 'scheduler:new_media_is_scheduled_for_unpublishing';
  const EXISTING_FOR_PUBLISHING = 'scheduler:existing_media_is_scheduled_for_publishing';
  const EXISTING_FOR_UNPUBLISHING = 'scheduler:existing_media_is_scheduled_for_unpublishing';

  /**
   * The media item which is being processed.
   *
   * @var \Drupal\media\MediaInterface
   */
  public $media;

  /**
   * Constructs the object.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item which is being processed.
   */
  public function __construct(MediaInterface $media) {
    $this->media = $media;
  }

}
