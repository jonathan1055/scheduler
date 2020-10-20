<?php

namespace Drupal\scheduler_repeat;

class MissingOptionNodeException extends \Exception {
  // Used when node is not given and repeater logic can't be reliably executed
}
