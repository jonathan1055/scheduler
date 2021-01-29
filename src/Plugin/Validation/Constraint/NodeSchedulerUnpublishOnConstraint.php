<?php
namespace Drupal\scheduler\Plugin\Validation\Constraint;

use Drupal\scheduler\Plugin\Validation\Constraint\BaseSchedulerUnpublishOnConstraint;

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
