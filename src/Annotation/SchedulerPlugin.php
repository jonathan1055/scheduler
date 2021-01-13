<?php

namespace Drupal\scheduler\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation class for scheduler entity plugins.
 *
 * @package Drupal\scheduler\Annotation
 *
 * @Annotation
 */
class SchedulerPlugin extends Plugin {

  /**
   * The name of the plugin.
   *
   * @var string
   */
  public $label;

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

  /**
   * The entity type.
   *
   * @var string
   */
  public $entityType;

  /**
   * The name of the type/bundle field.
   *
   * @var string
   */
  public $typeFieldName;

  /**
   * Module name that plugin requires.
   *
   * @var string
   */
  public $dependency;

  /**
   * The name of the function used to get id list.
   *
   * @var string
   */
  public $idListFunction;

  /**
   * The Form ID of the devel generate form.
   *
   * @var string
   */
  public $develGenerateForm;

}
