<?php

namespace Drupal\scheduler;

/**
 * SchedulerPluginInterface definition.
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
