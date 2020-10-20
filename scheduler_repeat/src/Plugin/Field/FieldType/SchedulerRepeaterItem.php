<?php


namespace Drupal\scheduler_repeat\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of baz.
 *
 * @FieldType(
 *   id = "scheduler_repeater",
 *   label = @Translation("Scheduler Repeater"),
 *   default_formatter = "scheduler_repeater_formatter",
 *   default_widget = "scheduler_repeater_widget",
 * )
 */
class SchedulerRepeaterItem extends FieldItemBase implements FieldItemInterface {

  const MAX_VARCHAR_LENGTH = 255;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel('Repeat')
      ->setDescription('Specifies the plugin to be used for applying repeat logic.');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '' || $value === 'once';
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => self::MAX_VARCHAR_LENGTH,
            'maxMessage' => $this->t('%name: may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $options = [
      'hourly' => 'Hourly',
    ];
    $values['value'] = array_rand($options);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => self::MAX_VARCHAR_LENGTH,
          'not null' => TRUE,
        ]
      ]
    ];
  }

}
