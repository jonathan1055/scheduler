<?php

namespace Drupal\scheduler_repeat;

/**
 * Defines an exception for missing node object.
 *
 * This is thrown when the node is not passed in the $options parameter.
 *
 * @see SchedulerRepeaterBase::__construct()
 */
class MissingOptionNodeException extends \Exception {
}
