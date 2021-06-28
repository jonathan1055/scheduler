<?php

namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;

/**
 * Plugin for Node entity type.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "node_scheduler",
 *  label = @Translation("Node Scheduler Plugin"),
 *  description = @Translation("Provides support for scheduling node entities"),
 *  entityType = "node",
 *  typeFieldName = "type",
 *  dependency = "node",
 *  develGenerateForm = "devel_generate_form_content",
 *  userViewRoute = "view.scheduler_scheduled_content.user_page",
 * )
 */
class NodeScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Get the available types/bundles for the entity type.
   *
   * Do not use static or drupal_static here, because changes to third-party
   * settings invalidate the saved values during phpunit testing.
   *
   * @return array
   *   The node type objects, keyed by node type name.
   */
  public function getTypes() {
    $nodeTypes = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    return $nodeTypes;
  }

  /**
   * Get the form IDs for node add/edit forms.
   *
   * @return array
   *   The list of form IDs.
   */
  public function entityFormIds() {
    static $ids;
    if (!isset($ids)) {
      $ids = [];
      $types = array_keys($this->getTypes());
      foreach ($types as $typeId) {
        // The node add form is named node_{type}_form. This is different from
        // other entities, which have {entity}_{type}_add_form.
        $ids[] = 'node_' . $typeId . '_form';
        $ids[] = 'node_' . $typeId . '_edit_form';
      }
    }
    return $ids;
  }

  /**
   * Get the form IDs for node type forms.
   *
   * @return array
   *   The list of form IDs.
   */
  public function entityTypeFormIds() {
    return [
      'node_type_add_form',
      'node_type_edit_form',
    ];
  }

}
