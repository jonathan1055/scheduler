<?php

namespace Drupal\scheduler\Plugin\Validation\Constraint;

/**
 * Validates unpublish on values.
 *
 * @Constraint(
 *   id = "NodeSchedulerUnpublishOn",
 *   label = @Translation("Scheduler unpublish on", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class NodeSchedulerUnpublishOnConstraint extends BaseSchedulerUnpublishOnConstraint {

}
