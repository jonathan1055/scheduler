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

  const COLUMN_PLUGIN_MAX_LENGTH = 255;
  const COLUMN_TIMESTAMP_MAX_LENGTH = 20;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['plugin_id'] = DataDefinition::create('string')
      ->setLabel('Repeat')
      ->setDescription('Specifies the plugin to be used for applying repeat logic.');
    $properties['next_publish_on'] = DataDefinition::create('string')
      ->setLabel('Next publish on ')
      ->setDescription('The calculated next datet and time for scheduled publishing.');
    $properties['next_unpublish_on'] = DataDefinition::create('string')
      ->setLabel('Next unpublish on')
      ->setDescription('The calculated next datet and time for scheduled unpublishing.');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $plugin_id = $this->get('plugin_id')->getValue();
    return $plugin_id === NULL || $plugin_id === '' || $plugin_id === 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ComplexData', [
      'plugin_id' => [
        'Length' => [
          'max' => self::COLUMN_PLUGIN_MAX_LENGTH,
          'maxMessage' => $this->t('%name: may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => self::COLUMN_PLUGIN_MAX_LENGTH]),
        ],
      ],
      'next_publish_on' => [
        'Length' => [
          'max' => self::COLUMN_TIMESTAMP_MAX_LENGTH,
          'maxMessage' => $this->t('%name: may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => self::COLUMN_TIMESTAMP_MAX_LENGTH]),
        ],
      ],
      'next_unpublish_on' => [
        'Length' => [
          'max' => self::COLUMN_TIMESTAMP_MAX_LENGTH,
          'maxMessage' => $this->t('%name: may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => self::COLUMN_TIMESTAMP_MAX_LENGTH]),
        ],
      ],
    ]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $options = [
      'hourly' => 'Hourly',
    ];
    $values['plugin_id'] = array_rand($options);
    $values['next_publish_on'] = rand(strtotime("+1 day"), strtotime("+3 days"));
    // @todo Change unpublish time when we have more $options.
    $values['next_unpublish_on'] = $values['next_publish_on'] + rand(1, 3600);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'plugin_id' => [
          'type' => 'varchar',
          'length' => self::COLUMN_PLUGIN_MAX_LENGTH,
          'not null' => TRUE,
        ],
        'next_publish_on' => [
          'type' => 'varchar',
          'length' => self::COLUMN_TIMESTAMP_MAX_LENGTH,
          'not null' => TRUE,
        ],
        'next_unpublish_on' => [
          'type' => 'varchar',
          'length' => self::COLUMN_TIMESTAMP_MAX_LENGTH,
          'not null' => TRUE,
        ],
      ]
    ];
  }

}
