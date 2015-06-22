<?php

/**
 * @file
 * Contains \Drupal\scheduler\Controller\SchedulerController.
 */

namespace Drupal\scheduler\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Query;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Scheduler module.
 */
class SchedulerController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a SchedulerController object.
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

    $query = \Drupal::entityQuery('node');
    $query->condition($query->orConditionGroup()
      ->condition('publish_on', 0, '>')
      ->condition('unpublish_on', 0, '>')
    );

    // If this function is being called from a user account page then only
    // select the nodes owned by that user. If the current user is viewing
    // another users' profile and they do not have 'administer nodes' permission
    // then it won't even get this far, as the tab will not be accessible.
    if ($user_only && $user) {
      $query->condition('uid', $user->id(), '=');
    }
    $destination = \Drupal::destination()->getAsArray();

    $rows = [];
    foreach (Node::loadMultiple($query->execute()) as $node) {
      // Provide regular operations to edit and delete the node.
      $ops = [
        \Drupal::l(t('edit'), Url::fromRoute('entity.node.edit_form', ['node' => $node->id()], ['query' => $destination])),
        \Drupal::l(t('delete'), Url::fromRoute('entity.node.delete_form', ['node' => $node->id()], ['query' => $destination])),
      ];

      $username = ['#theme' => 'username', '#account' => $node->uid->entity];
      $rows[] = [
        \Drupal::l($node->getTitle(), Url::fromRoute('entity.node.canonical', ['node' => $node->id()])),
        SafeMarkup::checkPlain($node->type->entity->label()),
        SafeMarkup::set($this->renderer->render($username)),
        $node->status ? t('Published') : t('Unpublished'),
        !$node->publish_on->isEmpty() ? format_date($node->publish_on->value) : '',
        !$node->unpublish_on->isEmpty() ? format_date($node->unpublish_on->value) : '',
        SafeMarkup::set(implode(' ', $ops)),
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
