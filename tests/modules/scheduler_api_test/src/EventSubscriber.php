<?php

namespace Drupal\scheduler_api_test;

use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests events fired on entity objects.
 *
 * These events allow modules to react to the Scheduler process being performed.
 * They are all triggered during Scheduler cron processing with the exception of
 * 'publish_immediately' which is triggered from scheduler_node_presave().
 *
 * The tests use the standard 'sticky' and 'promote' fields as a simple way to
 * check the processing. Use extra conditional checks on $node->isPublished() to
 * make the tests stronger so they fail if the calls are in the wrong place.
 *
 * To allow this API test module to be enabled interactively (for development
 * and testing) we must avoid unwanted side-effects on other non-test nodes.
 * This is done simply by checking that the node title starts with 'API TEST'.
 *
 * @group scheduler_api_test
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The values in the arrays give the function names below.
    $events[SchedulerEvents::PRE_PUBLISH][] = ['testPrePublish'];
    $events[SchedulerEvents::PRE_UNPUBLISH][] = ['testPreUnpublish'];
    $events[SchedulerEvents::PUBLISH][] = ['publish'];
    $events[SchedulerEvents::UNPUBLISH][] = ['unpublish'];
    $events[SchedulerEvents::PUBLISH_IMMEDIATELY][] = ['publishImmediately'];
    return $events;
  }

  /**
   * Operations to perform before Scheduler publishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function testPrePublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // Before publishing a node make it sticky.
    if (!$node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setSticky(TRUE);
      $event->setNode($node);
    }
  }

  /**
   * Operations to perform before Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function testPreUnpublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    if ($node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      // Before unpublishing a node make it unsticky.
      $node->setSticky(FALSE);
      $event->setNode($node);
    }
  }

  /**
   * Operations to perform after Scheduler publishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function publish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // After publishing a node promote it to the front page.
    if ($node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setPromoted(TRUE);
      $event->setNode($node);
    }
  }

  /**
   * Operations to perform after Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function unpublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // After unpublishing a node remove it from the front page.
    if (!$node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setPromoted(FALSE);
      $event->setNode($node);
    }
  }

  /**
   * Operations after Scheduler publishes a node immediately not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function publishImmediately(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // When publishing immediately set the node to sticky and promoted, and
    // also change the title.
    if (!$node->isPromoted() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setTitle('Published immediately')
        ->setPromoted(TRUE)
        ->setSticky(TRUE);
      $event->setNode($node);
    }
  }

}
