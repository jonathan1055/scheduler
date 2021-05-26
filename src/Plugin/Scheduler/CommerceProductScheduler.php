<?php

namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for Commerce Product entity type.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "commerce_product__scheduler",
 *  label = @Translation("Commerce Product Scheduler Plugin"),
 *  description = @Translation("Provides support for scheduling commerce product entities"),
 *  entityType = "commerce_product",
 *  typeFieldName = "type",
 *  dependency = "commerce",
 *  schedulerEventClass = "\Drupal\scheduler\Event\SchedulerCommerceProductEvents",
 *  develGenerateForm = "devel_generate_form_commerce_product",
 *  userViewRoute = "view.scheduler_scheduled_commerce_products.user_page",
 *  publishAction = "commerce_publish_product",
 *  unpublishAction = "commerce_unpublish_product"
 * )
 */
class CommerceProductScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation')
    );
  }

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
