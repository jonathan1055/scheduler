<?php

/**
 * @file
 * Scheduler node edit functions.
 *
 * This file is included only when a node is being edited by a user who has
 * permission to schedule nodes, and the node-type is enabled for scheduling.
 */

/**
 * Helper function that does all the work for the real hook_form_alter().
 *
 * @see scheduler_form_alter()
 */
function _scheduler_form_alter(&$form, $form_state) {
  $publishing_enabled = variable_get('scheduler_publish_enable_' . $form['type']['#value'], 0) == 1;
  $unpublishing_enabled = variable_get('scheduler_unpublish_enable_' . $form['type']['#value'], 0) == 1;
  $date_format = \Drupal::config('scheduler.settings')->get('date_format');
  $date_only_format = \Drupal::config('scheduler.settings')->get('date_only_format');
  $time_only_format = \Drupal::config('scheduler.settings')->get('time_only_format');
  $date_only_allowed = \Drupal::config('scheduler.settings')->get('allow_date_only');
  $node = $form['#node'];

  // If this is a preview then get the values from the form, not the
  // database.
  if (isset($form_state['values']['op']) && $form_state['values']['op'] == t('Preview')) {
    $defaults = new StdClass;
    $defaults->publish_on = $publishing_enabled ? $form_state['values']['publish_on'] : NULL;
    $defaults->unpublish_on = $unpublishing_enabled ? $form_state['values']['unpublish_on'] : NULL;
  }
  elseif (isset($node->nid) && $node->nid > 0) {
    // Load the values from the database if we are viewing an existing node.
    $query = db_select('scheduler', 's');
    $query->fields('s', array('publish_on', 'unpublish_on'));
    $query->condition('s.nid', $node->nid, '=');
    $defaults = $query->execute()->fetchObject();
  }
  else {
    // Initialise standard values.
    $defaults = new StdClass;
    // Respect presets added by functions like
    // scheduler_field_attach_prepare_translation_alter().
    $defaults->publish_on = isset($node->publish_on) ? $node->publish_on : NULL;
    $defaults->unpublish_on = isset($node->unpublish_on) ? $node->unpublish_on : NULL;
  }

  // If there is a text value then convert it to a Unix timestamp.
  if (isset($defaults->publish_on) && $defaults->publish_on && !is_numeric($defaults->publish_on)) {
    $defaults->publish_on = _scheduler_strtotime($defaults->publish_on);
  }
  if (isset($defaults->unpublish_on) && $defaults->unpublish_on && !is_numeric($defaults->unpublish_on)) {
    $defaults->unpublish_on = _scheduler_strtotime($defaults->unpublish_on);
  }

  // A publish_on date is required if the content type option is set and the
  // node is being created or it currently has a scheduled publishing date.
  $publishing_required = variable_get('scheduler_publish_required_' . $form['type']['#value'], 0) == 1
    && (empty($node->nid) || ($node->status == 0 && !empty($node->publish_on)));

  // An unpublish_on date is required if the content type option is set and
  // the node is being created or the current status is published or the
  // node is scheduled to be published.
  $unpublishing_required = variable_get('scheduler_unpublish_required_' . $form['type']['#value'], 0) == 1
    && (empty($node->nid) || $node->status == 1 || !empty($node->publish_on));

  $use_vertical_tabs = variable_get('scheduler_use_vertical_tabs_' . $form['type']['#value'], 1);
  $fieldset_extended = (
    (isset($defaults->publish_on) && $defaults->publish_on != 0)
    || (isset($defaults->unpublish_on) && $defaults->unpublish_on != 0)
    || $publishing_required
    || $unpublishing_required
    || variable_get('scheduler_expand_fieldset_' . $form['type']['#value'], 0)
  );

  $form['scheduler_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Scheduling options'),
    '#collapsible' => TRUE,
    '#collapsed' => !$fieldset_extended,
    '#weight' => 35,
    '#group' => $use_vertical_tabs ? 'additional_settings' : FALSE,
  );
  // Attach the javascript for the vertical tabs.
  if ($use_vertical_tabs) {
    $form['scheduler_settings']['#attached']['js'][] = drupal_get_path('module', 'scheduler') . '/scheduler_vertical_tabs.js';
  }

  // Define the descriptions depending on whether the time can be skipped.
  $descriptions = array();
  if ($date_only_allowed && ($date_only_format != $date_format)) {
    $descriptions['format'] = t('Format: %date_only_format or %standard_format.', array(
      '%date_only_format' => format_date(time(), 'custom', $date_only_format),
      '%standard_format' => format_date(time(), 'custom', $date_format),
    ));
  }
  else {
    $descriptions['format'] = t('Format: %standard_format.', array(
      '%standard_format' => format_date(time(), 'custom', $date_format),
    ));
  }
  // Show the default time so users know what they will get if they do not
  // enter a time.
  if ($date_only_allowed) {
    $default_time = strtotime(\Drupal::config('scheduler.settings')->get('default_time'));
    $descriptions['default'] = t('The default time is @default_time.', array(
      '@default_time' => format_date($default_time, 'custom', $time_only_format ? $time_only_format : SCHEDULER_TIME_ONLY_FORMAT),
    ));
  }

  if ($publishing_enabled) {
    if (!$publishing_required) {
      $descriptions['blank'] = t('Leave the date blank for no scheduled publishing.');
    }

    $form['scheduler_settings']['publish_on'] = array(
      '#type' => 'textfield',
      '#title' => t('Publish on'),
      '#maxlength' => 30,
      '#required' => $publishing_required,
      '#default_value' => isset($defaults->publish_on) && $defaults->publish_on ? format_date($defaults->publish_on, 'custom', $date_format) : '',
      '#description' => filter_xss(implode(' ', $descriptions)),
      '#value_callback' => 'scheduler_date_value_callback',
    );
  }

  if ($unpublishing_enabled) {
    if (!$unpublishing_required) {
      $descriptions['blank'] = t('Leave the date blank for no scheduled unpublishing.');
    }
    else {
      unset($descriptions['blank']);
    }
    $form['scheduler_settings']['unpublish_on'] = array(
      '#type' => 'textfield',
      '#title' => t('Unpublish on'),
      '#maxlength' => 30,
      '#required' => $unpublishing_required,
      '#default_value' => isset($defaults->unpublish_on) && $defaults->unpublish_on ? format_date($defaults->unpublish_on, 'custom', $date_format) : '',
      '#description' => filter_xss(implode(' ', $descriptions)),
      '#value_callback' => 'scheduler_date_value_callback',
    );
  }
}

/**
 * Callback function for the Scheduler date entry elements.
 */
function scheduler_date_value_callback(&$element, $input = FALSE, &$form_state) {
  // When processing a delete operation the user should not be forced to enter a
  // date. Hence set the scheduler date element's #required attribute to FALSE.
  // Test the input operation against $form_state['values']['delete'] as this
  // will match the value of the Delete button even if translated.
  if (isset($form_state['input']['op']) && isset($form_state['values']['delete']) && $form_state['input']['op'] == $form_state['values']['delete']) {
    $element['#required'] = FALSE;
  }
}