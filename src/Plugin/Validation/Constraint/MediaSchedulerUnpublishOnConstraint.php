<?php
namespace Drupal\scheduler\Plugin\Validation\Constraint;

use Drupal\scheduler\Plugin\Validation\Constraint\BaseSchedulerUnpublishOnConstraint;

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
