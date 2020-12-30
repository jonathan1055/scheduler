<?php

namespace Drupal\scheduler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Abstract class for scheduler plugins.
 */
abstract class SchedulerPluginBase extends PluginBase implements SchedulerPluginInterface {

  /**
   * Description of plugin.
   *
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

  /**
   * Get list of Entity Type objects.
   *
   * @return array
   *   The list of entity type objects.
   */
  abstract public function getTypes();

  /**
   * Return the entity type object for a specific entity.
   *
   * @param EntityInterface $entity
   *   The entity.
   *
   * @return mixed
   *   The entity type object.
   */
  abstract public function getEntityType(EntityInterface $entity);

}
