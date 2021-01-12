<?php

namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for Media entity type.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "media_scheduler",
 *  label = @Translation("Media Scheduler Plugin"),
 *  entityType = "media",
 *  typeFieldName = "bundle",
 *  dependency = "media",
 *  idListFunction = "scheduler_media_list",
 *  develGenerateForm = "devel_generate_form_media",
 * )
 */
class MediaScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {

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
   * Get the available types/bundles for the entity type.
   *
   * @return array
   *   The media bundle objects.
   */
  public function getTypes() {
    if (!\Drupal::moduleHandler()->moduleExists('media')) {
      return [];
    }
    $mediaTypes = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    return $mediaTypes;
  }

  /**
   * Get the form IDs for media add/edit forms.
   *
   * @return array
   *   The list of form IDs.
   */
  public function entityFormIds() {

    if (!\Drupal::moduleHandler()->moduleExists('media')) {
      return [];
    }

    $ids = [
      'media_add_form',
      'media_edit_form',
    ];
    $types = array_keys($this->getTypes());
    foreach ($types as $typeId) {
      $ids[] = 'media_' . $typeId . '_form';
      $ids[] = 'media_' . $typeId . '_add_form';
      $ids[] = 'media_' . $typeId . '_edit_form';
    }
    return $ids;
  }

  /**
   * Get the form IDs for media type forms.
   *
   * @return array
   *   The list of form IDs.
   */
  public function entityTypeFormIds() {

    if (!\Drupal::moduleHandler()->moduleExists('media')) {
      return [];
    }

    return [
      'media_type_add_form',
      'media_type_edit_form',
    ];
  }

}
