<?php

namespace Drupal\scheduler_repeat\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'scheduler_repeater' formatter.
 *
 * @FieldFormatter(
 *   id = "scheduler_repeater_formatter",
 *   label = @Translation("Scheduler repeater"),
 *   field_types = {
 *     "scheduler_repeater"
 *   }
 * )
 */
class SchedulerRepeaterFormatter extends FormatterBase {

  /**
   * The Scheduler Repeat manager.
   *
   * @var
   */
  protected $pluginManager;

  /**
   * The Drupal Core date formatter.
   */
  protected $dateFormatter;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Render\Element\ContainerInterface $container
   *   Container which is being used to inject plugin manager and date
   *   formatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ContainerInterface $container) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->pluginManager = $container->get('plugin.manager.scheduler_repeat.repeater');
    $this->dateFormatter = $container->get('date.formatter');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'], $container);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $label = $this->pluginManager->getDefinition($item->plugin)['label'];
      $occurence = $this->renderOccurence($item->getEntity());
      $elements[$delta] = [
        '#markup' => Xss::filter("$label ($occurence)"),
      ];
    }
    return $elements;
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   *
   * @return string
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function renderOccurence(NodeInterface $node) {
    return $this->renderDate($node->get('scheduler_repeat')->next_publish_on) . ' - ' . $this->renderDate($node->get('scheduler_repeat')->next_unpublish_on);
  }

  /**
   * @param $timestamp
   *
   * @return string
   */
  protected function renderDate($timestamp) {
    // @todo Make it configurable
    return $this->dateFormatter->format($timestamp, 'short');
  }

}
