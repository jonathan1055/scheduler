<?php

namespace Drupal\scheduler;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permisions for scheduler plugins.
 */
class SchedulerPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The scheduler manager service.
   *
   * @var SchedulerManager
   */
  private $schedulerManager;

  /**
   * Constructs a \Drupal\scheduler\SchedulerPermissions instance.
   */
  public function __construct(SchedulerManager $schedulerManager) {
    $this->schedulerManager = $schedulerManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('scheduler.manager'));
  }

  /**
   * Build permissions for each entity type.
   *
   * @return array|array[]
   *   The list of permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function permissions() {
    $permissions = [];
    $plugins = $this->schedulerManager->getPluginEntityTypes();
    foreach ($plugins as $type) {
      // Nodes are already handled in permissions.yml.
      if ($type == 'node') {
        continue;
      }
      $permissions += [
        'schedule publishing of ' . $type => [
          'title' => $this->t('Schedule publishing of %type', ['%type' => ucfirst($type)]),
          'description' => $this->t('Allows users to set a start and end time for %type publication', ['%type' => ucfirst($type)]),
        ],
      ];
    }
    return $permissions;
  }

}
