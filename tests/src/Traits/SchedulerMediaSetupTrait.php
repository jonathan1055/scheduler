<?php

namespace Drupal\Tests\scheduler\Traits;

use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Additional setup trait for Scheduler tests that use Media.
 *
 * This builds on the standard SchedulerSetupTrait.
 */
trait SchedulerMediaSetupTrait {

  use MediaTypeCreationTrait;

  /**
   * The internal name of the standard media type created for testing.
   *
   * @var string
   */
  protected $mediaTypeName = 'test_video';

  /**
   * The readable label of the standard media type created for testing.
   *
   * @var string
   */
  protected $mediaTypeLabel = 'Test Video';

  /**
   * The media type object which is enabled for scheduling.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

  /**
   * The internal name of the media type not enabled for scheduling.
   *
   * @var string
   */
  protected $nonSchedulerMediaTypeName = 'test_audio_not_enabled';

  /**
   * The readable label of the media type not enabled for scheduling.
   *
   * @var string
   */
  protected $nonSchedulerMediaTypeLabel = 'Test Audio - not for scheduling';

  /**
   * The media type object which is not enabled for scheduling.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $nonSchedulerMediaType;

  /**
   * The media entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * Set common properties, define content types and create users.
   */
  public function schedulerMediaSetUp() {

    // Create a test media type for video that is enabled for scheduling.
    /** @var \Drupal\media\Entity\MediaTypeInterface $mediaType */
    $this->mediaType = $this->createMediaType('video_file', [
      'id' => $this->mediaTypeName,
      'label' => $this->mediaTypeLabel,
    ]);

    // Add scheduler functionality to the video media type.
    $this->mediaType->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Create a test media type for audio which is not enabled for scheduling.
    /** @var \Drupal\media\Entity\MediaTypeInterface $nonSchedulerMediaType */
    $this->nonSchedulerMediaType = $this->createMediaType('audio_file', [
      'id' => $this->nonSchedulerMediaTypeName,
      'label' => $this->nonSchedulerMediaTypeLabel,
    ]);

    // Define mediaStorage for use in many tests.
    /** @var MediaStorageInterface $mediaStorage */
    $this->mediaStorage = $this->container->get('entity_type.manager')->getStorage('media');

    // Add extra permisssions to the role assigned to the adminUser.
    $this->addPermissionsToUser($this->adminUser, [
      'create ' . $this->mediaTypeName . ' media',
      'edit any ' . $this->mediaTypeName . ' media',
      'delete any ' . $this->mediaTypeName . ' media',
      'create ' . $this->nonSchedulerMediaTypeName . ' media',
      'edit any ' . $this->nonSchedulerMediaTypeName . ' media',
      'delete any ' . $this->nonSchedulerMediaTypeName . ' media',
      'access media overview',
      'view own unpublished media',
      'schedule publishing of media',
      'view scheduled media',
    ]);

    // Add extra permisssions to the role assigned to the schedulerUser.
    $this->addPermissionsToUser($this->schedulerUser, [
      'create ' . $this->mediaTypeName . ' media',
      'edit own ' . $this->mediaTypeName . ' media',
      'delete own ' . $this->mediaTypeName . ' media',
      'view own unpublished media',
      'schedule publishing of media',
    ]);

    // By default, media items cannot be viewed directly, and the url media/mid
    // gives a 404 not found. Changing this setting makes debugging the tests
    // easier. It is also required for the meta information test.
    $configFactory = $this->container->get('config.factory');
    $configFactory->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);
    $this->container->get('router.builder')->rebuild();

    // Set the media file attachments to be optional not required, to simplify
    // editing and saving media entities.
    $configFactory->getEditable('field.field.media.test_video.field_media_video_file')
      ->set('required', FALSE)
      ->save(TRUE);
    $configFactory->getEditable('field.field.media.test_audio_not_enabled.field_media_audio_file')
      ->set('required', FALSE)
      ->save(TRUE);
  }

  /**
   * Creates a media entity.
   *
   * @param array $values
   *   The values to use for the entity.
   *
   * @return \Drupal\media\MediaInterface
   *   The created media object.
   */
  public function createMediaItem(array $values) {
    // Provide defaults for the critical values.
    $values += [
      'bundle' => $this->mediaTypeName,
      'name' => $this->randomstring(12),
    ];
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->mediaStorage->create($values);
    $media->save();
    return $media;
  }

  /**
   * Gets a media item from storage.
   *
   * For nodes, there is drupalGetNodeByTitle() but nothing similar exists to
   * help Media testing. But this function goes one better - if a name is given,
   * then a match will be attempted on the name, and fail if none found. But if
   * no name is supplied then the media entity with the highest id value (the
   * newest item created) is returned, as this is often what is required.
   *
   * @param string $name
   *   Optional name text to match on. If given and no match, returns NULL.
   *   If no $name is given then returns the media with the highest id value.
   *
   * @return \Drupal\media\MediaInterface
   *   The media object.
   */
  public function getMediaItem(string $name = NULL) {
    $query = $this->mediaStorage->getQuery()
      ->accessCheck(FALSE)
      ->sort('mid', 'DESC');
    if (!empty($name)) {
      $query->condition('name', $name);
    }
    $result = $query->execute();
    if (count($result)) {
      $media_id = reset($result);
      return $this->mediaStorage->load($media_id);
    }
    else {
      return NULL;
    }
  }

}
