<?php

namespace Drupal\scheduler;

/**
 * Interface for Scheduler entity plugin definition.
 */
interface SchedulerPluginInterface {

  /**
   * Get the label.
   *
   * @return mixed
   *   The label.
   */
  public function label();

  /**
   * Get the description.
   *
   * @return mixed
   *   The description.
   */
  public function description();

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
   * Get the id of the Devel Generate form for this entity type.
   *
   * @return string
   *   The form id.
   */
  public function develGenerateForm();

  /**
   * Get all the type/bundle objects for this entity.
   *
   * @return array
   *   The type/bundle objects.
   */
  public function getTypes();

  /**
   * Get the form IDs for entity add/edit forms.
   */
  public function entityFormIds();

  /**
   * Get the form IDs for entity type add/edit forms.
   */
  public function entityTypeFormIds();

}
