<?php

/**
 * @file
 * Scheduler rules functions.
 */

use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Implements hook_rules_event_info().
 */
function scheduler_rules_event_info() {
  // This hook function defines four Scheduler events which can be used by Rules
  // to trigger other actions.

}

/**
 * Implements hook_rules_action_info().
 */
function scheduler_rules_action_info() {
  // These actions can be added when building a reaction rule. Use a $defaults
  // array to avoid duplication.
  $defaults = array(
    'parameter' => array(
      'node' => array(
        'type' => 'node',
        // 'label' is shown as fieldset title after adding action.
        'label' => t('The node to be processed by Scheduler'),
        // 'description' is shown beneath the label during add/edit.
        'description' => t('This can be a node object or node id'),
      ),
    ),
    'group' => t('Scheduler'),
  );

  // 1. Action to set the publishing date.
  $actions['scheduler_set_publish_date_action'] = array('label' => t('Set publishing date')) + $defaults;
  $actions['scheduler_set_publish_date_action']['parameter']['date'] = array(
    'type' => 'date',
    'label' => t('The date for publishing'),
    'description' => t('The date when Scheduler will publish the node'),
  );

  // 2. Action to set the unpublishing date.
  $actions['scheduler_set_unpublish_date_action'] = array('label' => t('Set unpublishing date')) + $defaults;
  $actions['scheduler_set_unpublish_date_action']['parameter']['date'] = array(
    'type' => 'date',
    'label' => t('The date for unpublishing'),
    'description' => t('The date when Scheduler will unpublish the node'),
  );

  // 3. Action to remove the publishing date.
  $actions['scheduler_remove_publish_date_action'] = array('label' => t('Remove publishing date')) + $defaults;

  // 4. Action to remove the unpublishing date.
  $actions['scheduler_remove_unpublish_date_action'] = array('label' => t('Remove unpublishing date')) + $defaults;

  return $actions;
}

/**
 * Set the publish_on date for the node.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node object to be scheduled for publishing.
 * @param int $date
 *   The date for publishing, a unix timestamp integer.
 */
function scheduler_set_publish_date_action(Node $node, $date) {
  // When this action is invoked and it operates on the node being editted then
  // hook_node_presave() and hook_node_update() will be executed anyway. But if
  // this action is being used to schedule a different node then we need to call
  // the functions directly here.
  $default_publish_enable = \Drupal::config('scheduler.settings')->get('default_publish_enable');
  if ($node->type->entity->getThirdPartySetting('scheduler', 'publish_enable', $default_publish_enable)) {
    $node->publish_on->value = $date;
    scheduler_node_presave($node);
    scheduler_node_update($node);
  }
  else {
    $type_name = node_get_type_label($node);

    \Drupal::logger('scheduler')->warning('Scheduled publishing is not enabled for %type content. To prevent this message add the condition "Scheduled publishing is enabled" to your Rule, or enable the Scheduler options via the %type content type settings.', array('%type' => $type_name, 'link' => \Drupal::l(t('@type settings', array('@type' => $type_name)), new Url('entity.node_type.edit_form', ['node_type' => $node->getType()]))));
  }
}

/**
 * Set the unpublish_on date for the node.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node object to be scheduled for unpublishing.
 * @param int $date
 *   The date for unpublishing, a unix timestamp integer.
 */
function scheduler_set_unpublish_date_action(Node $node, $date) {
  $default_unpublish_enable = \Drupal::config('scheduler.settings')->get('default_unpublish_enable');
  if ($node->type->entity->getThirdPartySetting('scheduler', 'unpublish_enable', $default_unpublish_enable)) {
    $node->unpublish_on->value = $date;
    scheduler_node_presave($node);
    scheduler_node_update($node);
  }
  else {
    $type_name = node_get_type_label($node);

    $settings_link = \Drupal::l(t('@type settings', array('@type' => $type_name)), new Url('entity.node_type.edit_form', ['node_type' => $node->getType()]));

    \Drupal::logger('scheduler')->warning('Scheduled unpublishing is not enabled for %type content. To prevent this message add the condition "Scheduled unpublishing is enabled" to your Rule, or enable the Scheduler options via the %type content type settings.', array('%type' => $type_name, 'link' => $settings_link));
  }
}

/**
 * Remove the publish_on date for the node.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node object from which to remove the publish_on date.
 */
function scheduler_remove_publish_date_action(Node $node) {
  $default_publish_enable = \Drupal::config('scheduler.settings')->get('default_publish_enable');
  if ($node->type->entity->getThirdPartySetting('scheduler', 'publish_enable', $default_publish_enable)) {
    $node->publish_on->value = NULL;
    scheduler_node_presave($node);
    scheduler_node_update($node);
  }
  else {
    $type_name = node_get_type_label($node);

    $settings_link = \Drupal::l(t('@type settings', array('@type' => $type_name)), new Url('entity.node_type.edit_form', ['node_type' => $node->getType()]));

    \Drupal::logger('scheduler')->warning('Scheduled publishing is not enabled for %type content. To prevent this message add the condition "Scheduled publishing is enabled" to your Rule, or enable the Scheduler options via the %type content type settings.', array('%type' => $type_name, 'link' => $settings_link));
  }
}

/**
 * Remove the unpublish_on date for the node.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node object from which to remove the unpublish_on date.
 */
function scheduler_remove_unpublish_date_action(Node $node) {
  $default_unpublish_enable = \Drupal::config('scheduler.settings')->get('default_unpublish_enable');
  if ($node->type->entity->getThirdPartySetting('scheduler', 'unpublish_enable', $default_unpublish_enable)) {
    $node->unpublish_on->value = NULL;
    scheduler_node_presave($node);
    scheduler_node_update($node);
  }
  else {
    $type_name = node_get_type_label($node);

    $settings_link = \Drupal::l(t('@type settings', array('@type' => $type_name)), new Url('entity.node_type.edit_form', ['node_type' => $node->getType()]));

    \Drupal::logger('scheduler')->warning('Scheduled unpublishing is not enabled for %type content. To prevent this message add the condition "Scheduled unpublishing is enabled" to your Rule, or enable the Scheduler options via the %type content type settings.', array('%type' => $type_name, 'link' => $settings_link));
  }
}

/**
 * Implements hook_rules_condition_info().
 */
function scheduler_rules_condition_info() {
  // Create a default, as most of the values are identical for all conditions.
  $default = array(
    'group' => t('Scheduler'),
    'parameter' => array(
      'node' => array(
        'type' => 'node',
        'label' => t('The node to test for scheduling properties'),
        'description' => t('This can be a node object or node id'),
      ),
    ),
  );

  // 1. Condition to check if publishing is enabled for the content type.
  $conditions['scheduler_condition_publishing_is_enabled'] = array(
    'label' => t('Scheduled publishing is enabled for this content type')) + $default;

  // 2. Condition to check if unpublishing is enabled for the content type.
  $conditions['scheduler_condition_unpublishing_is_enabled'] = array(
    'label' => t('Scheduled unpublishing is enabled for this content type')) + $default;

  // 3. Condition to check if the node is scheduled for publishing.
  $conditions['scheduler_condition_node_is_scheduled_for_publishing'] = array(
    'label' => t('The node is scheduled for publishing')) + $default;

  // 4. Condition to check if the node is scheduled for unpublishing.
  $conditions['scheduler_condition_node_is_scheduled_for_unpublishing'] = array(
    'label' => t('The node is scheduled for unpublishing')) + $default;

  return $conditions;
}

/**
 * Determines whether scheduled publishing is enabled for this node type.
 *
 * @param \Drupal\node\Entity\Node $node
 *   A node object.
 * @return
 *   TRUE if scheduled publishing is enabled for the node type, FALSE if not.
 */
function scheduler_condition_publishing_is_enabled(Node $node) {
  $default_publish_enable = \Drupal::config('scheduler.settings')->get('default_publish_enable');
  return ($node->type->entity->getThirdPartySetting('scheduler', 'publish_enable', $default_publish_enable));
}

/**
 * Determines whether scheduled unpublishing is enabled for this node type.
 *
 * @param \Drupal\node\Entity\Node $node
 *   A node object.
 * @return
 *   TRUE if scheduled unpublishing is enabled for the node type, FALSE if not.
 */
function scheduler_condition_unpublishing_is_enabled(Node $node) {
  $default_unpublish_enable = \Drupal::config('scheduler.settings')->get('default_unpublish_enable');
  return ($node->type->entity->getThirdPartySetting('scheduler', 'unpublish_enable', $default_unpublish_enable));
}

/**
 * Determines whether a node is scheduled for publishing.
 *
 * @param \Drupal\node\Entity\Node $node
 *   A node object.
 * @return
 *   TRUE if the node is scheduled for publishing, FALSE if not.
 */
function scheduler_condition_node_is_scheduled_for_publishing(Node $node) {
  return !empty($node->publish_on->value);
}

/**
 * Determines whether a node is scheduled for unpublishing.
 *
 * @param \Drupal\node\Entity\Node $node
 *   A node object.
 * @return
 *   TRUE if the node is scheduled for unpublishing, FALSE if not.
 */
function scheduler_condition_node_is_scheduled_for_unpublishing(Node $node) {
  return !empty($node->unpublish_on->value);
}
