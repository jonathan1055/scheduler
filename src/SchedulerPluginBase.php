<?php

namespace Drupal\scheduler;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for scheduler plugins.
 */
abstract class SchedulerPluginBase extends PluginBase implements SchedulerPluginInterface {

  /**
   * Get plugin label.
   *
   * @return string
   *   The label.
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Get the plugin description.
   *
   * @inheritDoc
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * Get the type of entity supported by this plugin.
   *
   * @return string
   *   The name of the entity type.
   */
  public function entityType() {
    return $this->pluginDefinition['entityType'];
  }

  /**
   * Get the name of the "type" field for the entity.
   *
   * @return string
   *   The name of the type/bundle field for this entity type.
   */
  public function typeFieldName() {
    return $this->pluginDefinition['typeFieldName'];
  }

  /**
   * Get module dependency.
   *
   * @return string
   *   The name of the required module.
   */
  public function dependency() {
    return $this->pluginDefinition['dependency'];
  }

  /**
   * Get the id of the Devel Generate form for this entity type.
   *
   * @return string
   *   The form id.
   */
  public function develGenerateForm() {
    // Some entity types may not have a Devel Generate form, so allow for none.
    return $this->pluginDefinition['develGenerateForm'] ?? NULL;
  }

  /**
   * Get the route of the scheduled view on the user profile page.
   *
   * @return string
   *   The route id.
   */
  public function userViewRoute() {
    // Some entity types may not have a user view, so allow for none.
    return $this->pluginDefinition['userViewRoute'] ?? NULL;
  }

  /**
   * Get the Scheduler event class.
   *
   * @return string
   *   The event class.
   */
  public function schedulerEventClass() {
    // If no class is defined in the plugin then default to the standard
    // scheduler class '\Drupal\scheduler\Event\Scheduler{Type}Events'.
    $class = $this->pluginDefinition['schedulerEventClass'] ??
      '\Drupal\scheduler\Event\Scheduler' . ucfirst($this->entityType()) . 'Events';
    return $class;
  }

  /**
   * Get the publish action name of the entity type.
   *
   * @return string
   *   The action name.
   */
  public function publishAction() {
    return $this->pluginDefinition['publishAction'] ?? NULL;
  }

  /**
   * Get the unpublish action name of the entity type.
   *
   * @return string
   *   The action name.
   */
  public function unpublishAction() {
    return $this->pluginDefinition['unpublishAction'] ?? NULL;
  }

  /**
   * Get all the type/bundle objects for this entity.
   *
   * @return array
   *   The type/bundle objects.
   */
  abstract public function getTypes();

  /**
   * Get the form IDs for entity add/edit forms.
   */
  abstract public function entityFormIds();

  /**
   * Get the form IDs for entity type add/edit forms.
   */
  abstract public function entityTypeFormIds();

}
