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
    /** @var Node $node */
    $node = $event->getNode();
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
