<?php

namespace Drupal\scheduler\Commands;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\scheduler\SchedulerManager;
use Drush\Commands\DrushCommands;

/**
 * Drush 9 Scheduler commands for Drupal Core 8.4+.
 */
class SchedulerCommands extends DrushCommands {

  /**
   * The Scheduler manager service.
   *
   * @var \Drupal\scheduler\SchedulerManager
   */
  protected $schedulerManager;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * SchedulerCommands constructor.
   *
   * @param \Drupal\scheduler\SchedulerManager $schedulerManager
   *   Scheduler manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(SchedulerManager $schedulerManager, MessengerInterface $messenger) {
    parent::__construct();
    $this->schedulerManager = $schedulerManager;
    $this->messenger = $messenger;
  }

  /**
   * Lightweight cron to process Scheduler module tasks.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option nomsg
   *   to avoid the "cron completed" message being written to the terminal.
   * @option nolog
   *   to overide the site setting and not write 'started' and 'completed'
   *   messages to the dblog.
   *
   * @command scheduler:cron
   * @aliases sch-cron, scheduler-cron
   */
  public function cron(array $options = ['nomsg' => NULL, 'nolog' => NULL]) {
    $this->schedulerManager->runLightweightCron($options);

    $options['nomsg'] ? NULL : $this->messenger->addMessage(dt('Scheduler lightweight cron completed.'));
  }

  /**
   * Update entities - add scheduler db fields for entities covered by plugins.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option nomsg
   *   Do not write any confirmation message to the terminal.
   *
   * @command scheduler:update-entities
   * @aliases sch-upd-ent, scheduler-update-entities
   */
  public function updateEntities(array $options = ['nomsg' => NULL]) {
    $result = $this->schedulerManager->updateEntities();
    $updated = $result ? implode(',', $result) : dt('Nothing to update');
    $options['nomsg'] ? NULL : $this->messenger->addMessage(dt('Scheduler updated entities: @updated', ['@updated' => $updated]));
  }

}
