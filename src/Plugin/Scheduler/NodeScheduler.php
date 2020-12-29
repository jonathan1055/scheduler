<?php
namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\NodeType;
use Drupal\scheduler\SchedulerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Node
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "node_scheduler",
 *  label = @Translation("Node Scheduler Plugin"),
 * )
 */
class NodeScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation')
    );
  }

  public function publish() {
    /** @var \Drupal\scheduler\SchedulerManager $scheduler_manager */
    $scheduler_manager = \Drupal::service('scheduler.manager');
    $scheduler_manager->publish();

  }
  public function unpublish() {
    /** @var \Drupal\scheduler\SchedulerManager $scheduler_manager */
    $scheduler_manager = \Drupal::service('scheduler.manager');
    $scheduler_manager->unpublish();
  }

  public function entityType() {
    return 'node';
  }

  public function getTypes() {
    return NodeType::loadMultiple();
  }

  public function entityFormIDs() {
    $ids = [
      'node_add_form',
      'node_edit_form',
    ];

    $types = NodeType::loadMultiple();
    /** @var NodeType $type */
    foreach ($types as $type) {
      $ids[] = 'node_' . $type->id() . '_form';
      $ids[] = 'node_' . $type->id() . '_add_form';
      $ids[] = 'node_' . $type->id() . '_edit_form';
    }
    return $ids;
  }

  public function entityTypeFormIDs() {
    return [
      'node_type_add_form',
      'node_type_edit_form',
      ];
  }

  public function getEntityType($node) {
    return $node->type->entity;
  }
}
