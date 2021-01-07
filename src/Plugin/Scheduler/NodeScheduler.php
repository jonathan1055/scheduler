<?php

namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\NodeType;
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
   * Publish pending nodes.
   */
  public function publish() {
    // @todo - this will likely be handled in the ScheduleManager service.
  }

  /**
   * Unpublish pending nodes.
   */
  public function unpublish() {
    // @todo - this will likely be handled in the ScheduleManager service.
  }

  /**
   * Get the available bundles for the entity type.
   *
   * @return array
   *   The list of bundles.
   */
  public function getTypes() {
    return NodeType::loadMultiple();
  }

  /**
   * Get the form IDs for entity add/edit forms.
   *
   * @return array
   *   The list of form IDs.
   */
  public function entityFormIds() {
    $ids = [
      'node_add_form',
      'node_edit_form',
    ];

    $types = NodeType::loadMultiple();
    /** @var \Drupal\node\Entity\NodeType $type */
    foreach ($types as $type) {
      $ids[] = 'node_' . $type->id() . '_form';
      $ids[] = 'node_' . $type->id() . '_add_form';
      $ids[] = 'node_' . $type->id() . '_edit_form';
    }
    return $ids;
  }

  /**
   * Get the list of node type form IDs.
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

  /**
   * Get the bundle name for $media.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node.
   *
   * @return string
   *   The bundle.
   */
  public function getEntityType(EntityInterface $node) {
    return $node->type->entity;
  }

  /**
   * Get list of enabled bundles for $action ([un]publish).
   *
   * @param string $action
   *   The action - publish|unpublish.
   *
   * @return array
   *   The list of bundles.
   */
  public function getEnabledTypes($action) {
    $config = \Drupal::config('scheduler.settings');
    $types = $this->getTypes();
    return array_filter($types, function ($bundle) use ($action, $config) {
      /** @var \Drupal\node\NodeTypeInterface $bundle */
      return $bundle->getThirdPartySetting('scheduler', $action . '_enable', $config->get('default_' . $action . '_enable'));
    });
  }

}
