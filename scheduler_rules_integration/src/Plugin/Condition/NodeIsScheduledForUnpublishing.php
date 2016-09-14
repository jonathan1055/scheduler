<?php

namespace Drupal\scheduler_rules_integration\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides 'Node is scheduled for unpublishing' condition.
 *
 * @Condition(
 *   id = "scheduler_condition_node_scheduled_for_unpublishing",
 *   label = @Translation("Node is scheduled for unpublishing"),
 *   category = @Translation("Scheduler"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("The node to test for scheduling properties")
 *     )
 *   }
 * )
 */
class NodeIsScheduledForUnpublishing extends RulesConditionBase {

  /**
   * Determines whether a node is scheduled for unpublishing.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node object to check.
   *
   * @return
   *   TRUE if the node is scheduled for unpublishing, FALSE if not.
   */
  protected function doEvaluate() {
    $node = $this->getContextValue('node');
    return !empty($node->unpublish_on->value);
  }
}
