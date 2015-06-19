<?php

/**
 * @file
 * Contains \Drupal\scheduler\Access\ScheduledListAccess.
 */

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

    // All Scheduler users can see their own scheduled content via their user
    // page. In addition, if they have 'view scheduled content' permission they
    // will be able to see all scheduled content by all authors.
    return ($account->hasPermission('view scheduled content') || (\Drupal::currentUser()->id() == $account->id() && $account->hasPermission('schedule (un)publishing of nodes'))) ? AccessResult::allowed(): AccessResult::forbidden() ;
  }

}
