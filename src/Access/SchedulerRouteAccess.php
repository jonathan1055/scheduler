<?php

namespace Drupal\scheduler\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\Route;

/**
 * Sets access for specific scheduler views routes.
 */
class SchedulerRouteAccess {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return NULL;
  }

  /**
   * Provides custom access checks for the scheduled views on the user page.
   *
   * A user is given access if either of the following conditions are met:
   * - they are viewing their own page and they have the permission
   * 'scheduled publishing of {type}'
   * - they are viewing another user's page and they have 'access user profiles'
   * and 'view scheduled {type}' permissions, and the user they are viewing has
   * 'scheduled publishing of {type}' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match) {
    $user_being_viewed = $route_match->getParameter('user');
    $viewing_own_page = $user_being_viewed == $account->id();
    // For backwards-compatibility the node permission names have to end with
    // 'nodes' and 'content'. For all other entity types we use entity type id.
    $view_id = $route_match->getParameter('view_id');
    if ($view_id == 'scheduler_scheduled_content') {
      $edit_key = 'nodes';
      $view_key = 'content';
    }
    else {
      // @todo Exploding the view_id is a bit hacky, is there a better way?
      $edit_key = $view_key = explode('_', $view_id)[2];
    }

    if ($viewing_own_page && $account->hasPermission("schedule publishing of $edit_key")) {
      return AccessResult::allowed();
    }
    if (!$viewing_own_page && $account->hasPermission("view scheduled $view_key") && $account->hasPermission('access user profiles')) {
      $other_user = User::load($user_being_viewed);
      if ($other_user && $other_user->hasPermission("schedule publishing of $edit_key")) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
