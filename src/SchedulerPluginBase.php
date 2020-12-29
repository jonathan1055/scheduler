<?php

namespace Drupal\scheduler;

use Drupal\Core\Plugin\PluginBase;

abstract class SchedulerPluginBase extends PluginBase implements SchedulerPluginInterface {

  /**
   * @inheritDoc
   */
  public function description()
  {
    return $this->pluginDefinition['description'];
  }

  public function label() {
    return $this->pluginDefinition['label'];
  }

  abstract public function publish();

  abstract public function unpublish();

  abstract public function entityType();

  abstract public function entityFormIds();

  abstract public function entityTypeFormIds();
}
