<?php

namespace Drupal\scheduler_repeat;

/**
 * Defines an exception for the wrong type of repeater plugin.
 *
 * This is thrown when the Scheduler Repeat manager returns a wrong type of
 * repeater plugin.
 *
 * @see SchedulerRepeatConstraintValidator::initializeRepeaterWithPlugin()
 */
class InvalidPluginTypeException extends \Exception {}
