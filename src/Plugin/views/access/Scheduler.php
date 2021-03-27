<?php

namespace Drupal\scheduler\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that use to provide access control for Scheduler views.
 *
 * This is no longer used, but the class needs to remain temporarily as it is
 * used in the existing views. Deleting this class will cause errors during
 * upgrade.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "scheduler",
 *   title = @Translation("Scheduled content access"),
 *   help = @Translation("NOT USED. Replaced with SchedulerRouteAccess"),
 * )
 */
class Scheduler extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return \Drupal::service('access_checker.scheduler_content')->access($account);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_access_scheduler_content', 'TRUE');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
