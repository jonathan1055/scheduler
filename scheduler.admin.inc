<?php

/**
 * @file
 * Administration forms for the Scheduler module.
 */

/**
 * Helper function for the real hook_form_node_type_form_alter().
 *
 * @see scheduler_form_node_type_form_alter()
 */
function _scheduler_form_node_type_form_alter(&$form, $form_state) {
  /** @var \Drupal\node\NodeTypeInterface $type */
  $type = $form_state->getFormObject()->getEntity();

  drupal_add_css(drupal_get_path('module', 'scheduler') . '/scheduler.css');
  $form['scheduler'] = array(
    '#type' => 'fieldset',
    '#title' => t('Scheduler'),
    '#weight' => 35,
    '#group' => 'additional_settings',
    '#attached' => array(
      'js' => array(
        'vertical-tabs' => drupal_get_path('module', 'scheduler') . "/scheduler_vertical_tabs.js",
      ),
    ),
  );

  $form['scheduler']['publish'] = array(
    '#type' => 'fieldset',
    '#title' => t('Publishing'),
    '#collapsible' => FALSE,
    '#weight' => 1,
    '#group' => 'scheduler',
  );
  $form['scheduler']['publish']['scheduler_publish_enable'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable scheduled publishing for this content type'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'publish_enable', FALSE),
  );
  $form['scheduler']['publish']['scheduler_publish_touch'] = array(
    '#type' => 'checkbox',
    '#title' => t('Change content creation time to match the scheduled publish time'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'publish_touch', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_publish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['scheduler']['publish']['scheduler_publish_required'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require scheduled publishing'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'publish_required', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_publish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['scheduler']['publish']['scheduler_publish_revision'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create a new revision on publishing'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'publish_revision', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_publish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['scheduler']['publish']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced options'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_publish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['scheduler']['publish']['advanced']['scheduler_publish_past_date'] = array(
    '#type' => 'radios',
    '#title' => t('Action to be taken for publication dates in the past'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'publish_past_date', 'error'),
    '#options' => array(
      'error' => t('Display an error message - do not allow dates in the past'),
      'publish' => t('Publish the content immediately after saving'),
      'schedule' => t('Schedule the content for publication on the next cron run'),
    ),
  );
  $form['scheduler']['unpublish'] = array(
    '#type' => 'fieldset',
    '#title' => t('Unpublishing'),
    '#collapsible' => FALSE,
    '#weight' => 2,
    '#group' => 'scheduler',
  );
  $form['scheduler']['unpublish']['scheduler_unpublish_enable'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable scheduled unpublishing for this content type'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'unpublish_enable', FALSE),
  );
  $form['scheduler']['unpublish']['scheduler_unpublish_required'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require scheduled unpublishing'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'unpublish_required', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_unpublish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['scheduler']['unpublish']['scheduler_unpublish_revision'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create a new revision on unpublishing'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'unpublish_revision', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_unpublish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  // The 'node_edit_layout' fieldset contains options to alter the layout of
  // node edit pages.
  $form['scheduler']['node_edit_layout'] = array(
    '#type' => 'fieldset',
    '#title' => t('Node edit page layout'),
    '#collapsible' => FALSE,
    '#weight' => 3,
    '#group' => 'scheduler',
    // The #states processing only caters for AND and does not do OR. So to set
    // the state to visible if either of the boxes are ticked we use the fact
    // that logical 'X = A or B' is equivalent to 'not X = not A and not B'.
    '#states' => array(
      '!visible' => array(
        ':input[name="scheduler_publish_enable"]' => array('!checked' => TRUE),
        ':input[name="scheduler_unpublish_enable"]' => array('!checked' => TRUE),
      ),
    ),
  );
  // @todo Worthwhile to port this to D8 now form displays are configurable?
  $form['scheduler']['node_edit_layout']['scheduler_use_vertical_tabs'] = array(
    '#type' => 'radios',
    '#title' => t('Display scheduling options as'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'use_vertical_tabs', TRUE),
    '#options' => array(
      '1' => t('Vertical tab'),
      '0' => t('Separate fieldset'),
    ),
    '#description' => t('Use this option to specify how the scheduling options will be displayed when editing a node.'),
  );
  $form['scheduler']['node_edit_layout']['scheduler_expand_fieldset'] = array(
    '#type' => 'radios',
    '#title' => t('Expand fieldset'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'expand_fieldset', FALSE),
    '#options' => array(
      '0' => t('Expand only when a scheduled date exists or when a date is required'),
      '1' => t('Always open the fieldset, even if no dates exist'),
    ),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_use_vertical_tabs"]' => array('value' => '0'),
      ),
    ),
  );
}

/**
 * Form constructor for the lightweight cron form to allow a manual run.
 */
function _scheduler_lightweight_cron($form, &$form_state) {
  $form = array();
  $form['scheduler_cron'] = array(
    '#type' => 'submit',
    '#prefix' => '<div class="container-inline">' . t("You can test Scheduler's lightweight cron process interactively") . ': ',
    '#value' => t("Run Scheduler's lightweight cron now"),
    '#submit' => array('_scheduler_lightweight_cron_submit'),
    '#suffix' => "</div>\n",
  );
  $form['scheduler_cron_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Lightweight cron settings'),
  );
  $form['scheduler_cron_settings']['scheduler_lightweight_log'] = array(
    '#type' => 'checkbox',
    '#title' => t('Log every activation and completion message.'),
    '#default_value' => \Drupal::config('scheduler.settings')->get('log'),
    '#description' => t("When this option is checked, Scheduler will write an entry to the dblog every time the lightweight cron process is started and completed. This is useful during set up and testing, but can result in a large number of log entries. Any actions performed during the lightweight cron run will always be logged regardless of this setting."),
  );
  $form['scheduler_cron_settings']['scheduler_lightweight_access_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Lightweight cron access key'),
    '#size' => 100,
    '#default_value' => \Drupal::config('scheduler.settings')->get('lightweight_cron_access_key'),
    '#description' => t("Similar to Drupal's cron key this acts as a security token to prevent unauthorised calls to scheduler/cron. The key should be passed as scheduler/cron/&lt;this key&gt;. To disable security for lightweight cron leave this field blank."),
  );
  if (isset($form_state['scheduler_generate_new_key'])) {
    $new_access_key = substr(md5(rand()), 0, 20);
    $form_state['input']['scheduler_lightweight_cron_access_key'] = $new_access_key;
    drupal_set_message(t('A new random key has been generated but not saved. If you wish to use this, first "Save Configuration" to store the value, then modify your crontab job.'), 'warning');
  }
  $form['scheduler_cron_settings']['create_key'] = array(
    '#type' => 'submit',
    '#value' => t('Generate new random key'),
    '#submit' => array('_scheduler_generate_key'),
  );
  return system_settings_form($form);
}

/**
 * Form submission handler for _scheduler_lightweight_cron().
 */
function _scheduler_lightweight_cron_submit() {
  // Load the cron functions file.
  module_load_include('inc', 'scheduler', 'scheduler.cron');
  _scheduler_run_cron();
}

/**
 * Form submission handler for _scheduler_lightweight_cron().
 *
 * This only fires when the 'Generate new random key' button is clicked.
 */
function _scheduler_generate_key($form, &$form_state) {
  // Everything in $form_state is persistent, so we can use a direct attribute.
  $form_state['scheduler_generate_new_key'] = TRUE;
  // Setting $form_state['rebuild'] = TRUE causes the form to be rebuilt again.
  $form_state['rebuild'] = TRUE;
}

/**
 * Page callback: Generate the timecheck admin page.
 */
function _scheduler_timecheck() {
  return theme('scheduler_timecheck', array('now' => REQUEST_TIME));
}

/**
 * Returns HTML the timecheck administration page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - 'now': A UNIX timestamp to format.
 *
 * @ingroup themeable
 */
function theme_scheduler_timecheck($variables) {
  global $user;

  $now = $variables['now'];
  $date_default_timezone = variable_get('date_default_timezone', date_default_timezone_get());

  $t_options = array(
    // For @utc specify 'GMT' as the timezone (4th parameter) so that no
    // timezone offset is returned.
    '@utc' => format_date($now, 'custom', 'jS F Y, H:i:s P', 'GMT'),
    // For @localtime do not specify any timezone parameter so that the user or
    // site default setting is returned.
    '@localtime' => format_date($now, 'custom', 'jS F Y, H:i:s P T e'),
    '@daylight_saving' => format_date($now, 'custom', 'I') ? t('currently in daylight saving mode') : t('not in daylight saving mode'),
    '@user_account' => url('user/' . $user->uid . '/edit'),
    '@date_default_timezone' => check_plain($date_default_timezone),
    '@date_default_offset' => format_date($now, 'custom', 'P', $date_default_timezone),
    '@date_default_code' => format_date($now, 'custom', 'T', $date_default_timezone),
    '@admin_regional_settings' => url('admin/config/regional/settings'),
  );

  $output = '<h4>' . t('Time check') . '</h4>'
    . '<p>' . t('Your server\'s time is @utc. In most cases this should match <a href="https://www.google.com/search?q=Greenwich%20Mean%20Time">Greenwich Mean Time (GMT) / Coordinated Universal Time (UTC)</a>', $t_options) . '</p>'
    . '<p>' . t('The website default timezone is @date_default_timezone (@date_default_code) which is offset from GMT by @date_default_offset hours. This timezone can be <a href="@admin_regional_settings">changed by admin users</a> with the appropriate access.', $t_options) . '</p>';

  if (variable_get('configurable_timezones', 1)) {
    $output .= '<p>' . t('Your local time is @localtime (@daylight_saving). You can change this via your <a href="@user_account">user account</a>.', $t_options) . '</p>';
    if (empty($user->timezone)) {
      $output .= '<p>' . t('Note: The user timezone has not been stored, so defaulting to the website timezone.') . '</p>';
    }
  }
  else {
    $output .= '<p>' . t('Your local time is @localtime (@daylight_saving). This is not configurable by you.', $t_options) . '</p>';
  }

  return $output;
}

/**
 * Page callback: Displays a list of nodes scheduled for (un)publication.
 *
 * This will appear as a tab on the content admin page ('admin/content'). It is
 * also shown as a tab on the 'My account' page if the user has permission to
 * schedule content.
 *
 * @return array
 *   A render array for a page containing a list of nodes.
 */
function scheduler_list() {
  $header = array(
    array(
      'data' => t('Title'),
      'field' => 'n.title',
    ),
    array(
      'data' => t('Type'),
      'field' => 'n.type',
    ),
    array(
      'data' => t('Author'),
      'field' => 'u.name',
    ),
    array(
      'data' => t('Status'),
      'field' => 'n.status',
    ),
    array(
      'data' => t('Publish on'),
      'field' => 's.publish_on',
    ),
    array(
      'data' => t('Unpublish on'),
      'field' => 's.unpublish_on',
    ),
    array(
      'data' => t('Operations'),
    ),
  );

  // Default ordering.
  if (!isset($_GET['order']) && !isset($_GET['sort'])) {
    $_GET['order'] = t('Publish on');
    $_GET['sort'] = 'ASC';
  }

  $query = db_select('scheduler', 's')->extend('PagerDefault');
  $query->limit(50);
  $query->addJoin('LEFT', 'node', 'n', 's.nid = n.nid');
  $query->addJoin('LEFT', 'users', 'u', 'u.uid = n.uid');
  $query->fields('s', array('nid', 'publish_on', 'unpublish_on'));
  $query->fields('n', array('uid', 'status', 'title', 'type', 'status'));
  $query->addField('u', 'name');

  // If this function is being called from a user account page then only select
  // the nodes owned by that user. If the current user is viewing another users'
  // profile and they do not have 'administer nodes' permission then it won't
  // even get this far, as the tab will not be accessible.
  $args = func_get_args();
  if ($args[0] == 'user_only') {
    $query->condition('n.uid', $args[1], '=');
  }
  $query = $query->extend('TableSort')->orderByHeader($header);
  $result = $query->execute();
  $destination = drupal_get_destination();
  $rows = array();

  foreach ($result as $node) {
    // Set the operations depending on whether the node is valid or corrupt.
    if ($node->type) {
      // Node type is present so this indicates a valid join with the node
      // table. Provide regular operations to edit and delete the node.
      $ops = array(
        l(t('edit'), 'node/' . $node->nid . '/edit', array('query' => $destination)),
        l(t('delete'), 'node/' . $node->nid . '/delete', array('query' => $destination)),
      );
    }
    else {
      // There was no matching row in the node table. Provide a special link to
      // delete the row from the Scheduler table.
      $ops = array(l(t('delete'), 'admin/content/scheduler/delete/' . $node->nid, array('query' => $destination)));
    }

    $rows[] = array(
      ($node->title ? l($node->title, "node/$node->nid") : t('Missing data for node @nid', array('@nid' => $node->nid))),
      ($node->type ? check_plain(node_type_get_name($node)) : ''),
      ($node->type ? theme('username', array('account' => $node)) : ''),
      ($node->type ? ($node->status ? t('Published') : t('Unpublished')) : ''),
      ($node->publish_on ? format_date($node->publish_on) : '&nbsp;'),
      ($node->unpublish_on ? format_date($node->unpublish_on) : '&nbsp;'),
      implode(' ', $ops),
    );
  }
  if (count($rows) && ($pager = theme('pager'))) {
    $rows[] = array(
      array(
        'data' => $pager,
        'colspan' => count($rows['0']),
      ),
    );
  }
  global $user;
  $build['scheduler_list'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => ($args[0] == 'user_only') ? t('There are no scheduled nodes for @username.', array('@username' => $user->name)) : t('There are no scheduled nodes.'),
  );
  return $build;
}

/**
 * Form constructor for the Scheduler Delete Row confirmation form.
 *
 * @see _scheduler_delete_row_confirm_submit()
 */
function _scheduler_delete_row_confirm($form, &$form_state, $nid) {
  $form['nid'] = array('#type' => 'value', '#value' => $nid);
  $details = array(t('Are you sure you want to delete the Scheduler row for missing node @nid?', array('@nid' => $nid)));
  $details[] = t('This action cannot be undone.');
  return confirm_form(
    $form,
    $details[0],
    // The cancel path will be changed automatically because the calling url
    // contains a destination value. We need to provide a default here anyway.
    'admin/content/scheduler',
    implode('<br>', $details),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for _scheduler_delete_row_confirm().
 */
function _scheduler_delete_row_confirm_submit($form, &$form_state) {
  // This is a special case, where we only want to delete the row in the
  // Scheduler table. The associated node does not exist any more so we cannot
  // use the normal node/$nid/delete process as that requires a node object.
  if ($form_state['values']['confirm']) {
    $nid = $form_state['values']['nid'];
    db_delete('scheduler')->condition('nid', $nid)->execute();
    watchdog('scheduler', 'Row for missing node @nid deleted from scheduler table.', array('@nid' => $nid));
    drupal_set_message(t('The scheduler data for missing node @nid has been deleted.', array('@nid' => $nid)));
  }
}
