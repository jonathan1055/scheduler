<?php

/**
 * @file
 * Scheduler cron functions.
 *
 * This file is included only when running a crontab job or executing the
 * lightweight cron via the admin interface.
 */

use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Declare exception classes.
 */
class SchedulerNodeTypeNotEnabledException extends Exception {}
class SchedulerMissingDateException extends Exception {}

/**
 * Publish scheduled nodes.
 *
 * @return bool
 *   TRUE if any node has been published, FALSE otherwise.
 */
function _scheduler_publish() {
  $date_formatter = \Drupal::service('date.formatter');
  $result = FALSE;

  // If the time now is greater than the time to publish a node, publish it.
  $query = \Drupal::entityQuery('node')
    ->condition('publish_on', 0, '>')
    ->condition('publish_on', REQUEST_TIME, '<=');
  // @todo Change this query to exclude nodes which are not enabled for
  // publishing. See https://www.drupal.org/node/2659824
  $nids = $query->execute();

  $action = 'publish';

  // Allow other modules to add to the list of nodes to be published.
  $nids = array_unique(array_merge($nids, _scheduler_scheduler_nid_list($action)));

  // Allow other modules to alter the list of nodes to be published.
  \Drupal::moduleHandler()->alter('scheduler_nid_list', $nids, $action);

  $logger = \Drupal::logger('scheduler');
  // @todo Use dependency injection for the logger when we have written the new
  // service for scheduler API. See https://www.drupal.org/node/2651338
  $nodes = Node::loadMultiple($nids);
  foreach ($nodes as $nid => $node) {
    // The API calls could return nodes of types which are not enabled for
    // scheduled publishing. Do not process these.
    if (!$node->type->entity->getThirdPartySetting('scheduler', 'publish_enable', SCHEDULER_DEFAULT_PUBLISH_ENABLE)) {
      throw new SchedulerNodeTypeNotEnabledException(sprintf("Node %d '%s' will not be published because node type '%s' is not enabled for scheduled publishing", $node->id(), $node->getTitle(), node_get_type_label($node)));
      continue;
    }

    // Check that other modules allow the action on this node.
    if (!_scheduler_allow($node, $action)) {
      continue;
    }

    // Invoke Scheduler API for modules to react before the node is published.
    _scheduler_scheduler_api($node, 'pre_' . $action);

    // If an API call has removed the date $node->set('changed', $publish_on)
    // would fail, so trap this exception here and give a meaningful message.
    if (empty($node->publish_on->value)) {
      $field_definitions = \Drupal::entityManager()->getFieldDefinitions('node', $node->getType());
      $field = (string)$field_definitions['publish_on']->getLabel();
      throw new SchedulerMissingDateException(sprintf("Node %d '%s' will not be published because field '%s' has no value", $node->id(), $node->getTitle(), $field));
      continue;
    }

    // Update timestamps.
    $publish_on = $node->publish_on->value;
    $node->set('changed', $publish_on);
    $old_creation_date = $node->getCreatedTime();
    if ($node->type->entity->getThirdPartySetting('scheduler', 'publish_touch', SCHEDULER_DEFAULT_PUBLISH_TOUCH)) {
      $node->setCreatedTime($publish_on);
    }

    $create_publishing_revision = $node->type->entity->getThirdPartySetting('scheduler', 'publish_revision', SCHEDULER_DEFAULT_PUBLISH_REVISION);
    if ($create_publishing_revision) {
      $node->setNewRevision();
      // Use a core date format to guarantee a time is included.
      $node->revision_log = t('Node published by Scheduler on @now. Previous creation date was @date.', array(
        '@now' => $date_formatter->format(REQUEST_TIME, 'short'),
        '@date' => $date_formatter->format($old_creation_date, 'short'),
      ));
    }
    // Unset publish_on so the node will not get rescheduled by subsequent calls
    // to $node->save().
    $node->publish_on->value = NULL;

    // Log the fact that a scheduled publication is about to take place.
    $view_link = $node->link(t('View node'));
    $nodetype_url = Url::fromRoute('entity.node_type.edit_form', array('node_type' => $node->getType()));
    $nodetype_link = \Drupal::l(node_get_type_label($node) . ' ' . t('settings'), $nodetype_url);
    $logger_variables = array(
      '@type' => node_get_type_label($node),
      '%title' => $node->getTitle(),
      'link' => $nodetype_link . ' ' . $view_link,
    );
    $logger->notice('@type: scheduled publishing of %title.', $logger_variables);

    // Use the actions system to publish the node.
    \Drupal::entityManager()->getStorage('action')->load('node_publish_action')->getPlugin()->execute($node);

    // Invoke the event to tell Rules that Scheduler has published this node.
    if (\Drupal::moduleHandler()->moduleExists('rules')) {
      /*
      TEMP remove call to undefined function rules_invoke_event until converted.
      @see https://www.drupal.org/node/2651348
      rules_invoke_event('scheduler_node_has_been_published_event', $node, $publish_on, $node->unpublish_on->value);
      */
    }

    // Invoke scheduler API for modules to react after the node is published.
    _scheduler_scheduler_api($node, $action);

    $result = TRUE;
  }

  return $result;
}

/**
 * Unpublish scheduled nodes.
 *
 * @return bool
 *   TRUE is any node has been unpublished, FALSE otherwise.
 */
function _scheduler_unpublish() {
  $date_formatter = \Drupal::service('date.formatter');
  $result = FALSE;

  // If the time is greater than the time to unpublish a node, unpublish it.
  $query = \Drupal::entityQuery('node')
    ->condition('unpublish_on', 0, '>')
    ->condition('unpublish_on', REQUEST_TIME, '<=');
  // @todo Change this query to exclude nodes which are not enabled for
  // unpublishing. See https://www.drupal.org/node/2659824
  $nids = $query->execute();

  $action = 'unpublish';

  // Allow other modules to add to the list of nodes to be unpublished.
  $nids = array_unique(array_merge($nids, _scheduler_scheduler_nid_list($action)));

  // Allow other modules to alter the list of nodes to be unpublished.
  \Drupal::moduleHandler()->alter('scheduler_nid_list', $nids, $action);

  $logger = \Drupal::logger('scheduler');
  // @todo Use dependency injection for the logger when we have written the new
  // service for scheduler API. See https://www.drupal.org/node/2651338
  $nodes = Node::loadMultiple($nids);
  foreach ($nodes as $nid => $node) {
    // The API calls could return nodes of types which are not enabled for
    // scheduled unpublishing. Do not process these.
    if (!$node->type->entity->getThirdPartySetting('scheduler', 'unpublish_enable', SCHEDULER_DEFAULT_UNPUBLISH_ENABLE)) {
      throw new SchedulerNodeTypeNotEnabledException(sprintf("Node %d '%s' will not be unpublished because node type '%s' is not enabled for scheduled unpublishing", $node->id(), $node->getTitle(), node_get_type_label($node)));
      continue;
    }

    // Check that other modules allow the action on this node.
    if (!_scheduler_allow($node, $action)) {
      continue;
    }

    // Do not process the node if it has a publish_on time which is in the past,
    // as this implies that scheduled publishing has been blocked by one of the
    // API functions we provide. Hence unpublishing should be halted too.
    $publish_on = $node->publish_on->value;
    if (!empty($publish_on) && $publish_on <= REQUEST_TIME) {
      continue;
    }

    // Invoke scheduler API for modules to react before the node is unpublished.
    _scheduler_scheduler_api($node, 'pre_' . $action);

    // If an API call has removed the date $node->set('changed', $unpublish_on)
    // would fail, so trap this exception here and give a meaningful message.
    if (empty($node->unpublish_on->value)) {
      $field_definitions = \Drupal::entityManager()->getFieldDefinitions('node', $node->getType());
      $field = (string)$field_definitions['unpublish_on']->getLabel();
      throw new SchedulerMissingDateException(sprintf("Node %d '%s' will not be unpublished because field '%s' has no value", $node->id(), $node->getTitle(), $field));
      continue;
    }

    // Update timestamps.
    $old_change_date = $node->getChangedTime();
    $unpublish_on = $node->unpublish_on->value;
    $node->set('changed', $unpublish_on);

    $create_unpublishing_revision = $node->type->entity->getThirdPartySetting('scheduler', 'unpublish_revision', SCHEDULER_DEFAULT_UNPUBLISH_REVISION);
    if ($create_unpublishing_revision) {
      $node->setNewRevision();
      // Use a core date format to guarantee a time is included.
      $node->revision_log = t('Node unpublished by Scheduler on @now. Previous change date was @date.', array(
        '@now' => $date_formatter->format(REQUEST_TIME, 'short'),
        '@date' => $date_formatter->format($old_change_date, 'short'),
      ));
    }
    // Unset unpublish_on so the node will not get rescheduled by subsequent
    // calls to $node->save(). Save the value for use when calling Rules.
    $node->unpublish_on->value = NULL;

    // Log the fact that a scheduled unpublication is about to take place.
    $view_link = $node->link(t('View node'));
    $nodetype_url = Url::fromRoute('entity.node_type.edit_form', array('node_type' => $node->getType()));
    $nodetype_link = \Drupal::l(node_get_type_label($node) . ' ' . t('settings'), $nodetype_url);
    $logger_variables = array(
      '@type' => node_get_type_label($node),
      '%title' => $node->getTitle(),
      'link' => $nodetype_link . ' ' . $view_link,
    );
    $logger->notice('@type: scheduled unpublishing of %title.', $logger_variables);

    // Use the actions system to publish the node.
    \Drupal::entityManager()->getStorage('action')->load('node_unpublish_action')->getPlugin()->execute($node);

    // Invoke event to tell Rules that Scheduler has unpublished this node.
    if (\Drupal::moduleHandler()->moduleExists('rules')) {
      /*
      TEMP remove call to undefined function rules_invoke_event until converted.
      @see https://www.drupal.org/node/2651348
      rules_invoke_event('scheduler_node_has_been_unpublished_event', $node, $node->publish_on, $unpublish_on);
      */
    }

    // Invoke scheduler API for modules to react after the node is unpublished.
    _scheduler_scheduler_api($node, $action);

    $result = TRUE;
  }

  return $result;
}

/**
 * Gather node IDs for all nodes that need to be $action'ed.
 *
 * @param string $action
 *   The action being performed, either "publish" or "unpublish".
 *
 * @return array
 *   An array of node ids.
 */
function _scheduler_scheduler_nid_list($action) {
  $nids = array();

  foreach (\Drupal::moduleHandler()->getImplementations('scheduler_nid_list') as $module) {
    $function = $module . '_scheduler_nid_list';
    $nids = array_merge($nids, $function($action));
  }

  return $nids;
}

/**
 * Run the lightweight cron.
 *
 * The Scheduler part of the processing performed here is the same as in the
 * normal Drupal cron run. The difference is that only scheduler_cron() is
 * executed, no other modules hook_cron() functions are called.
 *
 * This function is called from the external crontab job via url /scheduler/cron
 * or it can be run interactively from the Scheduler configuration page at
 * /admin/config/content/scheduler/cron.
 */
function _scheduler_run_cron() {
  $log = \Drupal::config('scheduler.settings')->get('log');
  if ($log) {
    \Drupal::logger('scheduler')->notice('Lightweight cron run activated.');
  }
  scheduler_cron();
  if (ob_get_level() > 0) {
    $handlers = ob_list_handlers();
    if (isset($handlers[0]) && $handlers[0] == 'default output handler') {
      ob_clean();
    }
  }
  if ($log) {
    \Drupal::logger('scheduler')->notice('Lightweight cron run completed.', array('link' => \Drupal::l(t('settings'), Url::fromRoute('scheduler.cron_form'))));
  }
}
