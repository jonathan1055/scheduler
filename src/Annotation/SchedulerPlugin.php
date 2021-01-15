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
   * The internal id / machine name of the plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
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
   * The name of the hook function used to get/alter the id list.
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
