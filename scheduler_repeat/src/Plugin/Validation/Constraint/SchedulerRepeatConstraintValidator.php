<?php

namespace Drupal\scheduler_repeat\Plugin\Validation\Constraint;

use Drupal\node\NodeInterface;
use Drupal\scheduler_repeat\SchedulerRepeaterInterface;
use Drupal\scheduler_repeat\InvalidPluginTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SchedulerRepeatConstraintValidator extends ConstraintValidator {

  protected $scheduler_repeater_manager;

  public function __construct() {
    $this->scheduler_repeater_manager = \Drupal::service('plugin.manager.scheduler_repeat.repeater');
  }

  /**
   * {@inheritdoc}
   * @throws InvalidPluginTypeException
   */
  public function validate($value, Constraint $constraint) {
    if ($value->isEmpty()) {
      return;
    }

    /** @var NodeInterface $node */
    $node = $value->getEntity();
    if (!$this->shouldValidate($node)) {
      return;
    }

    // @todo When associated data is added, the plugin id can be extracted.
    $plugin_id = $value->plugin;
    
    $repeater = $this->getRepeaterWithNode($plugin_id, $node);
    if (!$repeater->validate()) {
      $this->context->buildViolation($constraint->messageRepeatPeriodSmallerThanScheduledPeriod)
        ->atPath('repeat')
        ->addViolation();
    }

  }

  /**
   * Determine when validation should be applied.
   *
   * @param NodeInterface $node
   *
   * @return bool
   */
  private function shouldValidate(NodeInterface $node) {
    // We need both dates in order to validate potentially conflicting periods
    // @todo Figure out how to handle $node that is in active scheduled period
    return !$node->get('publish_on')->isEmpty() && !$node->get('unpublish_on')->isEmpty();
  }

  /**
   * @param string $plugin_id
   * @param NodeInterface $node
   *
   * @return SchedulerRepeaterInterface
   * @throws InvalidPluginTypeException
   *
   * @todo This duplicates fiunctionality done in _scheduler_repeat_get_repeater
   * Need to consolidate these? Also from node->plugin may need to get the
   * optional associated data. See _scheduler_repeat_get_repeater().
   */
  private function getRepeaterWithNode(string $plugin_id, NodeInterface $node) {
    $repeater = $this->scheduler_repeater_manager->createInstance($plugin_id, ['node' => $node]);
    if (!$repeater instanceof SchedulerRepeaterInterface) {
      throw new InvalidPluginTypeException('Scheduler repeater manager returned wrong plugin type: ' . get_class($repeater));
    }
    return $repeater;
  }

}
