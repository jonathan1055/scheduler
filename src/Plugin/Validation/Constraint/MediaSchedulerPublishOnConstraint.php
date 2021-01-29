<?php

namespace Drupal\scheduler\Plugin\Validation\Constraint;

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
