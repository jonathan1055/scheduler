<?php

/**
 * @file
 * Drush commands for Scheduler.
 */

/**
 * Implements hook_drush_command().
 */
function scheduler_drush_command() {
  $items = [];

  $items['scheduler-cron'] = [
    'description' => 'Lightweight cron to process scheduler tasks.',
    'core' => ['8+'],
    'aliases' => ['sch-cron'],
    'category' => 'scheduler',
    'options' => [
      'nomsg' => 'to avoid the "cron completed" message being written to the terminal.',
    ],
  ];

  $items['scheduler-update-entities'] = [
    'description' => 'Add [un]publish_on fields to entities.',
    'core' => ['8+'],
    'aliases' => ['sch-updent'],
    'category' => 'scheduler',
    'options' => [
      'nomsg' => 'to avoid the "cron completed" message being written to the terminal.',
    ],
  ];
  return $items;
}

function drush_scheduler_update_entities() {
  \Drupal::service('scheduler.manager')->updateEntities();
}

/**
 * Run lightweight scheduler cron.
 */
function drush_scheduler_cron() {
  \Drupal::service('scheduler.manager')->runLightweightCron();
  $nomsg = drush_get_option('nomsg', NULL);
  $nomsg ? NULL : \Drupal::messenger()->addMessage(t('Scheduler lightweight cron completed'));
}
