<?php

namespace Drupal\scheduler_repeat\Plugin\Validation\Constraint;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;
use Drupal\scheduler_repeat\InvalidPluginTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SchedulerRepeat constraint.
 */
class SchedulerRepeatConstraintValidator extends ConstraintValidator {

  /**
   * The Scheduler Repeat plugin manager.
   *
   * @var \Drupal\scheduler_repeat\SchedulerRepeaterManager
   */
  protected $schedulerRepeatPluginManager;

  /**
   * The node to validate.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The repeat plugin instance.
   *
   * @var \Drupal\scheduler_repeat\SchedulerRepeaterInterface
   */
  protected $repeater;

  /**
   * Constructs the object.
   */
  public function __construct() {
    $this->schedulerRepeatPluginManager = \Drupal::service('plugin.manager.scheduler_repeat.repeater');
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($value->isEmpty()) {
      return;
    }

    /** @var \Drupal\node\NodeInterface $node */
    $this->node = $value->getEntity();
    if (!$this->shouldValidate()) {
      return;
    }

    // @todo When associated data is added, the plugin id can be extracted.
    $plugin_id = $value->plugin;

    $this->repeater = $this->initializeRepeaterWithPlugin($plugin_id);
    // If the calculated next publish_on value is earlier than the current
    // unpublish_on value then the dates overlap. This means that the repeat
    // period is too short so fail validation.
    if ($this->repeater->calculateNextPublishedOn($this->getPublishOn()) <= $this->getUnpublishOn()) {
      $this->context->buildViolation($constraint->messageRepeatPeriodSmallerThanScheduledPeriod)
        ->atPath('scheduler_repeat')
        ->addViolation();
    }
  }

  /**
   * Determine when validation should be applied.
   *
   * @return bool
   *   Whether to validate the repeat value.
   */
  private function shouldValidate() {
    // We need both dates in order to validate potentially conflicting periods.
    // @todo Figure out how to handle $node that is in active scheduled period
    return !$this->node->get('publish_on')->isEmpty() && !$this->node->get('unpublish_on')->isEmpty();
  }

  /**
   * Gets publish_on date that exists in the node.
   *
   * @return int
   *   The current publish_on value.
   */
  protected function getPublishOn() {
    return $this->node->get('publish_on')->value;
  }

  /**
   * Gets unpublish_on date that exists in the node.
   *
   * @return int
   *   The current unpublish_on value.
   */
  protected function getUnpublishOn() {
    return $this->node->get('unpublish_on')->value;
  }

  /**
   * Create an instance of the required repeat plugin.
   *
   * @param string $plugin
   *   The plugin information. Currently this is just the plugin id, but could
   *   be expanded to hold additional associated data.
   *
   * @return \Drupal\scheduler_repeat\SchedulerRepeaterInterface
   *   The repeat plugin object.
   *
   * @throws \Drupal\scheduler_repeat\InvalidPluginTypeException
   *
   * @todo This duplicates some functionality in _scheduler_repeat_get_repeater.
   * Need to consolidate these?
   */
  private function initializeRepeaterWithPlugin(string $plugin) {
    // @todo When we cater for optional associated data, the id can be
    // extracted and the other values added into $plugin_data.
    $plugin_id = $plugin;
    $plugin_data = ['node' => $node];
    $repeater = $this->schedulerRepeatPluginManager->createInstance($plugin_id, $plugin_data);
    if (!$repeater instanceof SchedulerRepeaterInterface) {
      throw new InvalidPluginTypeException('Scheduler repeater manager returned wrong plugin type: ' . get_class($repeater));
    }
    return $repeater;
  }

}
