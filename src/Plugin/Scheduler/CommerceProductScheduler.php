<?php

namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;

/**
 * Plugin for Commerce Product entity type.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "commerce_product_scheduler",
 *  label = @Translation("Commerce Product Scheduler Plugin"),
 *  description = @Translation("Provides support for scheduling Commerce Product entities"),
 *  entityType = "commerce_product",
 *  typeFieldName = "type",
 *  dependency = "commerce_product",
 *  schedulerEventClass = "\Drupal\scheduler\Event\SchedulerCommerceProductEvents",
 *  publishAction = "commerce_publish_product",
 *  unpublishAction = "commerce_unpublish_product"
 * )
 */
class CommerceProductScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Get the available types/bundles for the entity type.
   *
   * Do not use static or drupal_static here, because changes to third-party
   * settings invalidate the saved values during phpunit testing.
   *
   * @return array
   *   The commerce product bundle objects, keyed by bundle name, or an empty
   *   array if Commerce is not enabled.
   */
  public function getTypes() {
    if (!\Drupal::moduleHandler()->moduleExists('commerce')) {
      return [];
    }
    $productTypes = \Drupal::entityTypeManager()->getStorage('commerce_product_type')->loadMultiple();
    return $productTypes;
  }

  /**
   * Get the form IDs for commerce product add/edit forms.
   *
   * @return array
   *   The list of form IDs, or an empty array if Commerce is not enabled.
   */
  public function entityFormIds() {
    if (!\Drupal::moduleHandler()->moduleExists('commerce')) {
      return [];
    }
    static $ids;
    if (!isset($ids)) {
      $ids = [];
      $types = array_keys($this->getTypes());
      foreach ($types as $typeId) {
        $ids[] = 'commerce_product_' . $typeId . '_add_form';
        $ids[] = 'commerce_product_' . $typeId . '_edit_form';
      }
    }
    return $ids;
  }

  /**
   * Get the form IDs for commerce product type forms.
   *
   * @return array
   *   The list of form IDs, or an empty array if Commerce is not enabled.
   */
  public function entityTypeFormIds() {
    if (!\Drupal::moduleHandler()->moduleExists('commerce')) {
      return [];
    }
    return [
      'commerce_product_type_add_form',
      'commerce_product_type_edit_form',
    ];
  }

}
