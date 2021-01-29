<?php

namespace Drupal\scheduler\Plugin\Validation\Constraint;

/**
 * Validates unpublish on values.
 *
 * @Constraint(
 *   id = "MediaSchedulerUnpublishOn",
 *   label = @Translation("Scheduler unpublish on", context = "Validation"),
 *   type = "entity:media"
 * )
 */
class MediaSchedulerUnpublishOnConstraint extends BaseSchedulerUnpublishOnConstraint {

}
