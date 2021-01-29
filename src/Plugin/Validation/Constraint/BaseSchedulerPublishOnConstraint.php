<?php

namespace Drupal\scheduler\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Base class for Publish On constraint.
 */
class BaseSchedulerPublishOnConstraint extends CompositeConstraintBase {

  /**
   * Message shown when publish_on is not the future.
   *
   * @var string
   */
  public $messagePublishOnDateNotInFuture = "The 'publish on' date must be in the future.";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['publish_on'];
  }

}
