<?php

/**
 * @file
 * Contains
 *   \Drupal\scheduler\Tests\ScedulerDateCombineFunctionalTest
 */

namespace Drupal\scheduler\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the components of the Scheduler interface which use the Date module.
 *
 * @group scheduler
 */
class SchedulerDateCombinedFunctionalTest extends SchedulerTestBase {

  /**
   * The modules to be loaded for these tests.
   */
  public static $modules = array('node', 'scheduler', 'datetime');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $config = $this->config('scheduler.settings');

    // Create a 'Basic Page' content type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => t('Basic page')));

    // Create an administrator user.
    $this->adminUser = $this->drupalCreateUser(array(
      'access content',
      'administer scheduler',
      'create page content',
      'edit own page content',
      'delete own page content',
      'view own unpublished content',
      'administer nodes',
      'schedule (un)publishing of nodes',
    ));

    // Add scheduler functionality to the page node type.
    $config->set('scheduler_publish_enable_page', 1);
    $config->set('scheduler_unpublish_enable_page', 1);
  }

  /**
   * Test the default time functionality.
   */
  public function testDefaultTime() {
    $this->drupalLogin($this->adminUser);

    // Check that the correct default time is added to the scheduled date.
    // For testing we use an offset of 6 hours 30 minutes (23400 seconds).
    $edit = array(
      'scheduler_date_format' => 'Y-m-d H:i:s',
      'scheduler_allow_date_only' => TRUE,
      'scheduler_default_time' => '6:30',
    );
    $this->drupalPost('admin/config/content/scheduler', $edit, t('Save configuration'));
    $this->assertDefaultTime();

    // Check that it is not possible to enter a date format without a time if
    // the 'date only' option is not enabled.
    $edit = array(
      'scheduler_date_format' => 'Y-m-d',
      'scheduler_allow_date_only' => FALSE,
    );
    $this->drupalPost('admin/config/content/scheduler', $edit, t('Save configuration'));
    $this->assertRaw(t('You must either include a time within the date format or enable the date-only option.'), 'It is not possible to enter a date format without a time if the "date only" option is not enabled.');
  }

  /**
   * Asserts that the default time works as expected.
   */
  protected function assertDefaultTime() {
    // We cannot easily test the exact validation messages as they contain the
    // REQUEST_TIME of the POST request, which can be one or more seconds in the
    // past. Best we can do is check the fixed part of the message as it is when
    // passed to t(). This will only work in English.
    $publish_validation_message = "The 'publish on' value does not match the expected format of";
    $unpublish_validation_message = "The 'unpublish on' value does not match the expected format of";

    // First test with the "date only" functionality disabled.
    $this->drupalPost('admin/config/content/scheduler', array('scheduler_allow_date_only' => FALSE), t('Save configuration'));

    // Test if entering a time is required.
    $edit = array(
      'title' => $this->randomName(),
      'publish_on' => date('Y-m-d', strtotime('+1 day', REQUEST_TIME)),
      'unpublish_on' => date('Y-m-d', strtotime('+2 day', REQUEST_TIME)),
    );
    $this->drupalPost('node/add/page', $edit, t('Save'));

    $this->assertRaw($publish_validation_message, 'By default it is required to enter a time when scheduling content for publication.');
    $this->assertRaw($unpublish_validation_message, 'By default it is required to enter a time when scheduling content for unpublication.');

    // Allow the user to enter only the date and repeat the test.
    $this->drupalPost('admin/config/content/scheduler', array('scheduler_allow_date_only' => TRUE), t('Save configuration'));

    $this->drupalPost('node/add/page', $edit, t('Save'));
    $this->assertNoRaw("The 'publish on' value does not match the expected format of", 'If the default time option is enabled the user can skip the time when scheduling content for publication.');
    $this->assertNoRaw("The 'unpublish on' value does not match the expected format of", 'If the default time option is enabled the user can skip the time when scheduling content for unpublication.');
    $publish_time = date('Y-m-d H:i:s', strtotime('tomorrow', REQUEST_TIME) + 23400);
    $args = array('@publish_time' => $publish_time);
    $this->assertRaw(t('This post is unpublished and will be published @publish_time.', $args), 'The user is informed that the content will be published on the requested date, on the default time.');

    // Check that the default time has been added to the scheduler form fields.
    $this->clickLink(t('Edit'));
    $this->assertFieldByName('publish_on', date('Y-m-d H:i:s', strtotime('tomorrow', REQUEST_TIME) + 23400), 'The default time offset has been added to the date field when scheduling content for publication.');
    $this->assertFieldByName('unpublish_on', date('Y-m-d H:i:s', strtotime('tomorrow +1 day', REQUEST_TIME) + 23400), 'The default time offset has been added to the date field when scheduling content for unpublication.');
  }

}
