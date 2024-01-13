<?php

/**
 * @file
 * Builds placeholder replacement tokens for node scheduler data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Internal function to return the token type ids for supported entity types.
 */
function _scheduler_token_types() {
  static $token_types;
  if (!empty($token_types)) {
    return $token_types;
  }
  $plugin_types = \Drupal::service('scheduler.manager')->getPluginEntityTypes();
  // This function should only get executed if the Token module is enabled,
  // therefore we can assume the token.entity_mapper service exists without
  // having to check for it.
  foreach ($plugin_types as $type) {
    $token_type = \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($type, TRUE);
    $token_types[] = $token_type;
  }
  return $token_types;
}

/**
 * Implements hook_token_info().
 */
function scheduler_token_info() {
  // Initialise the array to avoid 'variable is undefined' phpcs error.
  $info = [];
  foreach (_scheduler_token_types() as $type) {
    $info['tokens'][$type]['scheduler-publish'] = [
      'name' => t('Publish on date'),
      'description' => t("The date the %type will be published.", ['%type' => $type]),
      'type' => 'date',
    ];
    $info['tokens'][$type]['scheduler-unpublish'] = [
      'name' => t('Unpublish on date'),
      'description' => t("The date the %type will be unpublished.", ['%type' => $type]),
      'type' => 'date',
    ];
  }
  return $info;
}

/**
 * Implements hook_tokens().
 */
function scheduler_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();
  $date_formatter = \Drupal::service('date.formatter');
  $language_code = $options['langcode'] ?? NULL;
  $replacements = [];

  if (in_array($type, _scheduler_token_types()) && !empty($data[$type])) {
    $entity = $data[$type];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'scheduler-publish':
          if (isset($entity->publish_on->value)) {
            $replacements[$original] = $date_formatter->format($entity->publish_on->value, 'medium', '', NULL, $language_code);
          }
          break;

        case 'scheduler-unpublish':
          if (isset($entity->unpublish_on->value)) {
            $replacements[$original] = $date_formatter->format($entity->unpublish_on->value, 'medium', '', NULL, $language_code);
          }
          break;
      }
    }

    // Chained token replacement.
    if (isset($entity->publish_on->value) && $publish_tokens = $token_service->findWithPrefix($tokens, 'scheduler-publish')) {
      $replacements += $token_service->generate('date', $publish_tokens, ['date' => $entity->publish_on->value], $options, $bubbleable_metadata);
    }
    if (isset($entity->unpublish_on->value) && $unpublish_tokens = $token_service->findWithPrefix($tokens, 'scheduler-unpublish')) {
      $replacements += $token_service->generate('date', $unpublish_tokens, ['date' => $entity->unpublish_on->value], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
