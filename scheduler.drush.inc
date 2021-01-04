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
  $entityUpdateManager = \Drupal::entityDefinitionUpdateManager();

  $list = $entityUpdateManager->getChangeList();
  foreach ($list as $entity_type_id => $definitions ) {
    if ($definitions['field_storage_definitions']['publish_on'] ?? 0 ) {
      $entity_type = $entityUpdateManager->getEntityType($entity_type_id);
      $fields = scheduler_entity_base_field_info($entity_type);
      foreach ($fields as $field_name => $field_definition) {
        $entityUpdateManager->installFieldStorageDefinition($field_name, $entity_type_id, $entity_type_id,  $field_definition);
      }
    }
  }
}

/**
 * Run lightweight scheduler cron.
 */
function drush_scheduler_cron() {
  \Drupal::service('scheduler.manager')->runLightweightCron();
  $nomsg = drush_get_option('nomsg', NULL);
  $nomsg ? NULL : \Drupal::messenger()->addMessage(t('Scheduler lightweight cron completed'));
}
