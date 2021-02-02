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

    /** @var \Drupal\user\Entity\RoleStorageInterface $roleStorage */
    $roleStorage = $this->container->get('entity_type.manager')->getStorage('user_role');

    // Add extra permisssions to the admin role assigned to the adminUser.
    $admin_media_permissions = [
      'create ' . $this->mediaTypeName . ' media',
      'edit any ' . $this->mediaTypeName . ' media',
      'delete any ' . $this->mediaTypeName . ' media',
      'create media',
      'update any media',
      'delete any media',
      'schedule publishing of media',
    ];
    foreach ($this->adminUser->getRoles() as $rid) {
      // The user will have two roles, 'authenticated' and one other.
      if ($rid != 'authenticated') {
        $role = $roleStorage->load($rid);
        foreach ($admin_media_permissions as $permission) {
          $role->grantPermission($permission);
        }
        $role->save();
      }
    }

    // Add extra permisssions to the role assigned to the schedulerUser.
    $user_media_permissions = [
      'create ' . $this->mediaTypeName . ' media',
      'edit any ' . $this->mediaTypeName . ' media',
      'delete any ' . $this->mediaTypeName . ' media',
      'schedule publishing of media',
    ];
    foreach ($this->schedulerUser->getRoles() as $rid) {
      // The user will have two roles, 'authenticated' and one other.
      if ($rid != 'authenticated') {
        $role = $roleStorage->load($rid);
        foreach ($user_media_permissions as $permission) {
          $role->grantPermission($permission);
        }
        $role->save();
      }
    }

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
   * Creates a test entity.
   *
   * This is called to generate a node or a media entity, for tests that process
   * both types of entities, either in loops or via a data provider.
   *
   * @param string $entityType
   *   The name of the entity type.
   * @param string $bundle
   *   The name of the bundle.
   * @param array $values
   *   Values for the new entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity object.
   */
  public function createEntity($entityType, $bundle = NULL, array $values = []) {

    switch ($entityType) {
      case 'media':
        $bundle = $bundle ?? $this->mediaTypeName;
        $values += ['type' => $bundle];
        $entity = $this->createMediaItem($values);
        break;

      case 'node':
      default:
        $bundle = $bundle ?? $this->type;
        $values += ['type' => $bundle];
        $entity = $this->drupalCreateNode($values);
        break;
    }
    return $entity;
  }

  /**
   * Returns the stored entity type object from a type name string.
   *
   * This allows previous usage of the hard-coded $this->nodetype to be
   * replaced with $this->entityTypeObject($entityType) when expanding the tests
   * to cover media entity types.
   *
   * @param string $entityType
   *   The name of the entity.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The stored entity type object.
   */
  public function entityTypeObject($entityType) {
    // The properties are case-sensitive and do not follow the same pattern.
    return ($entityType == 'media') ? $this->mediaType : $this->nodetype;
  }

}
