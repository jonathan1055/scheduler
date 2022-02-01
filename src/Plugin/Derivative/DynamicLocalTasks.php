<?php

namespace Drupal\scheduler\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\views\Entity\View;

/**
 * Defines dynamic local tasks.
 *
 * The local tasks that define tabs for the 'Scheduled' entity views cannot be
 * hard-coded in the links.task.yml file because if a view is disabled its route
 * will not exist and this produces an exception "Route X does not exist." The
 * routes are defined here instead to enable checking that the views are loaded.
 */
class DynamicLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    // Define a local task for scheduled content (nodes) view, only when the
    // view can be loaded, is enabled and that the overview display exists.
    $view = View::load('scheduler_scheduled_content');
    if ($view && $view->status() && $view->getDisplay('overview')) {
      // The content overview has weight 0 and moderated content has weight 1
      // so use weight 5 for the scheduled content tab.
      $this->derivatives['scheduler.scheduled_content'] = [
        'title' => 'Scheduled content',
        'route_name' => 'view.scheduler_scheduled_content.overview',
        'parent_id' => 'system.admin_content',
        'weight' => 5,
      ] + $base_plugin_definition;

      // Core content_moderation module defines an 'overview' local task which
      // is required when adding additional local tasks. If that module is not
      // installed then define the tab here. This can be removed if
      // https://www.drupal.org/project/drupal/issues/3199682 gets committed.
      if (!\Drupal::moduleHandler()->moduleExists('content_moderation')) {
        $this->derivatives['scheduler.content_overview'] = [
          'title' => 'Overview',
          'route_name' => 'system.admin_content',
          'parent_id' => 'system.admin_content',
        ] + $base_plugin_definition;
      }
    }

    $view = View::load('scheduler_scheduled_media');
    if ($view && $view->status() && $view->getDisplay('overview')) {
      // Define local task for scheduled media view.
      $this->derivatives['scheduler.scheduled_media'] = [
        'title' => 'Scheduled media',
        'route_name' => 'view.scheduler_scheduled_media.overview',
        'parent_id' => 'entity.media.collection',
        'weight' => 5,
      ] + $base_plugin_definition;

      // This task is added so that we get an 'overview' sub-task link alongside
      // the 'scheduled media' sub-task link.
      $this->derivatives['scheduler.media_overview'] = [
        'title' => 'Overview',
        'route_name' => 'entity.media.collection',
        'parent_id' => 'entity.media.collection',
      ] + $base_plugin_definition;
    }

    $view = View::load('scheduler_scheduled_commerce_product');
    if ($view && $view->status() && $view->getDisplay('overview')) {

      // The page created by route entity.commerce_product.collection does not
      // have any tabs or sub-links, because the Commerce Product module does
      // not specify any local tasks for this route. Therefore we need a
      // top-level task which just defines the route name as a base route. This
      // will be used as the parent for the two tabs defined below.
      $this->derivatives['scheduler.commerce_products'] = [
        'route_name' => 'entity.commerce_product.collection',
        'base_route' => 'entity.commerce_product.collection',
      ] + $base_plugin_definition;

      // Define local task for the scheduled products view.
      $this->derivatives['scheduler.scheduled_products'] = [
        'title' => 'Scheduled products',
        'route_name' => 'view.scheduler_scheduled_commerce_product.overview',
        'parent_id' => 'scheduler.local_tasks:scheduler.commerce_products',
        'weight' => 5,
      ] + $base_plugin_definition;

      // This task is added so that we get an 'overview' sub-task link alongside
      // the 'scheduled products' sub-task link.
      $this->derivatives['scheduler.commerce_product.collection'] = [
        'title' => 'Overview',
        'route_name' => 'entity.commerce_product.collection',
        'parent_id' => 'scheduler.local_tasks:scheduler.commerce_products',
      ] + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
