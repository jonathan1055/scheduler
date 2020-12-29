<?php

namespace Drupal\scheduler;

interface SchedulerPluginInterface {

  /**
   * Provide a description.
   *
   * @return mixed
   */
  public function description();

  /**
   * Provide a label.
   *
   * @return mixed
   */
  public function label();
}
