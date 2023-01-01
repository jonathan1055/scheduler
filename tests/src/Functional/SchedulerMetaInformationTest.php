<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests meta information output by scheduler.
 *
 * @group scheduler
 */
class SchedulerMetaInformationTest extends SchedulerBrowserTestBase {

  /**
   * Tests meta-information on scheduled nodes.
   *
   * When nodes are scheduled for unpublication, an X-Robots-Tag HTTP header is
   * sent, alerting crawlers about when an item expires and should be removed
   * from search results.
   */
  public function testMetaInformation() {
    // Log in.
    $this->drupalLogin($this->schedulerUser);

    // Create a published node without scheduling.
    $published_node = $this->drupalCreateNode([
      'type' => $this->type,
      'status' => TRUE,
    ]);
    $this->drupalGet('node/' . $published_node->id());

    // Since we did not set an unpublish date, there should be no X-Robots-Tag
    // header on the response.
    $this->assertNull($this->getSession()->getResponseHeader('X-Robots-Tag'), 'X-Robots-Tag should not be present when no unpublish date is set.');
    // Also check that there is no meta tag.
    $this->assertSession()->responseNotContains('unavailable_after:');

    // Set a scheduler unpublish date on the node.
    $unpublish_date = strtotime('+1 day');
    $edit = [
      'unpublish_on[0][value][date]' => $this->dateFormatter->format($unpublish_date, 'custom', 'Y-m-d'),
      'unpublish_on[0][value][time]' => $this->dateFormatter->format($unpublish_date, 'custom', 'H:i:s'),
    ];
    $this->drupalGet('node/' . $published_node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // The node page should now have an X-Robots-Tag header with an
    // unavailable_after-directive and RFC850 date- and time-value.
    $this->drupalGet('node/' . $published_node->id());
    $this->assertSession()->responseHeaderEquals('X-Robots-Tag', 'unavailable_after: ' . date(DATE_RFC850, $unpublish_date));

    // Check that the required meta tag is added to the html head section.
    $this->assertSession()->responseMatches('~meta name=[\'"]robots[\'"] content=[\'"]unavailable_after: ' . date(DATE_RFC850, $unpublish_date) . '[\'"]~');

    // Check that the node is shown on the summary listing page but the two tags
    // are not present.
    $this->drupalGet('node');
     // && $this->getSession()->getStatusCode() == '200') {
    $this->assertSession()->pageTextContains($node->label());
    $this->assertNull($this->getSession()->getResponseHeader('X-Robots-Tag'), 'X-Robots-Tag should not be added when entity is not in "full" view mode.');
    $this->assertSession()->responseNotContains('unavailable_after:');
  }

}
