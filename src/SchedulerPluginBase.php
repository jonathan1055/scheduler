<?php

namespace Drupal\scheduler;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for scheduler plugins.
 */
abstract class SchedulerPluginBase extends PluginBase implements SchedulerPluginInterface {

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation')
    );
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
   *   The form id, or an empty string if none.
   */
  public function develGenerateForm() {
    return $this->pluginDefinition['develGenerateForm'];
  }

  /**
   * Get the route of the scheduled view on the user profile page.
   *
   * @return string
   *   The form id, or an empty string if none.
   */
  public function userViewRoute() {
    return $this->pluginDefinition['userViewRoute'];
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
   * If no value is given in the plugin annotation then default to the commonly
   * used {entity type id}_publish_action.
   *
   * @return string
   *   The action name.
   */
  public function publishAction() {
    return $this->pluginDefinition['publishAction'] ?? $this->entityType() . '_publish_action';
  }

  /**
   * Get the unpublish action name of the entity type.
   *
   * If no value is given in the plugin annotation then default to the commonly
   * used {entity type id}_unpublish_action.
   *
   * @return string
   *   The action name.
   */
  public function unpublishAction() {
    return $this->pluginDefinition['unpublishAction'] ?? $this->entityType() . '_unpublish_action';
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
