<?php
namespace Drupal\scheduler\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Class SchedulerPlugin
 * @package Drupal\scheduler\Annotation
 *
 * @Annotation
 */
class SchedulerPlugin extends Plugin {

  /**
   * Description of plugin
   *
   * @var Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Plugin settings
   * @var array $settings
   */
  public $settings = [];
}
