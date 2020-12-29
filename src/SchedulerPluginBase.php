<?php

namespace Drupal\scheduler;

use Drupal\Core\Plugin\PluginBase;

/**
 * Abstract class for scheduler plugins.
 */
abstract class SchedulerPluginBase extends PluginBase implements SchedulerPluginInterface {

  /**
   * @inheritDoc
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

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
   * Publish pending entities.
   */
  abstract public function publish();

  /**
   * Unpublish pending entities.
   */
  abstract public function unpublish();

  /**
   * Get the bundle name for entity.
   */
  abstract public function entityType();

  /**
   * Get the form IDs for entity add/edit forms.
   */
  abstract public function entityFormIds();

  /**
   * Get the list of entity type form IDs.
   */
  abstract public function entityTypeFormIds();

}
