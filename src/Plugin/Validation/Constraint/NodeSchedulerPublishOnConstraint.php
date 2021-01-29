<?php

namespace Drupal\scheduler\Plugin\Validation\Constraint;

/**
 * Validates publish on values.
 *
 * @Constraint(
 *   id = "NodeSchedulerPublishOn",
 *   label = @Translation("Scheduler publish on", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class NodeSchedulerPublishOnConstraint extends BaseSchedulerPublishOnConstraint {

}
