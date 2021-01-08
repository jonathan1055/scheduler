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

}
