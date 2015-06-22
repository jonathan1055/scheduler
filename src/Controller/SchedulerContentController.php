<?php

/**
 * @file
 * Contains \Drupal\scheduler\Controller\SchedulerContentController.
 */

namespace Drupal\scheduler\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Query;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Scheduler module.
 */
class SchedulerContentController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a SchedulerContentController object.
   *
   * @param \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('renderer'));
  }

  /**
   * Displays a list of nodes scheduled for (un)publication.
   *
   * This will appear as a tab on the content admin page ('admin/content'). It is
   * also shown as a tab on the 'My account' page if the user has permission to
   * schedule content.
   *
   * @return array
   *   A render array for a page containing a list of nodes.
   */
  public function listScheduled(AccountInterface $user = NULL, $user_only = NULL) {
    // @todo Inject the Renderer service.
    $renderer = \Drupal::service('renderer');

    $header = [
      ['data' => t('Title'), 'field' => 'nd.title'],
      ['data' => t('Type'), 'field' => 'n.type'],
      ['data' => t('Author'), 'field' => 'ud.name'],
      ['data' => t('Status'), 'field' => 'nd.status'],
      ['data' => t('Publish on'), 'field' => 's.publish_on'],
      ['data' => t('Unpublish on'), 'field' => 's.unpublish_on'],
      ['data' => t('Operations')],
    ];

    // Default ordering.
    if (!isset($_GET['order']) && !isset($_GET['sort'])) {
      $_GET['order'] = t('Publish on');
      $_GET['sort'] = 'ASC';
    }

    // @todo Convert this to an entityQuery().
    $query = db_select('scheduler', 's')->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->limit(50);
    $query->addJoin('LEFT', 'node', 'n', 's.nid = n.nid');
    $query->addJoin('LEFT', 'node_field_data', 'nd', 'n.nid = nd.nid');
    $query->addJoin('LEFT', 'users', 'u', 'u.uid = nd.uid');
    $query->addJoin('LEFT', 'users_field_data', 'ud', 'ud.uid = u.uid');
    $query->fields('s', ['nid', 'publish_on', 'unpublish_on']);
    $query->fields('n', ['type']);
    $query->fields('nd', ['uid', 'status', 'title']);
    $query->addField('ud', 'name');

    // If this function is being called from a user account page then only select
    // the nodes owned by that user. If the current user is viewing another users'
    // profile and they do not have 'administer nodes' permission then it won't
    // even get this far, as the tab will not be accessible.
    if ($user_only && $user) {
      $query->condition('nd.uid', $user->id(), '=');
    }
    $query = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $result = $query->execute();
    $destination = drupal_get_destination();
    $rows = [];

    foreach ($result as $node) {
      // Provide regular operations to edit and delete the node.
      $ops = [
        \Drupal::l(t('edit'), Url::fromRoute('entity.node.edit_form', ['node' => $node->nid], ['query' => $destination])),
        \Drupal::l(t('delete'), Url::fromRoute('entity.node.delete_form', ['node' => $node->nid], ['query' => $destination])),
      ];

      $rows[] = [
        $node->title ? \Drupal::l($node->title, Url::fromRoute('entity.node.canonical', ['node' => $node->nid])) : t('Missing data for node @nid', ['@nid' => $node->nid]),
        $node->type ? SafeMarkup::checkPlain(node_get_type_label($node)) : '',
        $node->type ? $this->renderer->render(['#theme' => 'username', '#account' => $node]) : '',
        $node->type ? ($node->status ? t('Published') : t('Unpublished')) : '',
        $node->publish_on ? format_date($node->publish_on) : '&nbsp;',
        $node->unpublish_on ? format_date($node->unpublish_on) : '&nbsp;',
        implode(' ', $ops),
      ];
    }

    if (count($rows) && ($pager = $this->renderer->render(['#theme' => 'pager']))) {
      $rows[] = [
        ['data' => $pager, 'colspan' => count($rows['0'])],
      ];
    }

    $account = \Drupal::currentUser();
    $build['scheduler_list'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $user_only ? t('There are no scheduled nodes for @username.', ['@username' => $account->getUsername()]) : t('There are no scheduled nodes.'),
    ];
    return $build;
  }

}
