<?php

namespace Drupal\scheduler\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Custom route subscriber.
 */
class SchedulerRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // @todo Temporary hard-coded list. Construct this list using plugin
    // manager data.
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
