<?php

/**
 * @file
 * Contains \Drupal\scheduler\Plugin\Condition\PublishingIsEnabled.
 */

namespace Drupal\scheduler\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Publishing is enabled' condition.
 *
 * @Condition(
 *   id = "scheduler_condition_publishing_is_enabled",
 *   label = @Translation("Node type is enabled for scheduled publishing"),
 *   category = @Translation("Scheduler"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("The node to test for scheduling properties")
 *     )
 *   }
 * )
 */
class PublishingIsEnabled extends RulesConditionBase {

  /**
   * Determines whether scheduled publishing is enabled for this node type.
   *
   * @return
   *   TRUE if scheduled publishing is enabled for the node type, FALSE if not.
   */
  public function evaluate() {
    $node = $this->getContextValue('node');
    return ($node->type->entity->getThirdPartySetting('scheduler', 'publish_enable', SCHEDULER_DEFAULT_PUBLISH_ENABLE));
  }

}
