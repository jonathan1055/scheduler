<?php

/**
 * @file
 * Drush commands for Scheduler
 */

/**
 * Implements hook_drush_command().
 */
function scheduler_drush_command() {
  $items = array();

  $items['scheduler-cron'] = array(
    'description' => 'Lighweight cron to process scheduler tasks.',
    'core' => array('8+'),
    'aliases' => array('sch-cron'),
    'category' => 'scheduler',
    'options' => array(
      'nomsg' => 'to avoid the "cron completed" message being written to the terminal.',
    ),
  );

  return $items;
}

/**
 * Run lighweight scheduler cron.
 */
function drush_scheduler_cron() {
  \Drupal::service('scheduler.manager')->runCron();
  $nomsg = drush_get_option('nomsg', NULL);
  $nomsg ? NULL : drupal_set_message(t('Scheduler lightweight cron completed'));
}
