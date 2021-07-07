<?php

namespace Drupal\scheduler_repeat;

use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * These events allow modules to react to the Scheduler process being performed.
 *
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
   *   The scheduler event object.
   */
  public function unpublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();

    // The content has now been unpublished so get the stored dates for the next
    // period. We do not want to check if a repeat plugin exists, we only need
    // to check if the two 'next' dates are available. In future we could set a
    // 'stop after' date which would remove the repeat plugin but leave the last
    // pair of 'next' dates for use here.
    $next_publish_on = $node->get('scheduler_repeat')->next_publish_on;
    $next_unpublish_on = $node->get('scheduler_repeat')->next_unpublish_on;
    if (empty($next_publish_on) || empty($next_unpublish_on)) {
      // Do not have both dates, so cannot set the next period.
      return;
    }
    // Set the new period publish_on and unpublish_on values.
    $node->set('publish_on', $next_publish_on);
    $node->set('unpublish_on', $next_unpublish_on);
    $event->setNode($node);
  }

}
