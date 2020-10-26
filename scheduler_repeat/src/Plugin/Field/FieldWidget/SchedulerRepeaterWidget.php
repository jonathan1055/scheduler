<?php


namespace Drupal\scheduler_repeat\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'options_select' widget.
 *
 * @FieldWidget(
 *   id = "scheduler_repeater_widget",
 *   label = @Translation("Available repeat options"),
 *   field_types = {
 *     "scheduler_repeater"
 *   }
 * )
 */

class SchedulerRepeaterWidget extends WidgetBase implements WidgetInterface {

  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container, $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    $this->pluginManager = $container->get('plugin.manager.scheduler_repeat.repeater');
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
    $element['plugin_id'] = $element + [
      '#type' => 'select',
      '#default_value' => isset($items[$delta]->plugin_id) ? $items[$delta]->plugin_id : NULL,
      '#options' => $this->getRepeaterOptions(),
      '#empty_option' => $this->t('Once'),
      '#empty_value' => 'once',
    ];
    return $element;
  }

  protected function getRepeaterOptions() {
    $plugin_definitions = $this->pluginManager->getDefinitions();
    $options = [];
    foreach ($plugin_definitions as $plugin_id => $plugin) {
      /** @var TranslatableMarkup $label */
      $label = $plugin['label'];
      $options[$plugin_id] = $label->render();
    }
    return $options;
  }

}
