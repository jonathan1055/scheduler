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
    ddm('== scheduler_repeat EventSubscriber::prePublish == ' . format_date(REQUEST_TIME, 'medium'));
    /** @var Node $node */
    $node = $event->getNode();
    ddm('$node ' . $node->id() . ' "' . $node->title->value . '", publish_on ' . format_date($node->publish_on->value, 'medium'));

    // THIS IS ALL TEMPORARY. Values will be set in node edit form.
    if (!$repeater = _scheduler_repeat_get_repeater($node)) {
      ddm('no repeater found, so exiting');
      return 0;
    }

    // @todo We do need this.
    // try {
    //   _scheduler_repeat_set_snapshot_of_scheduling_timestamps($node);
    //   $event->setNode($node);
    // } catch (\Exception $e) {
    //   _scheduler_repeat_log_warning('Could not set scheduling snapshot: @message', ['@message' => $e->getMessage()]);
    // }

    // $node->get('repeat')->next_unpublish_on
    $repeater->setNextPublishOn($node->publish_on->value); // use existing function name, will change.
    $repeater->setNextUnpublishOn($node->unpublish_on->value);
    
    // @todo Check what this does. Verifies that values above are set.
    if (!$repeater->shouldRepeat()) {
      ddm('should not repeat, so exiting');
      return 0;
    }
    // ddm($node->get('repeat'), '$node->get(repeat)'); // big.

    $next_publish_on = $repeater->calculateNextPublishedOn($repeater->getNextPublishOn());
    $next_unpublish_on = $repeater->calculateNextUnpublishedOn($repeater->getNextUnpublishOn());
    ddm('initial next_publish_on = ' . $next_publish_on . ' = ' . format_date($next_publish_on, 'medium'));
    ddm('initial next_unpublish_on = ' . $next_unpublish_on . ' = ' . format_date($next_unpublish_on, 'medium'));
    
    $request_time = \Drupal::time()->getRequestTime();
    while ($next_publish_on < $request_time) {
      $next_publish_on = $repeater->calculateNextPublishedOn($next_publish_on);
      $next_unpublish_on = $repeater->calculateNextUnpublishedOn($next_unpublish_on);
    }
    ddm('final next_publish_on = ' . $next_publish_on . ' = ' . format_date($next_publish_on, 'medium'));
    ddm('final next_unpublish_on = ' . $next_unpublish_on . ' = ' . format_date($next_unpublish_on, 'medium'));

    $node->set('repeat', [
      'plugin_id' => $node->repeat->plugin_id,
      'next_publish_on' => $next_publish_on,
      'next_unpublish_on' => $next_unpublish_on,
    ]);
    $event->setNode($node);

  }

  /**
   * Operations to perform after Scheduler publishes a node via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function publish(SchedulerEvent $event) {
    // ddm('== scheduler_repeat EventSubscriber::publish == ' . format_date(REQUEST_TIME, 'medium'));
    
  }

  /**
   * Operations to perform before Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function preUnpublish(SchedulerEvent $event) {
    // ddm('== scheduler_repeat EventSubscriber::preUnpublish == ' . format_date(REQUEST_TIME, 'medium'));
  }

  /**
   * Operations to perform after Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function unpublish(SchedulerEvent $event) {
    ddm('== scheduler_repeat EventSubscriber::unpublish == ' . format_date(REQUEST_TIME, 'medium'));
    /** @var Node $node */
    $node = $event->getNode();

    if (!$repeater = _scheduler_repeat_get_repeater($node)) {
      ddm('should not repeat, so exiting');
      return 0;
    }

  // ddm($node->get('repeat'), '$node->get(repeat)'); // big.
  $next_publish_on = $node->get('repeat')->next_publish_on;
  $next_unpublish_on = $node->get('repeat')->next_unpublish_on;
  ddm($next_publish_on, 'recovered next_publish_on');
  ddm($next_unpublish_on, 'recovered next_unpublish_on');
  ddm('recovered next_publish_on = ' . $next_publish_on . ' = ' . format_date($next_publish_on, 'medium'));
  ddm('recovered next_unpublish_on = ' . $next_unpublish_on . ' = ' . format_date($next_unpublish_on, 'medium'));

  $node->set('publish_on', $next_publish_on);
  $node->set('unpublish_on', $next_unpublish_on);
  // _scheduler_repeat_clear_snapshot_of_scheduling_timestamps($node);

  // @todo Calculate and store next publish_on and unpublish_on here.

  $event->setNode($node);
  }

  /**
   * Operations to perform before Scheduler publishes a node immediately not via
   * cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function prePublishImmediately(SchedulerEvent $event) {
    // ddm('== scheduler_repeat EventSubscriber::prePublishImmediately == ' . format_date(REQUEST_TIME, 'medium'));
  }

  /**
   * Operations to perform after Scheduler publishes a node immediately not via
   * cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   */
  public function publishImmediately(SchedulerEvent $event) {
    // ddm('== scheduler_repeat EventSubscriber::publishImmediately == ' . format_date(REQUEST_TIME, 'medium'));
  }

}
