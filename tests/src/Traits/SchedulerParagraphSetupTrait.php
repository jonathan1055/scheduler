<?php

namespace Drupal\Tests\scheduler\Traits;

use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Additional setup trait for Scheduler tests that use Paragraph.
 *
 * This builds on the standard SchedulerSetupTrait.
 */
trait SchedulerParagraphSetupTrait {

  use ParagraphsTestBaseTrait;
  // use ParagraphsType;

  /**
   * The internal name of the standard paragraph type created for testing.
   *
   * @var string
   */
  protected $paragraphTypeName = 'test_paragraph';

  /**
   * The readable label of the standard paragraph type created for testing.
   *
   * @var string
   */
  protected $paragraphTypeLabel = 'Test Paragraph';

  /**
   * The paragraph type object which is enabled for scheduling.
   *
   * @var \Drupal\paragraph\ParagraphTypeInterface
   */
  protected $paragraphType;

  /**
   * The internal name of the paragraph type not enabled for scheduling.
   *
   * @var string
   */
  protected $nonSchedulerParagraphTypeName = 'test_paragraph_not_enabled';

  /**
   * The readable label of the paragraph type not enabled for scheduling.
   *
   * @var string
   */
  protected $nonSchedulerParagraphTypeLabel = 'Other Test Paragraph - not for scheduling';

  /**
   * The paragraph type object which is not enabled for scheduling.
   *
   * @var \Drupal\paragraph\ParagraphTypeInterface
   */
  protected $nonSchedulerParagraphType;

  /**
   * The paragraph entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $paragraphStorage;

  /**
   * Set common properties, define content types and create users.
   */
  public function schedulerParagraphSetUp() {

    // Create a test paragraph type that is enabled for scheduling.
    /** @var \Drupal\paragraph\Entity\ParagraphTypeInterface $paragraphType */
    $this->paragraphType = ParagraphsType::create([
      'id' => $this->paragraphTypeName,
      'label' => $this->paragraphTypeLabel,
    ]);
    $this->paragraphType->save();

    // Add scheduler functionality to this paragraph type.
    $this->paragraphType->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Create a test paragraph type which is not enabled for scheduling.
    /** @var \Drupal\paragraph\Entity\ParagraphTypeInterface $nonSchedulerParagraphType */
    $this->nonSchedulerParagraphType = ParagraphsType::create([
      'id' => $this->nonSchedulerParagraphTypeName,
      'label' => $this->nonSchedulerParagraphTypeLabel,
    ]);
    $this->nonSchedulerParagraphType->save();

    // Define paragraphStorage for use in many tests.
    /** @var ParagraphStorageInterface $paragraphStorage */
    $this->paragraphStorage = $this->container->get('entity_type.manager')->getStorage('paragraph');

    // Add extra permisssions to the role assigned to the adminUser.
    $this->addPermissionsToUser($this->adminUser, [
      'create ' . $this->paragraphTypeName . ' paragraph',
      'edit any ' . $this->paragraphTypeName . ' paragraph',
      'delete any ' . $this->paragraphTypeName . ' paragraph',
      'create ' . $this->nonSchedulerParagraphTypeName . ' paragraph',
      'edit any ' . $this->nonSchedulerParagraphTypeName . ' paragraph',
      'delete any ' . $this->nonSchedulerParagraphTypeName . ' paragraph',
      'administer paragraph types',
      'access paragraph overview',
      'view own unpublished paragraph',
      'schedule publishing of paragraph',
      'view scheduled paragraph',
    ]);

    // Add extra permisssions to the role assigned to the schedulerUser.
    $this->addPermissionsToUser($this->schedulerUser, [
      'create ' . $this->paragraphTypeName . ' paragraph',
      'edit own ' . $this->paragraphTypeName . ' paragraph',
      'delete own ' . $this->paragraphTypeName . ' paragraph',
      'view own unpublished paragraph',
      'schedule publishing of paragraph',
    ]);


    // Set the paragraph file attachments to be optional not required, to simplify
    // editing and saving paragraph entities.
    // $configFactory->getEditable('field.field.paragraph.test_video.field_paragraph_video_file')
    //   ->set('required', FALSE)
    //   ->save(TRUE);
    // $configFactory->getEditable('field.field.paragraph.test_audio_not_enabled.field_paragraph_audio_file')
    //   ->set('required', FALSE)
    //   ->save(TRUE);
  }

  /**
   * Creates a paragraph entity.
   *
   * @param array $values
   *   The values to use for the entity.
   *
   * @return \Drupal\paragraph\ParagraphInterface
   *   The created paragraph object.
   */
  public function createParagraph(array $values) {
    // Provide defaults for the critical values. The title is stored in the
    // 'name' field, so use 'title' when the 'name' is not defined, to allow
    // the same calling $value parameter names as for Node.
    $values += [
      'bundle' => $this->paragraphTypeName,
      'name' => $values['title'] ?? $this->randomstring(12),
    ];
    /** @var \Drupal\paragraph\ParagraphInterface $paragraph */
    $paragraph = $this->paragraphStorage->create($values);
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Gets a paragraph from storage.
   *
   * For nodes, there is drupalGetNodeByTitle() but nothing similar exists to
   * help Paragraph testing. But this function goes one better - if a name is given,
   * then a match will be attempted on the name, and fail if none found. But if
   * no name is supplied then the paragraph entity with the highest id value (the
   * newest paragraph created) is returned, as this is often what is required.
   *
   * @param string $name
   *   Optional name text to match on. If given and no match, returns NULL.
   *   If no $name is given then returns the paragraph with the highest id value.
   *
   * @return \Drupal\paragraph\ParagraphInterface
   *   The paragraph object.
   */
  public function getParagraph(string $name = NULL) {
    $query = $this->paragraphStorage->getQuery()
      ->accessCheck(FALSE)
      ->sort('mid', 'DESC');
    if (!empty($name)) {
      $query->condition('name', $name);
    }
    $result = $query->execute();
    if (count($result)) {
      $paragraph_id = reset($result);
      return $this->paragraphStorage->load($paragraph_id);
    }
    else {
      return NULL;
    }
  }

}
