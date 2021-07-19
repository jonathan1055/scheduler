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
   * The Form ID of the devel generate form (optional).
   *
   * @var string
   */
  public $develGenerateForm = '';

  /**
   * The route of the scheduled view on the user profile page (optional).
   *
   * @var string
   */
  public $userViewRoute = '';

  /**
   * The event class for Scheduler events relating to activity on the entity.
   *
   * If not specified, it is assumed that the plugin is part of the Scheduler
   * module and the event class will default to Scheduler's namespace.
   *
   * @var string
   */
  public $schedulerEventClass;

  /**
   * The name of the publish action for the entity type (optional).
   *
   * This is used when the action name does not match the default pattern of
   * {entity type id}_publish_action.
   *
   * @var string
   */
  public $publishAction;

  /**
   * The name of the unpublish action for the entity type (optional).
   *
   * This is used when the action name does not match the default pattern of
   * {entity type id}_unpublish_action.
   *
   * @var string
   */
  public $unpublishAction;

}
