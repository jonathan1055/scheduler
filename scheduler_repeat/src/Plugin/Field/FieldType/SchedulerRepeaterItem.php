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
    $properties['previous_publish_on'] = DataDefinition::create('string')
      ->setLabel('Previous publish on')
      ->setDescription('Snapshot of publish_on timestmap at the time of scheduled publishing.');
    $properties['previous_unpublish_on'] = DataDefinition::create('string')
      ->setLabel('Previous unpublish on')
      ->setDescription('Snapshot of unpublish_on timestmap at the time of scheduled publishing.');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $plugin_id = $this->get('plugin_id')->getValue();
    return $plugin_id === NULL || $plugin_id === '' || $plugin_id === 'once';
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
          'maxMessage' => $this->t('%name: may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length]),
        ],
      ],
      'previous_publish_on' => [
        'Length' => [
          'max' => self::COLUMN_TIMESTAMP_MAX_LENGTH,
          'maxMessage' => $this->t('%name: may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length]),
        ],
      ],
      'previous_unpublish_on' => [
        'Length' => [
          'max' => self::COLUMN_TIMESTAMP_MAX_LENGTH,
          'maxMessage' => $this->t('%name: may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length]),
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
    $values['previous_publish_on'] = rand(strtotime("-20 days"), strtotime("-10 days"));
    $values['previous_unpublish_on'] = rand(strtotime("-10 days"), strtotime("-1 hour"));
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
        'previous_publish_on' => [
          'type' => 'varchar',
          'length' => self::COLUMN_TIMESTAMP_MAX_LENGTH,
          'not null' => TRUE,
        ],
        'previous_unpublish_on' => [
          'type' => 'varchar',
          'length' => self::COLUMN_TIMESTAMP_MAX_LENGTH,
          'not null' => TRUE,
        ],
      ]
    ];
  }

}
