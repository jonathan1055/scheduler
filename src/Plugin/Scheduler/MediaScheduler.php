<?php

namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;

/**
 * Plugin for Media entity type.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "media_scheduler",
 *  label = @Translation("Media Scheduler Plugin"),
 *  description = @Translation("Provides support for scheduling media entities"),
 *  entityType = "media",
 *  typeFieldName = "bundle",
 *  dependency = "media",
 *  develGenerateForm = "devel_generate_form_media",
 *  userViewRoute = "view.scheduler_scheduled_media.user_page",
 * )
 */
class MediaScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Get the available types/bundles for the entity type.
   *
   * Do not use static or drupal_static here, because changes to third-party
   * settings invalidate the saved values during phpunit testing.
   *
   * @return array
   *   The media bundle objects, keyed by bundle name, or an empty array if
   *   Media is not enabled.
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
   *   The list of form IDs, or an empty array if Media is not enabled.
   */
  public function entityFormIds() {
    if (!\Drupal::moduleHandler()->moduleExists('media')) {
      return [];
    }
    static $ids;
    if (!isset($ids)) {
      $ids = [];
      $types = array_keys($this->getTypes());
      foreach ($types as $typeId) {
        $ids[] = 'media_' . $typeId . '_add_form';
        $ids[] = 'media_' . $typeId . '_edit_form';
      }
    }
    return $ids;
  }

  /**
   * Get the form IDs for media type forms.
   *
   * @return array
   *   The list of form IDs, or an empty array if Media is not enabled.
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
