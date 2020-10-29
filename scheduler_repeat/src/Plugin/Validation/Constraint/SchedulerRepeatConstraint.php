<?php

namespace Drupal\scheduler_repeat\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Validates repeat values.
 *
 * @Constraint(
 *   id = "SchedulerRepeat",
 *   label = @Translation("Scheduler repeat", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class SchedulerRepeatConstraint extends CompositeConstraintBase {

  /**
   * Message shown when repeat period conflicts with scheduled period.
   *
   * For example, you can't say "09:00-11:00 repeat every hour", because the
   * periods would overlap.
   *
   * @var string
   */
  public $messageRepeatPeriodSmallerThanScheduledPeriod = "Repeat conflicts with 'publish on' and 'unpublish on'.";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['repeat'];
  }

}
