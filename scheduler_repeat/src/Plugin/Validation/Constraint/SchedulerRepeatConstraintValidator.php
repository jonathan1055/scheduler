<?php

namespace Drupal\scheduler_repeat\Plugin\Validation\Constraint;

use Drupal\scheduler_repeat\SchedulerRepeaterInterface;
use Drupal\scheduler_repeat\InvalidPluginTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 *
 */
class SchedulerRepeatConstraintValidator extends ConstraintValidator {

  protected $scheduler_repeater_manager;
  protected $node;
  protected $repeater;

  /**
   *
   */
  public function __construct() {
    $this->scheduler_repeater_manager = \Drupal::service('plugin.manager.scheduler_repeat.repeater');
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
    if ($this->publishesBeforeUnpublishing() || $this->publishesAtSameTimeWhenUnpublishing()) {
      $this->context->buildViolation($constraint->messageRepeatPeriodSmallerThanScheduledPeriod)
        ->atPath('scheduler_repeat')
        ->addViolation();
    }
  }

  /**
   * Determine when validation should be applied.
   *
   * @return bool
   */
  private function shouldValidate() {
    // We need both dates in order to validate potentially conflicting periods.
    // @todo Figure out how to handle $node that is in active scheduled period
    return !$this->node->get('publish_on')->isEmpty() && !$this->node->get('unpublish_on')->isEmpty();
  }

  /**
   * Determines if current $this->node tries to publish next time before it has
   * not even being unpublished.
   *
   * @return bool
   */
  protected function publishesBeforeUnpublishing() {
    return $this->repeater->calculateNextPublishedOn($this->getPublishOn()) < $this->getUnpublishOn();
  }

  /**
   * Determines if current $this->node tries to publish next time at the same
   * time it should be unpublishing.
   *
   * @return bool
   */
  protected function publishesAtSameTimeWhenUnpublishing() {
    return $this->repeater->calculateNextPublishedOn($this->getPublishOn()) == $this->getUnpublishOn();
  }

  /**
   * Gets publish_on that is being used in node given in constructor.
   *
   * @return mixed
   */
  protected function getPublishOn() {
    return $this->node->get('publish_on')->value;
  }

  /**
   * Gets unpublish_on that is being used in node given in constructor.
   *
   * @return mixed
   */
  protected function getUnpublishOn() {
    return $this->node->get('unpublish_on')->value;
  }

  /**
   * @param string $plugin_id
   *
   * @return \Drupal\scheduler_repeat\SchedulerRepeaterInterface
   * @throws \Drupal\scheduler_repeat\InvalidPluginTypeException
   *
   * @todo This duplicates fiunctionality done in _scheduler_repeat_get_repeater
   * Need to consolidate these? Also from node->plugin may need to get the
   * optional associated data. See _scheduler_repeat_get_repeater().
   */
  private function initializeRepeaterWithPlugin(string $plugin_id) {
    $repeater = $this->scheduler_repeater_manager->createInstance($plugin_id, ['node' => $this->node]);
    if (!$repeater instanceof SchedulerRepeaterInterface) {
      throw new InvalidPluginTypeException('Scheduler repeater manager returned wrong plugin type: ' . get_class($repeater));
    }
    return $repeater;
  }

}
