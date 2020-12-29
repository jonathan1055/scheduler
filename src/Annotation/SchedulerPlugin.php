<?php

namespace Drupal\scheduler\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class SchedulerPlugin.
 *
 * @package Drupal\scheduler\Annotation
 *
 * @Annotation
 */
class SchedulerPlugin extends Plugin {

  /**
   * Description of plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Plugin settings.
   *
   * @var array
   */
  public $settings = [];

}
