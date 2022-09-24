<?php

namespace Drupal\scheduler\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;

/**
 * Plugin for Paragraphs entity type.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "paragraph_scheduler",
 *  label = @Translation("Paragraphs Scheduler Plugin"),
 *  description = @Translation("Provides support for scheduling paragraphs entities"),
 *  entityType = "paragraph",
 *  dependency = "paragraphs",
 * )
 */
class ParagraphScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {}
