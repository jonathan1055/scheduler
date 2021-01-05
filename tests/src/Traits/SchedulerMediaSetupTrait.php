<?php

namespace Drupal\Tests\scheduler\Traits;

use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Additonal setup trait for Scheduler tests that use Media.
 *
 * This builds on the standard SchedulerSetupTrait.
 */
trait SchedulerMediaSetupTrait {

  use SchedulerSetupTrait;
  use MediaTypeCreationTrait;

  /**
   * The internal name of the standard media type created for testing.
   *
   * @var string
   */
  protected $mediaTypeName;

  /**
   * The readable label of the standard media type created for testing.
   *
   * @var string
   */
  protected $mediaTypeLabel;

  /**
   * The media type object.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

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
    // Create a test media type for images.
    $this->mediaTypeName = 'test_media_image';
    $this->mediaTypeLabel = 'Test Media Image';
    /** @var MediaTypeInterface $mediaType */
    $this->mediaType = $this->createMediaType('image', [
      'id' => $this->mediaTypeName,
      'label' => $this->mediaTypeLabel,
    ]);

    // Add scheduler functionality to the media type.
    $this->mediaType->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Define mediaStorage for use in many tests.
    /** @var MediaStorageInterface $mediaStorage */
    $this->mediaStorage = $this->container->get('entity_type.manager')->getStorage('media');

    // Create an administrator user having the main admin permissions, full
    // rights on all the test types and all of the Scheduler permissions.
    // This will replace the adminUser created in SchedulerSetupTrait.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access content overview',
      'access site reports',
      'administer nodes',
      'administer site configuration',
      'create ' . $this->type . ' content',
      'edit any ' . $this->type . ' content',
      'delete any ' . $this->type . ' content',
      'create ' . $this->nonSchedulerNodeType->id() . ' content',
      'edit any ' . $this->nonSchedulerNodeType->id() . ' content',
      'delete any ' . $this->nonSchedulerNodeType->id() . ' content',
      'create ' . $this->mediaTypeName . ' media',
      'edit any ' . $this->mediaTypeName . ' media',
      'delete any ' . $this->mediaTypeName . ' media',
      'view own unpublished content',
      'administer scheduler',
      'schedule publishing of nodes',
      'view scheduled content',
    ]);

    // Create an ordinary Scheduler user, with permission to create and schedule
    // all entity types but not with administrator permissions. This will
    // replace the schedulerUser created in SchedulerSetupTrait.
    $this->schedulerUser = $this->drupalCreateUser([
      'create ' . $this->type . ' content',
      'edit own ' . $this->type . ' content',
      'delete own ' . $this->type . ' content',
      'create ' . $this->mediaTypeName . ' media',
      'edit any ' . $this->mediaTypeName . ' media',
      'delete any ' . $this->mediaTypeName . ' media',
      'view own unpublished content',
      'schedule publishing of nodes',
      'view scheduled content',
    ]);
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

}
