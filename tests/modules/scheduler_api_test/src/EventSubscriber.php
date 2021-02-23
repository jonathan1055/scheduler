<?php

namespace Drupal\scheduler_api_test;

use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Drupal\scheduler\SchedulerMediaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests events fired on entity objects.
 *
 * These events allow modules to react to the Scheduler process being performed.
 * They are all triggered during Scheduler cron processing with the exception of
 * 'pre_publish_immediately' and 'publish_immediately' which are triggered from
 * scheduler_entity_presave().
 *
 * The node event tests use the 'sticky' and 'promote' fields as a simple way to
 * check the processing. There are extra conditional checks on isPublished() to
 * make the tests stronger so they fail if the calls are in the wrong place.
 *
 * The media tests cannot use 'sticky' and 'promote' as these fields do not
 * exist, so the media name is altered instead.
 *
 * To allow this API test module to be enabled interactively (for development
 * and testing) we must avoid unwanted side-effects on other non-test nodes.
 * This is done simply by checking that the titles start with 'API TEST'.
 *
 * @group scheduler_api_test
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // The values in the arrays give the function names below.
    // These six events are the originals, dispatched for Nodes.
    $events[SchedulerEvents::PRE_PUBLISH][] = ['apiTestNodePrePublish'];
    $events[SchedulerEvents::PUBLISH][] = ['apiTestNodePublish'];
    $events[SchedulerEvents::PRE_UNPUBLISH][] = ['apiTestNodePreUnpublish'];
    $events[SchedulerEvents::UNPUBLISH][] = ['apiTestNodeUnpublish'];
    $events[SchedulerEvents::PRE_PUBLISH_IMMEDIATELY][] = ['apiTestNodePrePublishImmediately'];
    $events[SchedulerEvents::PUBLISH_IMMEDIATELY][] = ['apiTestNodePublishImmediately'];

    // These six events are dispatched for Media entity types only.
    $events[SchedulerMediaEvents::PRE_PUBLISH][] = ['apiTestMediaPrePublish'];
    $events[SchedulerMediaEvents::PUBLISH][] = ['apiTestMediaPublish'];
    $events[SchedulerMediaEvents::PRE_UNPUBLISH][] = ['apiTestMediaPreUnpublish'];
    $events[SchedulerMediaEvents::UNPUBLISH][] = ['apiTestMediaUnpublish'];
    $events[SchedulerMediaEvents::PRE_PUBLISH_IMMEDIATELY][] = ['apiTestMediaPrePublishImmediately'];
    $events[SchedulerMediaEvents::PUBLISH_IMMEDIATELY][] = ['apiTestMediaPublishImmediately'];

    return $events;
  }

  /**
   * Operations to perform before Scheduler publishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestNodePrePublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // Before publishing a node make it sticky.
    if (!$node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setSticky(TRUE);
      $event->setNode($node);
    }
  }

  /**
   * Operations to perform after Scheduler publishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestNodePublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // After publishing a node promote it to the front page.
    if ($node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setPromoted(TRUE);
      $event->setNode($node);
    }
  }

  /**
   * Operations to perform before Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestNodePreUnpublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // Before unpublishing a node make it unsticky.
    if ($node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setSticky(FALSE);
      $event->setNode($node);
    }
  }

  /**
   * Operations to perform after Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestNodeUnpublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // After unpublishing a node remove it from the front page.
    if (!$node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setPromoted(FALSE);
      $event->setNode($node);
    }
  }

  /**
   * Operations before Scheduler publishes a node immediately not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestNodePrePublishImmediately(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // Before publishing immediately set the node to sticky.
    if (!$node->isPromoted() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setSticky(TRUE);
      $event->setNode($node);
    }
  }

  /**
   * Operations after Scheduler publishes a node immediately not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestNodePublishImmediately(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // After publishing immediately set the node to promoted and change the
    // title.
    if (!$node->isPromoted() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setTitle('Published immediately')
        ->setPromoted(TRUE);
      $event->setNode($node);
    }
  }

  /**
   * Operations to perform before Scheduler publishes a media item.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestMediaPrePublish(SchedulerEvent $event) {
    $entity = $event->getEntity();
    if (!$entity->isPublished() && strpos($entity->label(), 'API TEST MEDIA') === 0) {
      // Media entities do not have the 'sticky' and 'promote' fields. Instead
      // we can alter the name, for checking in the test.
      $entity->set('name', 'API TEST MEDIA - changed by "PRE_PUBLISH" event');
      $event->setEntity($entity);
    }
  }

  /**
   * Operations to perform after Scheduler publishes a media item.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestMediaPublish(SchedulerEvent $event) {
    $entity = $event->getEntity();
    // The name will be changed here only if it has already been changed in the
    // PRE_PUBLISH event function. This will show that both events worked.
    if ($entity->isPublished() && $entity->label() == 'API TEST MEDIA - changed by "PRE_PUBLISH" event') {
      $entity->set('name', 'API TEST MEDIA - altered a second time by "PUBLISH" event');
      $event->setEntity($entity);
    }
  }

  /**
   * Operations to perform before Scheduler unpublishes a media item.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestMediaPreUnpublish(SchedulerEvent $event) {
    $entity = $event->getEntity();
    if ($entity->isPublished() && strpos($entity->label(), 'API TEST MEDIA') === 0) {
      // Media entities do not have the 'sticky' and 'promote' fields. Instead
      // we can alter the name, for checking in the test.
      $entity->set('name', 'API TEST MEDIA - changed by "PRE_UNPUBLISH" event');
      $event->setEntity($entity);
    }
  }

  /**
   * Operations to perform after Scheduler unpublishes a media item.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestMediaUnpublish(SchedulerEvent $event) {
    $entity = $event->getEntity();
    // The name will be changed here only if it has already been changed in the
    // PRE_UNPUBLISH event function. This will show that both events worked.
    if (!$entity->isPublished() && $entity->label() == 'API TEST MEDIA - changed by "PRE_UNPUBLISH" event') {
      $entity->set('name', 'API TEST MEDIA - altered a second time by "UNPUBLISH" event');
      $event->setEntity($entity);
    }
  }

  /**
   * Operations before Scheduler publishes a media immediately not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestMediaPrePublishImmediately(SchedulerEvent $event) {
    $entity = $event->getEntity();
    if (!$entity->isPublished() && strpos($entity->label(), 'API TEST MEDIA') === 0) {
      $entity->set('name', 'API TEST MEDIA - changed by "PRE_PUBLISH_IMMEDIATELY" event');
      $event->setEntity($entity);
    }
  }

  /**
   * Operations after Scheduler publishes a media immediately not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestMediaPublishImmediately(SchedulerEvent $event) {
    $entity = $event->getEntity();
    // The name will be changed here only if it has already been changed in the
    // PRE_PUBLISH_IMMEDIATELY event function, to show that both events worked.
    if ($entity->label() == 'API TEST MEDIA - changed by "PRE_PUBLISH_IMMEDIATELY" event') {
      $entity->set('name', 'API TEST MEDIA - altered a second time by "PUBLISH_IMMEDIATELY" event');
      $event->setEntity($entity);
    }
  }

}
