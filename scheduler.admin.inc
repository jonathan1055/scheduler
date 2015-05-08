<?php

/**
 * @file
 * Administration forms for the Scheduler module.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeTypeInterface;

/**
 * Helper function for the real hook_form_node_type_form_alter().
 *
 * @see scheduler_form_node_type_form_alter()
 */
function _scheduler_form_node_type_form_alter(array &$form, FormStateInterface $form_state) {
  /** @var \Drupal\node\NodeTypeInterface $type */
  $type = $form_state->getFormObject()->getEntity();

  $form['#attached']['library'][] = 'scheduler/admin';
  $form['#attached']['library'][] = 'scheduler/vertical-tabs';

  $form['scheduler'] = array(
    '#type' => 'details',
    '#title' => t('Scheduler'),
    '#weight' => 35,
    '#group' => 'additional_settings',
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
  );

  $form['#entity_builders'][] = 'scheduler_form_node_type_form_builder';
}

/**
 * Entity builder for the node type form with scheduler options.
 */
function scheduler_form_node_type_form_builder($entity_type, NodeTypeInterface $type, &$form, FormStateInterface $form_state) {
  $type->setThirdPartySetting('scheduler', 'expand_fieldset', $form_state->getValue('scheduler_expand_fieldset'));
  $type->setThirdPartySetting('scheduler', 'publish_enable', $form_state->getValue('scheduler_publish_enable'));
  $type->setThirdPartySetting('scheduler', 'publish_past_date', $form_state->getValue('scheduler_publish_past_date'));
  $type->setThirdPartySetting('scheduler', 'publish_required', $form_state->getValue('scheduler_publish_required'));
  $type->setThirdPartySetting('scheduler', 'publish_revision', $form_state->getValue('scheduler_publish_revision'));
  $type->setThirdPartySetting('scheduler', 'publish_touch', $form_state->getValue('scheduler_publish_touch'));
  $type->setThirdPartySetting('scheduler', 'unpublish_enable', $form_state->getValue('scheduler_unpublish_enable'));
  $type->setThirdPartySetting('scheduler', 'unpublish_required', $form_state->getValue('scheduler_unpublish_required'));
  $type->setThirdPartySetting('scheduler', 'unpublish_revision', $form_state->getValue('scheduler_unpublish_revision'));
  $type->setThirdPartySetting('scheduler', 'use_vertical_tabs', $form_state->getValue('scheduler_use_vertical_tabs'));
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
    // Provide regular operations to edit and delete the node.
    $ops = array(
      \Drupal::l(t('edit'), Url::fromRoute('entity.node.edit_form', ['node' => $node->nid], ['query' => $destination])),
      \Drupal::l(t('delete'), Url::fromRoute('entity.node.delete_form', ['node' => $node->nid], ['query' => $destination])),
    );

    $rows[] = array(
      ($node->title ? l($node->title, "node/$node->nid") : t('Missing data for node @nid', array('@nid' => $node->nid))),
      ($node->type ? String::checkPlain(node_get_type_label($node)) : ''),
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
  $account = \Drupal::currentUser();
  $build['scheduler_list'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => ($args[0] == 'user_only') ? t('There are no scheduled nodes for @username.', array('@username' => $account->getUsername())) : t('There are no scheduled nodes.'),
  );
  return $build;
}
