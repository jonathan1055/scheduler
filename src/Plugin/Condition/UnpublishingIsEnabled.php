<?php

/**
 * @file
 * Contains \Drupal\scheduler\Plugin\Condition\UnpublishingIsEnabled.
 */

namespace Drupal\scheduler\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides 'Unpublishing is enabled' condition.
 *
 * @Condition(
 *   id = "scheduler_condition_unpublishing_is_enabled",
 *   label = @Translation("Node type is enabled for scheduled unpublishing"),
 *   category = @Translation("Scheduler"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("The node to test for scheduling properties")
 *     )
 *   }
 * )
 */
class UnpublishingIsEnabled extends RulesConditionBase {

  /**
   * Determines whether scheduled unpublishing is enabled for this node type.
   *
   * @return
   *   TRUE if scheduled unpublishing is enabled for the node type, FALSE if not.
   */
  public function evaluate() {
    $node = $this->getContextValue('node');
    return ($node->type->entity->getThirdPartySetting('scheduler', 'unpublish_enable', SCHEDULER_DEFAULT_UNPUBLISH_ENABLE));
  }

}
