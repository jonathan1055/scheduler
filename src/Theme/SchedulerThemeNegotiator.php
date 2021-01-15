<?php

namespace Drupal\scheduler\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Defines a theme negotiator for the Scheduler routes.
 */
class SchedulerThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Use the Scheduler theme negotiator for scheduler views on the user page.
    $applies = (in_array($route_match->getRouteName(), [
      'view.scheduler_scheduled_content.user_page',
      'view.scheduler_scheduled_media.user_page',
    ]));
    return $applies;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Return the admin theme.
    $config = \Drupal::service('config.factory')->getEditable('system.theme');
    $admin_theme = $config->get('admin');
    return $admin_theme;
  }

}
