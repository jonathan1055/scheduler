<?php

namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\scheduler\SchedulerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Node.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "media_scheduler",
 *  label = @Translation("Media Scheduler Plugin"),
 * )
 */
class MediaScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Create mothod.
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
   * Publish pending media entities.
   */
  public function publish() {
    // @todo - this will likely be handled in the ScheduleManager service.
  }

  /**
   * Unpublish pending media entities.
   */
  public function unpublish() {
    // @todo - this will likely be handled in the ScheduleManager service.
  }

  /**
   * Get the type of entity supported by this plugin.
   *
   * @return string
   *   The entity type name.
   */
  public function entityType() {
    return 'media';
  }

  /**
   * Get the available bundles for the entity type.
   *
   * @return array
   *   The list of bundles.
   */
  public function getTypes() {
    if (!\Drupal::moduleHandler()->moduleExists('media')) {
      return [];
    }
    return MediaType::loadMultiple();
  }

  /**
   * Get the form IDs for entity add/edit forms.
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
    $types = MediaType::loadMultiple();
    /** @var \Drupal\media\Entity\MediaType $type */
    foreach ($types as $type) {
      $ids[] = 'media_' . $type->id() . '_form';
      $ids[] = 'media_' . $type->id() . '_add_form';
      $ids[] = 'media_' . $type->id() . '_edit_form';
    }
    return $ids;
  }

  /**
   * Get the list of entity type form IDs for media.
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

  /**
   * Get the bundle name for $media.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media.
   *
   * @return string
   *   The bundle.
   */
  public function getEntityType(Media $media) {

    if (!\Drupal::moduleHandler()->moduleExists('media')) {
      return NULL;
    }

    $bundle = $media->bundle();
    return MediaType::load($bundle);
  }

}
