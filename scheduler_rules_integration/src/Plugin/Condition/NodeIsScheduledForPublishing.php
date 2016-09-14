<?php

namespace Drupal\scheduler_rules_integration\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides 'Node is scheduled for publishing' condition.
 *
 * @Condition(
 *   id = "scheduler_condition_node_scheduled_for_publishing",
 *   label = @Translation("Node is scheduled for publishing"),
 *   category = @Translation("Scheduler"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("The node to test for scheduling properties")
 *     )
 *   }
 * )
 */
class NodeIsScheduledForPublishing extends RulesConditionBase {

  /**
   * Determines whether a node is scheduled for publishing.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node object to check.
   *
   * @return
   *   TRUE if the node is scheduled for publishing, FALSE if not.
   */
  protected function doEvaluate() {
    $node = $this->getContextValue('node');
    return !empty($node->publish_on->value);
  }
}
