<?php

/**
 * @file
 * Drush 8 commands for Scheduler.
 *
 * For Drush 9+ the commands are in src/Commands/SchedulerCommands.php.
 */

/**
 * Implements hook_drush_command().
 */
function scheduler_drush_command() {
  $items = [];

  $items['scheduler-cron'] = [
    'description' => 'Lightweight cron to process Scheduler tasks.',
    'core' => ['8+'],
    'aliases' => ['sch-cron'],
    'category' => 'scheduler',
    'options' => [
      'nomsg' => 'to avoid the "cron completed" message being written to the terminal.',
    ],
  ];

  $items['scheduler-entity-update'] = [
    'description' => 'Entity Update - Add missing Scheduler db fields for entities covered by plugins.',
    'core' => ['8+'],
    'aliases' => ['sch-ent-upd'],
    'category' => 'scheduler',
  ];
  return $items;
}

/**
 * Run lightweight scheduler cron.
 */
function drush_scheduler_cron() {
  \Drupal::service('scheduler.manager')->runLightweightCron();
  $nomsg = drush_get_option('nomsg', NULL);
  $nomsg ? NULL : \Drupal::messenger()->addMessage(t('Scheduler lightweight cron completed'));
}

/**
 * Call SchedulerManager entityUpdate() to add missing db fields.
 */
function drush_scheduler_entity_update() {
  $result = \Drupal::service('scheduler.manager')->entityUpdate();
  $updated = $result ? implode(', ', $result) : t('nothing to update');
  \Drupal::messenger()->addMessage(t('Scheduler entity update - @updated', ['@updated' => $updated]));
}
