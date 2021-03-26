<?php

namespace Drupal\scheduler\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Scheduler route subscriber to add custom access for user views.
 */
class SchedulerRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // @todo Is is better to construct this list using a views or routes query
    // to get all 'scheduler_scheduled_*.user_page' views? Maybe that is adding
    // unnecessary processing and complexity. For now leave as hard-coded.
    $user_page_routes = [
      'view.scheduler_scheduled_content.user_page',
      'view.scheduler_scheduled_media.user_page',
    ];

    foreach ($user_page_routes as $user_route) {
      if ($route = $collection->get($user_route)) {
        $requirements = $route->getRequirements();
        $requirements['_custom_access'] = '\Drupal\scheduler\Access\SchedulerRouteAccess::access';
        $route->setRequirements($requirements);
      }
    }
  }

}
