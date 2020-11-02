<?php

/**
 * @file
 * Contains \Drupal\scheduler_repeat\EventSubscriber.
 */

namespace Drupal\scheduler_repeat;

use Drupal\node\Entity\Node;
use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * These events allow modules to react to the Scheduler process being performed.
 * They are all triggered during Scheduler cron processing with the exception of
 * 'pre_publish_immediately' and 'publish_immediately' which are triggered from
 * scheduler_node_presave().
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The values in the arrays give the function names below.
    $events = [];
    $events[SchedulerEvents::PRE_PUBLISH][] = array('prePublish');
    $events[SchedulerEvents::PUBLISH][] = array('publish');
    $events[SchedulerEvents::PRE_UNPUBLISH][] = array('preUnpublish');
    $events[SchedulerEvents::UNPUBLISH][] = array('unpublish');
    $events[SchedulerEvents::PRE_PUBLISH_IMMEDIATELY][] = array('prePublishImmediately');
    $events[SchedulerEvents::PUBLISH_IMMEDIATELY][] = array('publishImmediately');
    return $events;
  }

  /**
   * Operations to perform before Scheduler publishes a node via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function prePublish(SchedulerEvent $event) {

    // @todo We do need this.
    // try {
    //   _scheduler_repeat_set_snapshot_of_scheduling_timestamps($node);
    //   $event->setNode($node);
    // } catch (\Exception $e) {
    //   _scheduler_repeat_log_warning('Could not set scheduling snapshot: @message', ['@message' => $e->getMessage()]);
    // }

    

  }

  /**
   * Operations to perform after Scheduler publishes a node via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function publish(SchedulerEvent $event) {
    
  }

  /**
   * Operations to perform before Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function preUnpublish(SchedulerEvent $event) {
  }

  /**
   * Operations to perform after Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function unpublish(SchedulerEvent $event) {
    /** @var Node $node */
    $node = $event->getNode();

    if (!$repeater = _scheduler_repeat_get_repeater($node)) {
      return 0;
    }

    // @todo Check what this does.
    // if (!$repeater->shouldRepeat()) {
    //   ddm('should not repeat any more, so exiting');
    //   return 0;
    // }

    // The content has now been unpublished. Get the stored dates for the next
    // period, and set these as the new publish_on and unpublish_on values. 
    $next_publish_on = $node->get('repeat')->next_publish_on;
    $next_unpublish_on = $node->get('repeat')->next_unpublish_on;
    if (empty($next_publish_on) || empty($next_unpublish_on)) {
      // Do not have both values, so cannot set the next period.
      return;
    }

    $node->set('publish_on', $next_publish_on);
    $node->set('unpublish_on', $next_unpublish_on);

    // @todo Not sure why this is necessary?
    $repeater->setNextPublishOn($next_publish_on);
    $repeater->setNextUnpublishOn($next_unpublish_on);

    // Calculate and store the new next_publish_on and next_unpublish_on values.
    $request_time = \Drupal::time()->getRequestTime();
    while ($next_publish_on < $request_time) {
      $next_publish_on = $repeater->calculateNextPublishedOn($next_publish_on);
      $next_unpublish_on = $repeater->calculateNextUnpublishedOn($next_unpublish_on);
    }
    $node->set('repeat', [
      'plugin' => $node->repeat->plugin,
      'next_publish_on' => $next_publish_on,
      'next_unpublish_on' => $next_unpublish_on,
    ]);

    $event->setNode($node);
  }

  /**
   * Operations to perform before Scheduler publishes a node immediately not via
   * cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function prePublishImmediately(SchedulerEvent $event) {
  }

  /**
   * Operations to perform after Scheduler publishes a node immediately not via
   * cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function publishImmediately(SchedulerEvent $event) {
  }

}
