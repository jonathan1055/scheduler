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
      $elements[$delta] = Xss::filter($item->plugin_id);
    }
    return $elements;
  }

}
