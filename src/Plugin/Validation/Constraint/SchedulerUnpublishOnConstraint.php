<?php

/**
 * @file
 * Contains \Drupal\scheduler\Plugin\Validation\Constraint\SchedulerUnpublishOnConstraint.
 */

namespace Drupal\scheduler\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Validates unpublish on values.
 *
 * @Constraint(
 *   id = "SchedulerUnpublishOn",
 *   label = @Translation("Scheduler unpublish on", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class SchedulerUnpublishOnConstraint extends CompositeConstraintBase {

  /**
   * Message shown when unpublish_on is not set but required.
   *
   * @var string
   */
  public $messageUnpublishOnRequiredIfPublishOnEntered = "If you set a 'publish-on' date then you must also set an 'unpublish-on' date.";

  /**
   * Message shown when unpublish_on is not in the future.
   *
   * @var string
   */
  public $messageUnpublishOnDateNotInFuture = "The 'unpublish on' date must be in the future.";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['unpublish_on'];
  }

}
