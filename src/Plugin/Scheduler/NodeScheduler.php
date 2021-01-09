<?php

namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for Node entity type.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "node_scheduler",
 *  label = @Translation("Node Scheduler Plugin"),
 *  entityType = "node",
 *  typeFieldName = "type",
 *  dependency = "node",
 *  idListFunction = "scheduler_nid_list",
 * )
 */
class NodeScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation')
    );
  }

  /**
   * Get the available types/bundles for the entity type.
   *
   * @return array
   *   The node type objects.
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
    $ids = [
      'node_add_form',
      'node_edit_form',
    ];
    $types = array_keys($this->getTypes());
    foreach ($types as $typeId) {
      $ids[] = 'node_' . $typeId . '_form';
      $ids[] = 'node_' . $typeId . '_add_form';
      $ids[] = 'node_' . $typeId . '_edit_form';
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
