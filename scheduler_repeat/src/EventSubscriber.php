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
    $events[SchedulerEvents::UNPUBLISH][] = ['unpublish'];
    return $events;
  }

  /**
   * Operations to perform after Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *
   * @return int|void
   */
  public function unpublish(SchedulerEvent $event) {
    /** @var Node $node */
    $node = $event->getNode();

    if (!$repeater = _scheduler_repeat_get_repeater($node)) {
      return 0;
    }

    // The content has now been unpublished. Get the stored dates for the next
    // period, and set these as the new publish_on and unpublish_on values.
    $next_publish_on = $node->get('scheduler_repeat')->next_publish_on;
    $next_unpublish_on = $node->get('scheduler_repeat')->next_unpublish_on;
    if (empty($next_publish_on) || empty($next_unpublish_on)) {
      // Do not have both values, so cannot set the next period.
      return;
    }
    // Set the new period dates.
    $node->set('publish_on', $next_publish_on);
    $node->set('unpublish_on', $next_unpublish_on);
    $event->setNode($node);
  }
}
