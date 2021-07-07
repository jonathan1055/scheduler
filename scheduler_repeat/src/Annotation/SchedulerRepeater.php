<?php

namespace Drupal\scheduler_repeat\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines repeater plugin.
 *
 * @code
 * @SchedulerRepeater(
 *   id = "example",
 *   label = @Translation("Example Label"),
 *   weight = 1
 * )
 * @endcode
 *
 * @Annotation
 */
class SchedulerRepeater extends Plugin {

  /**
   * ID of the repeat plugin.
   *
   * @var string
   */
  public $id;

  /**
   * Label of the repeat plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Weight of the repeat plugin.
   *
   * This is used for sorting the plugins in the form selction list.
   *
   * @var int
   */
  public $weight;

}
