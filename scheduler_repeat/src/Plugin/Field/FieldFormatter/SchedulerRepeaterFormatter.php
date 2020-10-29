<?php


namespace Drupal\scheduler_repeat\Plugin\Field\FieldFormatter;


use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

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
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      // @todo Cannot have numeric keys, because when added to a view this gives:
      // User error: "0" is an invalid render array key in Drupal\Core\Render\Element::children()
      // line 97 of /Library/WebServer/Web/drupal89dev/core/lib/Drupal/Core/Render/Element.php
      // But what key should is be? is #value correct?
      $elements[$delta] = ['#value' => Xss::filter($item->plugin_id)];
    }
    return $elements;
  }

}
