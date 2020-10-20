<?php


namespace Drupal\scheduler_repeat;


use Drupal\node\Entity\Node;

interface SchedulerRepeaterInterface {

  /**
   * SchedulerRepeaterInterface constructor.
   *
   * @param array $options
   *   Array of keyed options:
   *   - 'node': The NodeInterface which we operate on.
   */
  public function __construct(array $options);

  /**
   * Determines if given $node should be repeated.
   *
   * @return bool
   */
  public function shouldRepeat();

  /**
   * Implements the behaviour of calculating the next 'published_on' and
   * 'unpublished_on' timestamps.
   *
   * @param Node $node
   *
   * @return void
   */
  public function applyNextOccurance(Node &$node);

}
