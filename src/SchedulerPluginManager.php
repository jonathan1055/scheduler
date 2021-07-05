<?php

namespace Drupal\scheduler;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\scheduler\Annotation\SchedulerPlugin;

/**
 * Provides a Scheduler Plugin Manager.
 *
 * @package Drupal\scheduler
 */
class SchedulerPluginManager extends DefaultPluginManager {

  /**
   * Constructor.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $module_handler) {

    $subdir = 'Plugin/Scheduler';
    $plugin_interface = SchedulerPluginInterface::class;
    $plugin_definition_annotation_name = SchedulerPlugin::class;

    parent::__construct(
      $subdir,
      $namespaces,
      $module_handler,
      $plugin_interface,
      $plugin_definition_annotation_name
    );

    $this->alterInfo('scheduler_info');
    $this->setCacheBackend($cacheBackend, 'scheduler_info');
  }

}
