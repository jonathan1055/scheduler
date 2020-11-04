<?php


namespace Drupal\scheduler_repeat\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;


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
   * ID of the recurring scheduler plugin.
   *
   * @var string
   */
  public $id;

  /**
   * Label of the recurring scheduler plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var Translation
   */
  public $label;

  /**
   * Helps sorting plugins and display them in consistent order.
   *
   * @var int
   */
  public $weight;

}
