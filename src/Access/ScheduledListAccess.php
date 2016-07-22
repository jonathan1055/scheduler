<?php

namespace Drupal\scheduler\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks access for displaying the scheduler list of scheduled nodes.
 */
class ScheduledListAccess implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasRequirement('_access_scheduler_content');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // Users with 'schedule publishing of nodes' can see their own scheduled
    // content via a tab on their user page. Users with 'view scheduled content'
    // will be able to access the 'scheduled' tab for any user, and also access
    // the scheduled content overview page.

    // When viewing a user profile routeMatch()->getRawParameter('user') will
    // return the user's id. If not on a user page it returns NULL silently.
    $viewing_own_tab = \Drupal::routeMatch()->getRawParameter('user') == $account->id();
    return ($account->hasPermission('view scheduled content') || ($viewing_own_tab && $account->hasPermission('schedule publishing of nodes'))) ? AccessResult::allowed(): AccessResult::forbidden();
  }

}
