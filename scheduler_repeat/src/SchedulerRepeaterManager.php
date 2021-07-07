<?php

namespace Drupal\scheduler_repeat;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\scheduler_repeat\Annotation\SchedulerRepeater;

/**
 * Plugin type manager for scheduler repeat plugin options.
 *
 * @ingroup scheduler_repeat
 */
class SchedulerRepeaterManager extends DefaultPluginManager {

  /**
   * Constructs a SchedulerRepeatManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Needed by DefaultPluginManager for hooks.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SchedulerRepeater', $namespaces, $module_handler, SchedulerRepeaterInterface::class, SchedulerRepeater::class);
    $this->setCacheBackend($cache_backend, 'scheduler_repeat');
  }

}
