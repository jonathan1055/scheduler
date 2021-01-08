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
   * Get the type of entity supported by this plugin.
   *
   * @return string
   *   The name of the entity type.
   */
  public function entityType() {
    return $this->pluginDefinition['entityType'];
  }

  /**
   * Get module dependency.
   *
   * @return string
   *   The name of the entity type.
   */
  public function dependency() {
    return $this->pluginDefinition['dependency'];
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
   * Get the name of the id list hook function.
   *
   * Implementations of this hook function allow other modules to add to the
   * list of ids being published or unpublished. Further, implementations of
   * it's corresponding 'alter' function allow full manipulation of the list,
   * for adding and removing ids.
   *
   * @return string
   *   The hook function name.
   */
  public function idListFunction() {
    return $this->pluginDefinition['idListFunction'];
  }

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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return mixed
   *   The entity type object.
   */
  abstract public function getEntityType(EntityInterface $entity);

  /**
   * Get list of enabled bundles for the specified $action.
   *
   * @param string $action
   *   The action - publish|unpublish.
   *
   * @return array
   *   The list of bundles.
   */
  public function getEnabledTypes($action) {
    $config = \Drupal::config('scheduler.settings');
    $types = $this->getTypes();
    return array_filter($types, function ($bundle) use ($action, $config) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $bundle */
      return $bundle->getThirdPartySetting('scheduler', $action . '_enable', $config->get('default_' . $action . '_enable'));
    });
  }

}
