<?php

namespace Drupal\Tests\scheduler\Traits;

use Drupal\Core\Session\AccountInterface;
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

    // Create a test media type that will be enabled for scheduling. Image files
    // are validated on attaching and saving, and generated files fail. But for
    // video files the only validation is the file extension, hence use video.
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
    // gives a 404 not found. Changing this setting makes testing easier.
    $configFactory = \Drupal::configFactory();
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
   * Adds a set of permissions to an existing user.
   *
   * This avoids having to create new users when a test requires additional
   * permissions, as that leads to having a list of existing permissions which
   * has to be kept in sync with the standard user permissions.
   *
   * Each test user has two roles, 'authenticated' and one other randomly-named
   * role assigned when the user is created, and unique to that user. This is
   * the role to which these permissions are added.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user object.
   * @param array $permissions
   *   The machine names of new permissions to add to the user's unique role.
   */
  public function addPermissionsToUser(AccountInterface $user, array $permissions) {
    /** @var \Drupal\user\Entity\RoleStorageInterface $roleStorage */
    $roleStorage = $this->container->get('entity_type.manager')->getStorage('user_role');
    foreach ($user->getRoles() as $rid) {
      // The user will have two roles, 'authenticated' and one other.
      if ($rid != 'authenticated') {
        $role = $roleStorage->load($rid);
        foreach ($permissions as $permission) {
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
   *   The name of the entity type, for example 'node' or 'media'.
   * @param string $bundle
   *   The name of the bundle. Optional, will default to $this->type for nodes
   *   or $this->mediaTypeName for media.
   * @param array $values
   *   Values for the new entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity object.
   */
  public function createEntity(string $entityType, string $bundle = NULL, array $values = []) {

    switch ($entityType) {
      case 'media':
        $values += ['bundle' => $bundle ?? $this->mediaTypeName];
        // For Media, the title is stored in the 'name' field, so get the title
        // when the 'name' is not defined, to allow the same $value parameters
        // as for Node.
        if (isset($values['title'])) {
          $values['name'] = $values['name'] ?? $values['title'];
          unset($values['title']);
        }
        $entity = $this->createMediaItem($values);
        break;

      case 'node':
      default:
        // For nodes the field for bundle is called 'type'.
        $values += ['type' => $bundle ?? $this->type];
        $entity = $this->drupalCreateNode($values);
        break;
    }
    return $entity;
  }

  /**
   * Returns the stored entity type object from a type id and bundle id.
   *
   * This allows previous usages of $this->nodetype to be replaced by
   * entityTypeObject($entityTypeId) or entityTypeObject($entityTypeId, $bundle)
   * when expanding tests to cover Media entities.
   *
   * @param string $entityTypeId
   *   The machine name of the entity type, for example 'node' or 'media'.
   * @param string $bundle
   *   The machine name of the bundle, for example 'testpage', 'test_video',
   *   'not_for_scheduler', etc. Optional. Defaults to the enabled bundle. Also
   *   accepts the fixed string 'non-enabled' to indicate the non-enabled bundle
   *   for the entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The stored entity type object.
   */
  public function entityTypeObject(string $entityTypeId, string $bundle = NULL) {
    switch (TRUE) {
      case ($entityTypeId == 'node' && (empty($bundle) || $bundle == $this->type)):
        return $this->nodetype;

      case ($entityTypeId == 'node' && ($bundle == 'non-enabled' || $bundle == $this->nonSchedulerType)):
        return $this->nonSchedulerNodeType;

      case ($entityTypeId == 'media' && (empty($bundle) || $bundle == $this->mediaTypeName)):
        return $this->mediaType;

      case ($entityTypeId == 'media' && ($bundle == 'non-enabled' || $bundle == $this->nonSchedulerMediaTypeName)):
        return $this->nonSchedulerMediaType;

      default:
        // Incorrect parameter values.
        throw new \Exception(sprintf('Unrecognised entityTypeId and bundle combination "%s" and "%s" passed to entityTypeObject()', $entityTypeId, $bundle));
    }
  }

  /**
   * Returns the storage object of the entity type passed by string.
   *
   * This allows previous usage of the hard-coded $this->nodeStorage to be
   * replaced with $this->entityStorageObject($entityType) when expanding the
   * tests to cover media entity types.
   *
   * @param string $entityTypeId
   *   The machine id of the entity type.
   *
   * @return \Drupal\Core\Entity\ContentEntityStorageInterface
   *   The entity storage object.
   */
  public function entityStorageObject(string $entityTypeId) {
    return ($entityTypeId == 'media') ? $this->mediaStorage : $this->nodeStorage;
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

  /**
   * Gets an entity by title, a direct replacement of drupalGetNodeByTitle().
   *
   * This allows the same test code to be run for Nodes and Media types.
   *
   * @param string $entityTypeId
   *   The machine id of the entity type, for example 'node' or 'media'.
   * @param string $title
   *   The title to match with.
   *
   * @return mixed
   *   Either a node object or a media object.
   */
  public function getEntityByTitle($entityTypeId, $title) {
    switch ($entityTypeId) {
      case 'node':
        return $this->drupalGetNodeByTitle($title);

      case 'media':
        return $this->getMediaItem($title);

      default:
        // Incorrect parameter value.
        throw new \Exception(sprintf('Unrecognised entityTypeId value "%s" passed to getEntityByTitle()', $entityTypeId));
    }
  }

  /**
   * Provides test data containing the standard entity types.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id].
   */
  public function dataStandardEntityTypes() {
    // The data provider has access to $this where the values are set in the
    // property definition.
    $data = [
      0 => ['node', $this->type],
      1 => ['media', $this->mediaTypeName],
    ];
    return $data;
  }

}
