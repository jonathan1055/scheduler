<?php

namespace Drupal\scheduler;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for scheduler plugins.
 */
abstract class SchedulerPluginBase extends PluginBase implements SchedulerPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A static cache of create/edit entity form IDs.
   *
   * @var string[]
   */
  protected $entityFormIds;

  /**
   * A static cache of create/edit entity type form IDs.
   *
   * @var string[]
   */
  protected $entityTypeFormIds;

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
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
   * Get the route of the entity collection page.
   *
   * @return string
   *   The route. Defaults to entity.{entityType}.collection.
   */
  public function collectionRoute() {
    return $this->pluginDefinition['collectionRoute'] ?? "entity.{$this->entityType()}.collection";
  }

  /**
   * Get the route of the scheduled view on the user profile page.
   *
   * @return string
   *   The route, or blank if none.
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
    // If no event class is defined in the plugin then it will default to
    // '\Drupal\scheduler\Event\Scheduler{entityType}Events'. Specifying an
    // event class is only required when the entityType value contains an
    // underscore because that produces an invalid class name.
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
   * Get the field name for the 'type' or 'bundle'.
   *
   * @return string
   *   The name of the type/bundle field for this entity type.
   */
  public function typeFieldName() {
    return $this->entityTypeManager
      ->getDefinition($this->entityType())
      ->getKey('bundle');
  }

  /**
   * Get all the type/bundle objects for this entity.
   *
   * @return array
   *   The type/bundle objects.
   */
  public function getTypes() {
    $bundleDefinition = $this->entityTypeManager
      ->getDefinition($this->entityType())
      ->getBundleEntityType();

    return $this->entityTypeManager
      ->getStorage($bundleDefinition)
      ->loadMultiple();
  }

  /**
   * Get the form IDs for entity add/edit forms.
   */
  public function entityFormIds() {
    if (isset($this->entityFormIds)) {
      return $this->entityFormIds;
    }

    return $this->entityFormIds = $this->entityFormIdsByType($this->entityType());
  }

  /**
   * Get the form IDs for entity type add/edit forms.
   */
  public function entityTypeFormIds() {
    if (isset($this->entityTypeFormIds)) {
      return $this->entityTypeFormIds;
    }

    $bundleEntityType = $this->entityTypeManager
      ->getDefinition($this->entityType())
      ->getBundleEntityType();

    return $this->entityTypeFormIds = $this->entityFormIdsByType($bundleEntityType);
  }

  /**
   * Get the form IDs for the add/edit forms of a certain entity type.
   *
   * The logic for this function is based on EntityForm::getFormId.
   *
   * @see \Drupal\Core\Entity\EntityForm::getFormId()
   */
  protected function entityFormIdsByType(string $entityType): array {
    $ids = [];
    $definition = $this->entityTypeManager->getDefinition($entityType);
    $operations = [];

    // Some entity types, such as node, do not have 'add' in the add form id.
    if ($definition->getFormClass('add')) {
      $operations[] = 'add';
    }
    else {
      $operations[] = 'default';
    }
    // Some entity types, for example taxonomy_vocabulary and taxonomy_term, do
    // not have a separate edit form.
    if ($definition->getFormClass('edit')) {
      $operations[] = 'edit';
    }

    $types = array_keys($this->getTypes());
    foreach ($types as $typeId) {
      foreach ($operations as $operation) {
        $form_id = $entityType . '_' . $typeId;
        if ($operation != 'default') {
          $form_id .= '_' . $operation;
        }
        $ids[] = $form_id . '_form';
      }
    }

    return array_unique($ids);
  }

}
