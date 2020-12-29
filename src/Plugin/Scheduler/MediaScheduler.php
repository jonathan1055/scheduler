<?php
namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\Entity\MediaType;
use Drupal\scheduler\SchedulerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Class Node
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "media_scheduler",
 *  label = @Translation("Media Scheduler Plugin"),
 * )
 */
class MediaScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation')
    );
  }

  public function publish() {
    /** @var \Drupal\scheduler\SchedulerManager $scheduler_manager */
    $scheduler_manager = \Drupal::service('scheduler.manager');
    $scheduler_manager->publish();

  }
  public function unpublish() {
    /** @var \Drupal\scheduler\SchedulerManager $scheduler_manager */
    $scheduler_manager = \Drupal::service('scheduler.manager');
    $scheduler_manager->unpublish();
  }

  public function entityType() {
    return 'media';
  }

  public function getTypes() {
    if ( ! \Drupal::moduleHandler()->moduleExists('media')) {
      return [];
    }
    return MediaType::loadMultiple();
  }

  public function entityFormIDs() {

    if ( ! \Drupal::moduleHandler()->moduleExists('media')) {
      return [];
    }

    $ids = [
      'media_add_form',
      'media_edit_form',
    ];
    $types = MediaType::loadMultiple();
    /** @var MediaType $type */
    foreach ($types as $type) {
      $ids[] = 'media_' . $type->id() . '_form';
      $ids[] = 'media_' . $type->id() . '_add_form';
      $ids[] = 'media_' . $type->id() . '_edit_form';
    }
    return $ids;
  }

  public function entityTypeFormIDs() {

    if ( ! \Drupal::moduleHandler()->moduleExists('media')) {
      return [];
    }

    return [
      'media_type_add_form',
      'media_type_edit_form',
    ];
  }

  public function getEntityType($media) {

    if ( ! \Drupal::moduleHandler()->moduleExists('media')) {
      return null;
    }

    $bundle = $media->bundle();
    return  MediaType::load($bundle);
  }
}
