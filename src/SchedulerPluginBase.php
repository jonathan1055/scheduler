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
