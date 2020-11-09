<?php

namespace Drupal\scheduler_repeat\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'options_select' widget.
 *
 * @FieldWidget(
 *   id = "scheduler_repeater_widget",
 *   label = @Translation("Scheduler Repeat option list"),
 *   field_types = {
 *     "scheduler_repeater"
 *   }
 * )
 */
class SchedulerRepeaterWidget extends WidgetBase implements WidgetInterface {

  /**
   * The Scheduler Repeat plugin manager.
   *
   * @var \Drupal\scheduler_repeat\SchedulerRepeaterManager
   */
  protected $pluginManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container, $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    $this->pluginManager = $container->get('plugin.manager.scheduler_repeat.repeater');
    $this->dateFormatter = $container->get('date.formatter');
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container, $plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings']);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $formElement = [
      'plugin' => [
        '#title' => $this->t('Repeat schedule'),
        '#type' => 'select',
        '#default_value' => isset($items->get($delta)->plugin) ? $items->get($delta)->plugin : NULL,
        '#options' => $this->getRepeaterOptions(),
        '#empty_option' => $this->t('None'),
        '#empty_value' => 'none',
      ],
      'next_publish_on' => $this->addNextDateElement($this->t('Next publish on'), $items->get($delta)->next_publish_on),
      'next_unpublish_on' => $this->addNextDateElement($this->t('Next unpublish on'), $items->get($delta)->next_unpublish_on),
    ] + $element;
    return $formElement;
  }

  /**
   * Add a read-only form item to store and display the next date.
   *
   * @param Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The title of the date item.
   * @param int $value
   *   The date value, which may be empty.
   *
   * @return array
   *   A form element displaying the next date.
   */
  protected function addNextDateElement(TranslatableMarkup $title, $value) {
    // Create the element, even if the next date value is empty.
    $element = [
      '#type' => 'item',
      '#value' => $value,
    ];

    // If there is a value, display the title and the formatted value.
    if ($value) {
      $element['#title'] = $title;
      $element['#markup'] = $this->dateFormatter->format($value);
    }

    return $element;
  }

  /**
   * Get all Scheduler Repeat options.
   *
   * @return array
   *   An array of repeat options, for use in form selection element.
   */
  protected function getRepeaterOptions() {
    $plugin_definitions = $this->pluginManager->getDefinitions();
    // @todo Make the sorting more robust. If a plugin does not have 'weight' we
    // get error "array_multisort(): Array sizes are inconsistent".
    array_multisort(array_column($plugin_definitions, 'weight'), SORT_ASC, $plugin_definitions);
    $options = [];
    foreach ($plugin_definitions as $plugin_id => $plugin) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
      $label = $plugin['label'];
      $options[$plugin_id] = $label->render();
    }
    return $options;
  }

}
