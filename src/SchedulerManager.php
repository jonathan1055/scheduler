<?php

namespace Drupal\scheduler;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Defines a scheduler manager.
 */
class SchedulerManager {

  use StringTranslationTrait;

  /**
   * Date formatter service object.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Scheduler Logger service object.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Module handler service object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Entity Type Manager service object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config Factory service object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Entity Field Manager service object.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * Scheduler Plugin Manager service object.
   *
   * @var SchedulerPluginManager
   */
  private $pluginManager;

  /**
   * Constructs a SchedulerManager object.
   */
  public function __construct(DateFormatterInterface $dateFormatter,
                              LoggerInterface $logger,
                              ModuleHandlerInterface $moduleHandler,
                              EntityFieldManagerInterface $entityFieldManager,
                              EntityTypeManagerInterface $entityTypeManager,
                              ConfigFactoryInterface $configFactory,
                              ContainerAwareEventDispatcher $eventDispatcher,
                              TimeInterface $time,
                              SchedulerPluginManager $pluginManager
  ) {
    $this->dateFormatter = $dateFormatter;
    $this->logger = $logger;
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->eventDispatcher = $eventDispatcher;
    $this->time = $time;
    $this->entityFieldManager = $entityFieldManager;
    $this->pluginManager = $pluginManager;
  }

  /**
   * Dispatch a Scheduler event.
   *
   * All Scheduler events should be dispatched through this common function.
   *
   * Drupal 8.8 and 8.9 use Symfony 3.4 and from Drupal 9.0 the Symfony version
   * is 4.4. Starting with Symfony 4.3 the signature of the event dispatcher
   * function has the parameters swapped round, the event object is first,
   * followed by the event name string. At 9.0 the existing signature has to be
   * used but from 9.1 the parameters must be switched.
   *
   * @param \Drupal\Component\EventDispatcher\Event $event
   *   The event object.
   * @param string $event_name
   *   The text name for the event.
   *
   * @see https://www.drupal.org/project/scheduler/issues/3166688
   */
  public function dispatch(Event $event, string $event_name) {
    // \Symfony\Component\HttpKernel\Kernel::VERSION will give the symfony
    // version. However, testing this does not give the required outcome, we
    // need to test the Drupal core version.
    // @todo Remove the check when Core 9.1 is the lowest supported version.
    if (version_compare(\Drupal::VERSION, '9.1', '>=')) {
      // The new way, with $event first.
      $this->eventDispatcher->dispatch($event, $event_name);
    }
    else {
      // Replicate the existing dispatch signature.
      $this->eventDispatcher->dispatch($event_name, $event);
    }
  }

  /**
   * Dispatches a Scheduler event for an entity.
   *
   * This function dispatches a Scheduler event, identified by $event_id, for
   * the entity type of the provided $entity. Each entity type has its own
   * events class Scheduler{EntityType}Events, for example SchedulerNodeEvents,
   * SchedulerMediaEvents, etc. This class contains constants (with mames
   * matching the $event_id parameter) which uniquely define the final event
   * name string to be dispatched. The actual event object dispatched is always
   * of class SchedulerEvent.
   *
   * The $entity is passed by reference so that any changes made in the event
   * subsriber implementations are automatically stored and passed forward.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $event_id
   *   The short text id the event, for example 'PUBLISH' or 'PRE_UNPUBLISH'.
   */
  public function dispatchEvents(EntityInterface &$entity, string $event_id) {
    // Build a string to hold fully named-spaced events class name, for use in
    // the constant() function.
    $event_class = '\Drupal\scheduler\Scheduler' . ucfirst($entity->getEntityTypeId()) . 'Events';
    $event_name = constant("$event_class::$event_id");

    // Create the event object and dispatch the required event_name.
    $event = new SchedulerEvent($entity);
    $this->dispatch($event, $event_name);
    $entity = $event->getEntity();
  }

  /**
   * Handles throwing exceptions.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity causing the exepction.
   * @param string $exception_name
   *   Which exception to throw.
   * @param string $action
   *   The action being performed (publish|unpublish).
   *
   * @throws \Drupal\scheduler\Exception\SchedulerEntityTypeNotEnabledException
   */
  private function throwSchedulerException(EntityInterface $entity, $exception_name, $action) {
    $plugin = $this->getPlugin($entity->getEntityTypeId());

    // Exception messages are developer-facing and do not need to be translated
    // from English. So it is accpetable to create words such as "{$action}ed"
    // and "{$action}ing".
    switch ($exception_name) {
      case 'SchedulerEntityTypeNotEnabledException':
        $message = "'%s' (id %d) was not %s because %s %s '%s' is not enabled for scheduled %s. Check %s. Processing halted";
        $p1 = $entity->label();
        $p2 = $entity->id();
        $p3 = "{$action}ed";
        $p4 = $entity->getEntityTypeId();
        $p5 = $plugin->typeFieldName();
        $p6 = $entity->{$plugin->typeFieldName()}->entity->label();
        $p7 = "{$action}ing";
        // Get a list of the hook function implementations, as one of these will
        // have caused this exception.
        $hooks = [$plugin->idListFunction(), $plugin->idListFunction() . '_alter'];
        $implementations = [];
        foreach ($hooks as $hook) {
          foreach ($this->moduleHandler->getImplementations($hook) as $module) {
            $implementations[] = $module . '_' . $hook;
          }
        }
        $p8 = implode(', ', $implementations);

        break;
    }

    $class = "\\Drupal\\scheduler\\Exception\\$exception_name";
    throw new $class(sprintf($message, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8));
  }

  /**
   * Publish scheduled entities.
   *
   * @return bool
   *   TRUE if any entity has been published, FALSE otherwise.
   *
   * @throws \Drupal\scheduler\Exception\SchedulerEntityTypeNotEnabledException
   */
  public function publish() {
    $result = FALSE;
    $action = 'publish';

    $plugins = $this->getPlugins();

    foreach ($plugins as $plugin) {
      // Select all entities of the types that are enabled for scheduled
      // publishing and where publish_on is less than or equal to the current
      // time.
      $ids = [];
      $scheduler_enabled_types = array_keys($plugin->getEnabledTypes($action));

      if (!empty($scheduler_enabled_types)) {
        $query = $this->entityTypeManager->getStorage($plugin->entityType())->getQuery()
          ->exists('publish_on')
          ->condition('publish_on', $this->time->getRequestTime(), '<=')
          ->condition($plugin->typeFieldName(), $scheduler_enabled_types, 'IN')
          ->latestRevision()
          ->sort('publish_on');
        // Disable access checks for this query.
        // @see https://www.drupal.org/node/2700209
        $query->accessCheck(FALSE);
        $ids = $query->execute();
      }

      // Allow other modules to add to the list of entities to be published.
      foreach ($this->moduleHandler->getImplementations($plugin->idListFunction()) as $module) {
        $function = $module . '_' . $plugin->idListFunction();
        // Cast each hook result as array, to protect from bad implementations.
        $ids = array_merge($ids, (array) $function($action));
      }

      // Allow other modules to alter the list of entities to be published.
      $this->moduleHandler->alter($plugin->idListFunction(), $ids, $action);

      // Finally ensure that there are no duplicates in the list of ids.
      $ids = array_unique($ids);

      // In 8.x the entity translations are all associated with one entity id
      // unlike 7.x where each translation was a separate id. This means that
      // the list of ids returned above may have some translations that need
      // processing now and others that do not.
      /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
      $entities = $this->loadEntities($ids, $plugin->entityType());
      foreach ($entities as $entity_multilingual) {

        // The API calls could return entities of types which are not enabled
        // for scheduled publishing, so do not process these. This check can be
        // done once as the setting will be the same for all translations.
        if (!$this->getThirdPartySetting($entity_multilingual, 'publish_enable', $this->setting('default_publish_enable'))) {
          $this->throwSchedulerException($entity_multilingual, 'SchedulerEntityTypeNotEnabledException', $action);
        }

        $languages = $entity_multilingual->getTranslationLanguages();
        foreach ($languages as $language) {
          // The object returned by getTranslation() is a normal $entity.
          $entity = $entity_multilingual->getTranslation($language->getId());

          // If the current translation does not have a publish on value, or it
          // is later than the date we are processing then move on to the next.
          $publish_on = $entity->publish_on->value;
          if (empty($publish_on) || $publish_on > $this->time->getRequestTime()) {
            continue;
          }

          // Check that other modules allow the action on this entity.
          if (!$this->isAllowed($entity, $action)) {
            continue;
          }

          // Trigger the PRE_PUBLISH Scheduler event so that modules can react
          // before the entity is published.
          $this->dispatchEvents($entity, 'PRE_PUBLISH');

          // Update 'changed' timestamp.
          $entity->setChangedTime($publish_on);
          $old_creation_date = $entity->getCreatedTime();
          $msg_extra = '';

          // If required, set the created date to match published date.
          if ($this->getThirdPartySetting($entity, 'publish_touch', $this->setting('default_publish_touch')) ||
            ($entity->getCreatedTime() > $publish_on && $this->getThirdPartySetting($entity, 'publish_past_date_created', $this->setting('default_publish_past_date_created')))
          ) {
            $entity->setCreatedTime($publish_on);
            $msg_extra = $this->t('The previous creation date was @old_creation_date, now updated to match the publishing date.', [
              '@old_creation_date' => $this->dateFormatter->format($old_creation_date, 'short'),
            ]);
          }

          $create_publishing_revision = $this->getThirdPartySetting($entity, 'publish_revision', $this->setting('default_publish_revision'));
          if ($create_publishing_revision) {
            $entity->setNewRevision();
            // Use a core date format to guarantee a time is included.
            $revision_log_message = rtrim($this->t('Published by Scheduler. The scheduled publishing date was @publish_on.', [
              '@publish_on' => $this->dateFormatter->format($publish_on, 'short'),
            ]) . ' ' . $msg_extra);
            $entity->setRevisionLogMessage($revision_log_message)
              ->setRevisionCreationTime($this->time->getRequestTime());
          }
          // Unset publish_on so the entity will not get rescheduled by
          // subsequent calls to $entity->save().
          $entity->publish_on->value = NULL;

          // Invoke implementations of hook_scheduler_entity_publish_action()
          // to allow other modules to do the "publishing" process instead of
          // Scheduler.
          $hooks = ['scheduler_entity_publish_action'];
          if ($entity->getEntityTypeid() == 'node') {
            // Add the original hook function for Node types only, at the start.
            array_unshift($hooks, 'scheduler_publish_action');
          }
          $processed = FALSE;
          $failed = FALSE;
          foreach ($hooks as $hook) {
            foreach ($this->moduleHandler->getImplementations($hook) as $module) {
              $function = $module . '_' . $hook;
              $return = $function($entity);
              $processed = $processed || ($return === 1);
              $failed = $failed || ($return === -1);
            }
          }

          // Log the fact that a scheduled publication is about to take place.
          $entity_type = $this->entityTypeManager->getStorage($plugin->entityType() . '_type')->load($entity->bundle());
          $view_link = $entity->toLink($this->t('View @label', ['@label' => $entity_type->label()]));
          $entity_type_link = $entity_type->toLink($this->t('@label settings', ['@label' => $entity_type->label()]), 'edit-form');
          $logger_variables = [
            '@type' => $entity_type->label(),
            '%title' => $entity->label(),
            'link' => $entity_type_link->toString() . ' ' . $view_link->toString(),
            '@hook' => 'hook_' . $hook,
          ];

          if ($failed) {
            // At least one hook function returned a failure or exception, so
            // stop processing this entity and move on to the next one.
            $this->logger->warning('Publishing failed for %title. Calls to @hook returned a failure code.', $logger_variables);
            continue;
          }
          elseif ($processed) {
            // The entity was 'published' by a module implementing the hook, so
            // we only need to log this result.
            $this->logger->notice('@type: scheduled processing of %title completed by calls to @hook.', $logger_variables);
          }
          else {
            // None of the above hook calls processed the entity and there were
            // no errors detected so set the entity to published.
            $this->logger->notice('@type: scheduled publishing of %title.', $logger_variables);
            $entity->setPublished();
          }

          // Invoke event to tell Rules that Scheduler has published the entity.
          if ($this->moduleHandler->moduleExists('scheduler_rules_integration')) {
            _scheduler_rules_integration_dispatch_cron_event($entity, 'publish');
          }

          // Trigger the PUBLISH Scheduler event so that modules can react after
          // the entity is published.
          $this->dispatchEvents($entity, 'PUBLISH');

          // Use the standard actions system to publish and save the entity.
          $action_id = $plugin->entityType() . '_publish_action';
          if ($this->moduleHandler->moduleExists('workbench_moderation_actions')) {
            // workbench_moderation_actions module uses a custom action instead.
            $action_id = 'state_change__' . $plugin->entityType() . '__published';
          }
          $this->entityTypeManager->getStorage('action')->load($action_id)->getPlugin()->execute($entity);

          $result = TRUE;
        }
      }

    }

    return $result;
  }

  /**
   * Unpublish scheduled entities.
   *
   * @return bool
   *   TRUE if any entity has been unpublished, FALSE otherwise.
   *
   * @throws \Drupal\scheduler\Exception\SchedulerEntityTypeNotEnabledException
   */
  public function unpublish() {
    $result = FALSE;
    $action = 'unpublish';

    $plugins = $this->getPlugins();

    foreach ($plugins as $plugin) {
      // Select all entities of the types for this plugin that are enabled for
      // scheduled unpublishing and where unpublish_on is less than or equal to
      // the current time.
      $ids = [];
      $scheduler_enabled_types = array_keys($plugin->getEnabledTypes($action));
      if (!empty($scheduler_enabled_types)) {
        $query = $this->entityTypeManager->getStorage($plugin->entityType())->getQuery()
          ->exists('unpublish_on')
          ->condition('unpublish_on', $this->time->getRequestTime(), '<=')
          ->condition($plugin->typeFieldName(), $scheduler_enabled_types, 'IN')
          ->latestRevision()
          ->sort('unpublish_on');
        // Disable access checks for this query.
        // @see https://www.drupal.org/node/2700209
        $query->accessCheck(FALSE);
        $ids = $query->execute();
      }

      // Allow other modules to add to the list of entities to be unpublished.
      foreach ($this->moduleHandler->getImplementations($plugin->idListFunction()) as $module) {
        $function = $module . '_' . $plugin->idListFunction();
        // Cast each hook result as array, to protect from bad implementations.
        $ids = array_merge($ids, (array) $function($action));
      }

      // Allow other modules to alter the list of entities to be unpublished.
      $this->moduleHandler->alter($plugin->idListFunction(), $ids, $action);

      // Finally ensure that there are no duplicates in the list of ids.
      $ids = array_unique($ids);

      /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
      $entities = $this->loadEntities($ids, $plugin->entityType());
      foreach ($entities as $entity_multilingual) {
        // The API calls could return entities of types which are not enabled
        // for scheduled unpublishing. Do not process these.
        if (!$this->getThirdPartySetting($entity_multilingual, 'unpublish_enable', $this->setting('default_unpublish_enable'))) {
          $this->throwSchedulerException($entity_multilingual, 'SchedulerEntityTypeNotEnabledException', $action);
        }

        $languages = $entity_multilingual->getTranslationLanguages();
        foreach ($languages as $language) {
          // The object returned by getTranslation() is a normal $entity.
          $entity = $entity_multilingual->getTranslation($language->getId());

          // If the current translation does not have an unpublish-on value, or
          // it is later than the date we are processing then move to the next.
          $unpublish_on = $entity->unpublish_on->value;
          if (empty($unpublish_on) || $unpublish_on > $this->time->getRequestTime()) {
            continue;
          }

          // Do not process the entity if it still has a publish_on time which
          // is in the past, as this implies that scheduled publishing has been
          // blocked by one of the hook functions we provide, and is still being
          // blocked now that the unpublishing time has been reached.
          $publish_on = $entity->publish_on->value;
          if (!empty($publish_on) && $publish_on <= $this->time->getRequestTime()) {
            continue;
          }

          // Check that other modules allow the action on this entity.
          if (!$this->isAllowed($entity, $action)) {
            continue;
          }

          // Trigger the PRE_UNPUBLISH Scheduler event so that modules can react
          // before the entity is unpublished.
          $this->dispatchEvents($entity, 'PRE_UNPUBLISH');

          // Update 'changed' timestamp.
          $entity->setChangedTime($unpublish_on);

          $create_unpublishing_revision = $this->getThirdPartySetting($entity, 'unpublish_revision', $this->setting('default_unpublish_revision'));
          if ($create_unpublishing_revision) {
            $entity->setNewRevision();
            // Use a core date format to guarantee a time is included.
            $revision_log_message = $this->t('Unpublished by Scheduler. The scheduled unpublishing date was @unpublish_on.', [
              '@unpublish_on' => $this->dateFormatter->format($unpublish_on, 'short'),
            ]);
            // Create the new revision, setting message and revision timestamp.
            $entity->setRevisionLogMessage($revision_log_message)
              ->setRevisionCreationTime($this->time->getRequestTime());
          }
          // Unset unpublish_on so the entity will not get rescheduled by
          // subsequent calls to $entity->save().
          $entity->unpublish_on->value = NULL;

          // Invoke implementations of hook_scheduler_entity_unpublish_action()
          // to allow other modules to do the "unpublishing" process instead of
          // Scheduler.
          $hooks = ['scheduler_entity_unpublish_action'];
          if ($entity->getEntityTypeid() == 'node') {
            // Add the original hook function for Node types only, at the start.
            array_unshift($hooks, 'scheduler_unpublish_action');
          }
          $processed = FALSE;
          $failed = FALSE;
          foreach ($hooks as $hook) {
            foreach ($this->moduleHandler->getImplementations($hook) as $module) {
              $function = $module . '_' . $hook;
              $return = $function($entity);
              $processed = $processed || ($return === 1);
              $failed = $failed || ($return === -1);
            }
          }

          // Set up the log variables.
          $entity_type = $this->entityTypeManager->getStorage($plugin->entityType() . '_type')->load($entity->bundle());
          $view_link = $entity->toLink($this->t('View @label', ['@label' => $entity_type->label()]));
          $entity_type_link = $entity_type->toLink($this->t('@label settings', ['@label' => $entity_type->label()]), 'edit-form');
          $logger_variables = [
            '@type' => $entity_type->label(),
            '%title' => $entity->label(),
            'link' => $entity_type_link->toString() . ' ' . $view_link->toString(),
            '@hook' => 'hook_' . $hook,
          ];

          if ($failed) {
            // At least one hook function returned a failure or exception, so
            // stop processing this entity and move on to the next one.
            $this->logger->warning('Unpublishing failed for %title. Calls to @hook returned a failure code.', $logger_variables);
            continue;
          }
          elseif ($processed) {
            // The entity was 'unpublished' by a module implementing the hook,
            // so we only need to log this result.
            $this->logger->notice('@type: scheduled processing of %title completed by calls to @hook.', $logger_variables);
          }
          else {
            // None of the above hook calls processed the entity and there were
            // no errors detected so set the entity to unpublished.
            $this->logger->notice('@type: scheduled unpublishing of %title.', $logger_variables);
            $entity->setUnpublished();
          }

          // Invoke event to tell Rules that Scheduler has unpublished the
          // entity.
          if ($this->moduleHandler->moduleExists('scheduler_rules_integration')) {
            _scheduler_rules_integration_dispatch_cron_event($entity, 'unpublish');
          }

          // Trigger the UNPUBLISH Scheduler event so that modules can react
          // after the entity is unpublished.
          $this->dispatchEvents($entity, 'UNPUBLISH');

          // Use the standard actions system to unpublish and save the entity.
          $action_id = $plugin->entityType() . '_unpublish_action';
          if ($this->moduleHandler->moduleExists('workbench_moderation_actions')) {
            // workbench_moderation_actions module uses a custom action instead.
            $action_id = 'state_change__node__archived';
          }
          $this->entityTypeManager->getStorage('action')->load($action_id)->getPlugin()->execute($entity);

          $result = TRUE;
        }
      }
    }

    return $result;
  }

  /**
   * Checks whether a scheduled action on an entity is allowed.
   *
   * This provides a way for other modules to prevent scheduled publishing or
   * unpublishing, by implementing hook_scheduler_entity_allow_publishing() or
   * hook_scheduler_entity_allow_unpublishing().
   *
   * For Node entities only, to maintain backwards-compatibility we have
   * hook_scheduler_allow_publishing() and hook_scheduler_allow_unpublishing().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which the action is to be performed.
   * @param string $action
   *   The action that needs to be checked. Can be 'publish' or 'unpublish'.
   *
   * @return bool
   *   TRUE if the action is allowed, FALSE if not.
   *
   * @see hook_scheduler_allow_publishing()
   * @see hook_scheduler_allow_unpublishing()
   * @see hook_scheduler_entity_allow_publishing()
   * @see hook_scheduler_entity_allow_unpublishing()
   */
  public function isAllowed(EntityInterface $entity, $action) {
    // Default to TRUE.
    $result = TRUE;
    // Check that other modules allow the action.
    $hooks = ['scheduler_entity_allow_' . $action . 'ing'];
    if ($entity->getEntityTypeid() == 'node') {
      // Add the original hook function for Node types only, to be done first.
      array_unshift($hooks, 'scheduler_allow_' . $action . 'ing');
    }
    foreach ($hooks as $hook) {
      foreach ($this->moduleHandler->getImplementations($hook) as $module) {
        $function = $module . '_' . $hook;
        $result &= $function($entity);
      }
    }

    return $result;
  }

  /**
   * Run the lightweight cron.
   *
   * The Scheduler part of the processing performed here is the same as in the
   * normal Drupal cron run. The difference is that only scheduler_cron() is
   * executed, no other modules hook_cron() functions are called.
   *
   * This function is called from the external crontab job via url
   * /scheduler/cron/{access key} or it can be run interactively from the
   * Scheduler configuration page at /admin/config/content/scheduler/cron.
   * It is also executed when running Scheduler Cron via drush.
   *
   * @param array $options
   *   Options passed from drush command or admin form.
   */
  public function runLightweightCron(array $options = []) {
    // When calling via drush the log messages can be avoided by using --nolog.
    $log = $this->setting('log') && empty($options['nolog']);
    if ($log) {
      if (array_key_exists('nolog', $options)) {
        $trigger = 'drush command';
      }
      elseif (array_key_exists('admin_form', $options)) {
        $trigger = 'admin user form';
      }
      else {
        $trigger = 'url';
      }
      $this->logger->notice('Lightweight cron run activated by @trigger.', ['@trigger' => $trigger]);
    }
    scheduler_cron();
    if (ob_get_level() > 0) {
      $handlers = ob_list_handlers();
      if (isset($handlers[0]) && $handlers[0] == 'default output handler') {
        ob_clean();
      }
    }
    if ($log) {
      $link = Link::fromTextAndUrl($this->t('settings'), Url::fromRoute('scheduler.cron_form'));
      $this->logger->notice('Lightweight cron run completed.', ['link' => $link->toString()]);
    }
  }

  /**
   * Helper method to access the settings of this module.
   *
   * @param string $key
   *   The key of the configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The value of the configuration item requested.
   */
  protected function setting($key) {
    return $this->configFactory->get('scheduler.settings')->get($key);
  }

  /**
   * Get third-party setting for and entity type, via the entity object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $setting
   *   The setting to retrieve.
   * @param mixed $default
   *   The default value for setting if none is found.
   *
   * @return mixed
   *   The value of the setting.
   */
  public function getThirdPartySetting(EntityInterface $entity, $setting, $default) {
    $typeFieldName = $this->getPlugin($entity->getEntityTypeId())->typeFieldName();
    return $entity->$typeFieldName->entity->getThirdPartySetting('scheduler', $setting, $default);
  }

  /**
   * Helper method to load latest revision of each entity.
   *
   * @param array $ids
   *   Array of entity ids.
   * @param string $type
   *   The type of entity.
   *
   * @return array
   *   Array of loaded entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function loadEntities(array $ids, $type) {
    $storage = $this->entityTypeManager->getStorage($type);
    $entities = [];

    foreach ($ids as $id) {
      $entity = $storage->load($id);

      // @todo Verify that entity type is revisionable
      if (method_exists($storage, 'revisionIds')) {
        $revision_ids = $storage->revisionIds($entity);
        $vid = end($revision_ids);
        $entities[] = $storage->loadRevision($vid);
      }
      else {
        $entities[] = $entity;
      }
    }

    return $entities;
  }

  /**
   * Get a list of all scheduler plugin definitions.
   *
   * @return array|mixed[]|null
   *   A list of definitions for the registered scheduler plugins.
   */
  public function getPluginDefinitions() {
    return $this->pluginManager->getDefinitions();
  }

  /**
   * Get instances of all scheduler plugins.
   *
   * @return array
   *   The registered plugin objects.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPlugins() {
    $cache = \Drupal::cache()->get('scheduler.plugins');
    if (!empty($cache) && !empty($cache->data)) {
      return $cache->data;
    }

    $definitions = $this->getPluginDefinitions();
    $plugins = [];
    foreach ($definitions as $definition) {
      $plugin = $this->pluginManager->createInstance($definition['id']);
      $dependency = $plugin->dependency();
      if ($dependency && !\Drupal::moduleHandler()->moduleExists($dependency)) {
        continue;
      }
      $plugins[] = $plugin;
    }

    \Drupal::cache()->set('scheduler.plugins', $plugins);
    return $plugins;
  }

  /**
   * Reset the scheduler plugins cache.
   */
  public function invalidatePluginCache() {
    $cache = \Drupal::cache()->get('scheduler.plugins');
    if (!empty($cache) && !empty($cache->data)) {
      \Drupal::cache()->set('scheduler.plugins', NULL);
    }
  }

  /**
   * Get all entity types supported.
   *
   * @return array
   *   A list of the entity types supported by the registered scheduler plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPluginEntityTypes() {
    $plugins = $this->getPlugins();
    $types = [];
    foreach ($plugins as $plugin) {
      $types[] = $plugin->entityType();
    }
    return $types;
  }

  /**
   * Gets list of entity add/edit form IDs.
   *
   * @return array
   *   List of entity add/edit form IDs for all registered scheduler plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getEntityFormIds() {
    $plugins = $this->getPlugins();
    $form_ids = [];
    foreach ($plugins as $plugin) {
      $form_ids = array_merge($form_ids, $plugin->entityFormIDs());
    }
    return $form_ids;
  }

  /**
   * Gets list of entity type add/edit form IDs.
   *
   * @return array
   *   List of entity type add/edit form IDs for registered scheduler plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getEntityTypeFormIds() {
    $plugins = $this->getPlugins();
    $form_ids = [];
    foreach ($plugins as $plugin) {
      $form_ids = array_merge($form_ids, $plugin->entityTypeFormIDs());
    }
    return $form_ids;
  }

  /**
   * Gets the supported Devel Generate form IDs.
   *
   * @return array
   *   List of form IDs used by Devel Generate.
   */
  public function getDevelGenerateFormIds() {
    $plugins = $this->getPlugins();
    $form_ids = [];
    foreach ($plugins as $plugin) {
      // Use entityType as key so we can get back from form_id to entity.
      $form_ids[$plugin->entityType()] = $plugin->develGenerateForm();
    }
    // If an entity is not supported by Devel Generate then the form id will be
    // empty, so filter out these.
    return array_filter($form_ids);
  }

  /**
   * Get a plugin for a specific entity type.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return mixed
   *   The plugin object associated with a specific entity, or NULL if none.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPlugin($entity_type) {
    $plugins = $this->getPlugins();
    foreach ($plugins as $plugin) {
      if ($plugin->entityType() == $entity_type) {
        return $plugin;
      }
    }
    return NULL;
  }

  /**
   * Updates db tables for entities that should have the Scheduler fields.
   *
   * This is called from hook_modules_installed. It can also be called manually
   * via drush command scheduler-update-entities.
   *
   * @param array $options
   *   Array of options, passed as keys.
   *
   * @return array
   *   Labels of the entity types updated.
   *
   * @todo Add logging and messenger output. Cater for 'nomsg' option.
   */
  public function updateEntities(array $options = []) {
    $entityUpdateManager = \Drupal::entityDefinitionUpdateManager();
    $updated = [];
    $list = $entityUpdateManager->getChangeList();
    foreach ($list as $entity_type_id => $definitions) {
      if ($definitions['field_storage_definitions']['publish_on'] ?? 0) {
        $entity_type = $entityUpdateManager->getEntityType($entity_type_id);
        $fields = scheduler_entity_base_field_info($entity_type);
        foreach ($fields as $field_name => $field_definition) {
          $entityUpdateManager->installFieldStorageDefinition($field_name, $entity_type_id, $entity_type_id, $field_definition);
        }
        $updated[] = (string) $entity_type->getLabel();
      }
    }
    return $updated;
  }

}
