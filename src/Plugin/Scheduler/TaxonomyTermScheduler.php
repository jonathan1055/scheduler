<?php

namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;

/**
 * Plugin for Taxonomy Term entity type.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "taxonomy_term_scheduler",
 *  label = @Translation("Taxonomy Term Scheduler Plugin"),
 *  description = @Translation("Provides support for scheduling Taxonomy Term entities"),
 *  entityType = "taxonomy_term",
 *  typeFieldName = "vid",
 *  dependency = "taxonomy",
 *  develGenerateForm = "devel_generate_form_term",
 *  schedulerEventClass = "\Drupal\scheduler\Event\SchedulerTaxonomyTermEvents",
 * )
 */
class TaxonomyTermScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Get the available types/bundles for the entity type.
   *
   * Do not use static or drupal_static here, because changes to third-party
   * settings invalidate the saved values during phpunit testing.
   *
   * @return array
   *   The Taxonomy Term bundle objects (vocabularies), keyed by bundle name, or
   *   an empty array if Taxonomy is not enabled or there are no vocabularies.
   */
  public function getTypes() {
    if (!\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      return [];
    }
    return \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple();
  }

  /**
   * Get the form IDs for Taxonomy Term add/edit forms.
   *
   * @return array
   *   The list of form IDs, or an empty array if Taxonomy is not enabled.
   */
  public function entityFormIds() {
    if (!\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      return [];
    }
    static $ids;
    if (!isset($ids)) {
      $ids = [];
      $types = array_keys($this->getTypes());
      foreach ($types as $typeId) {
        $ids[] = 'taxonomy_term_' . $typeId . '_form';
      }
    }
    return $ids;
  }

  /**
   * Get the form IDs for Taxonomy Term type forms.
   *
   * @return array
   *   The list of form IDs, or an empty array if Taxonomy is not enabled.
   */
  public function entityTypeFormIds() {
    if (!\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      return [];
    }
    return [
      'taxonomy_vocabulary_form',
    ];
  }

}
