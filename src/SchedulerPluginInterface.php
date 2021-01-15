<?php

namespace Drupal\scheduler;

/**
 * Interface for Scheduler entity plugin definition.
 */
interface SchedulerPluginInterface {

  /**
   * Provide a description.
   *
   * @return mixed
   *   The description.
   */
  public function description();

  /**
   * Provide a label.
   *
   * @return mixed
   *   The label.
   */
  public function label();

  /**
   * Get the type of entity supported by this plugin.
   *
   * @return string
   *   The name of the entity type.
   */
  public function entityType();

  /**
   * Get module dependency.
   *
   * @return string
   *   The name of the required module.
   */
  public function dependency();

  /**
   * Get the name of the "type" field for the entity.
   *
   * @return string
   *   The name of the type/bundle field for this entity type.
   */
  public function typeFieldName();

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
  public function idListFunction();

  /**
   * Get the id of the Devel Generate form for this entity type.
   *
   * @return string
   *   The form id.
   */
  public function develGenerateForm();

  /**
   * Get the form IDs for entity add/edit forms.
   */
  public function entityFormIds();

  /**
   * Get the form IDs for entity type add/edit forms.
   */
  public function entityTypeFormIds();

  /**
   * Get all the type/bundle objects for this entity.
   *
   * @return array
   *   The type/bundle objects.
   */
  public function getTypes();

  /**
   * Get the types/bundles enabled for the specified $action.
   *
   * @param string $action
   *   The action - publish|unpublish.
   *
   * @return array
   *   The type/bundle objects enabled for the $action.
   */
  public function getEnabledTypes($action);

}
