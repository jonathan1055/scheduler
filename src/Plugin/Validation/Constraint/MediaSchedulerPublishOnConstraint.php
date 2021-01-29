<?php
namespace Drupal\scheduler\Plugin\Validation\Constraint;

use Drupal\scheduler\Plugin\Validation\Constraint\BaseSchedulerPublishOnConstraint;

/**
 * Validates publish on values.
 *
 * @Constraint(
 *   id = "MediaSchedulerPublishOn",
 *   label = @Translation("Scheduler publish on", context = "Validation"),
 *   type = "entity:media"
 * )
 */
class MediaSchedulerPublishOnConstraint extends BaseSchedulerPublishOnConstraint {

}
