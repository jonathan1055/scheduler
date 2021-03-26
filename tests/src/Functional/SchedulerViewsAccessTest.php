<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests access to the scheduled content overview page and user tab.
 *
 * @group scheduler
 */
class SchedulerViewsAccessTest extends SchedulerBrowserTestBase {

  // @todo This is temporary, to allow commented out long lines. TO BE REMOVED.
  // phpcs:disable Drupal.Files.LineLength.TooLong

  /**
   * Additional modules required.
   *
   * @var array
   */
  protected static $modules = ['views'];

  /**
   * Create users and scheduled content for the entity type being tested.
   */
  protected function createScheduledItems($entityTypeId, $bundle) {
    // For backwards-compatibility the node permission names have to end with
    // 'nodes' and 'content'. For all other entity types we use $entityTypeId.
    if ($entityTypeId == 'node') {
      $edit_key = 'nodes';
      $view_key = 'content';
    }
    else {
      $edit_key = $view_key = $entityTypeId;
    }
    $base_permissions = [
      "view own unpublished $view_key",
    ];

    $this->webUser = $this->drupalCreateUser(array_merge($base_permissions, ["access $view_key overview"]));
    $this->webUser->set('name', 'Webisa the Web User')->save();

    $this->schedulerEditor = $this->drupalCreateUser(array_merge($base_permissions, ["schedule publishing of $edit_key"]));
    $this->schedulerEditor->set('name', 'Eddie the Scheduler Editor')->save();

    $this->schedulerViewer = $this->drupalCreateUser(array_merge($base_permissions, ["view scheduled $view_key"]));
    $this->schedulerViewer->set('name', 'Vicenza the Scheduler Viewer')->save();

    $this->addPermissionsToUser($this->adminUser, ['access user profiles']);

    // Create content scheduled for publishing and for unpublishing. The first
    // two are authored by schedulerEditor, the second two by schedulerViewer.
    $this->createEntity($entityTypeId, $bundle, [
      'title' => "$entityTypeId created by Scheduler Editor for publishing",
      'uid' => $this->schedulerEditor->id(),
      'status' => FALSE,
      'publish_on' => strtotime('+1 week'),
    ]);
    $this->createEntity($entityTypeId, $bundle, [
      'title' => "$entityTypeId created by Scheduler Editor for unpublishing",
      'uid' => $this->schedulerEditor->id(),
      'status' => TRUE,
      'unpublish_on' => strtotime('+1 week'),
    ]);
    $this->createEntity($entityTypeId, $bundle, [
      'title' => "$entityTypeId created by Scheduler Viewer for publishing",
      'uid' => $this->schedulerViewer->id(),
      'status' => FALSE,
      'publish_on' => strtotime('+1 week'),
    ]);
    $this->createEntity($entityTypeId, $bundle, [
      'title' => "$entityTypeId created by Scheduler Viewer for unpublishing",
      'uid' => $this->schedulerViewer->id(),
      'status' => TRUE,
      'unpublish_on' => strtotime('+1 week'),
    ]);
  }

  /**
   * Tests the scheduled content tab on the user page.
   *
   * @dataProvider dataStandardEntityTypes()
   */
  public function testViewScheduledContentUser($entityTypeId, $bundle) {
    $this->createScheduledItems($entityTypeId, $bundle);
    $url_end = ($entityTypeId == 'node') ? 'scheduled' : "scheduled_{$entityTypeId}";
    $assert = $this->assertSession();

    // Try to access a scheduled content user tab as an anonymous visitor. This
    // should not be allowed, and give "403 Access Denied".
    $this->drupalGet("user/{$this->schedulerEditor->id()}/$url_end");
    $assert->statusCodeEquals(403);

    // Try to access a user's own scheduled content tab when they do not have
    // any scheduler permissions. This should give "403 Access Denied".
    $this->drupalLogin($this->webUser);
    $this->drupalGet("user/{$this->webUser->id()}/$url_end");
    $assert->statusCodeEquals(403);

    // Access a user's own scheduled content tab when they have only
    // 'schedule publishing of {type}' permission. This should give "200 OK".
    $this->drupalLogin($this->schedulerEditor);
    $this->drupalGet("user/{$this->schedulerEditor->id()}/$url_end");
    $assert->statusCodeEquals(200);
    $assert->pageTextContains("$entityTypeId created by Scheduler Editor for publishing");
    $assert->pageTextContains("$entityTypeId created by Scheduler Editor for unpublishing");
    $assert->pageTextNotContains("$entityTypeId created by Scheduler Viewer for publishing");
    $assert->pageTextNotContains("$entityTypeId created by Scheduler Viewer for unpublishing");

    // Access another user's scheduled content tab. This should not be possible
    // and will give "403 Access Denied".
    $this->drupalGet("user/{$this->schedulerViewer->id()}/$url_end");
    $assert->statusCodeEquals(403);

    // Access a user's own scheduled content tab when the user has only
    // 'view scheduled content' permission.
    // @todo This is now not allowed as there wont be anything there, so do not
    // want to show the tab? maybe? Adjust this when permission rules are finalised.
    $this->drupalLogin($this->schedulerViewer);
    $this->drupalGet("user/{$this->schedulerViewer->id()}/$url_end");
    // $assert->statusCodeEquals(200);
    // $assert->pageTextNotContains("$entityTypeId created by Scheduler Editor for publishing");
    // $assert->pageTextNotContains("$entityTypeId created by Scheduler Editor for unpublishing");
    // $assert->pageTextContains("$entityTypeId created by Scheduler Viewer for publishing");
    // $assert->pageTextContains("$entityTypeId created by Scheduler Viewer for unpublishing");

    // Access another user's scheduled content tab. This should not be possible
    // and will give "403 Access Denied".
    $this->drupalGet("user/{$this->schedulerEditor->id()}/$url_end");
    $assert->statusCodeEquals(403);

    // Log in as Admin who has 'access user profiles' permission and access the
    // user who can schedule content. This is allowed.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet("user/{$this->schedulerEditor->id()}/$url_end");
    $assert->statusCodeEquals(200);
    $assert->pageTextContains("$entityTypeId created by Scheduler Editor for publishing");
    $assert->pageTextContains("$entityTypeId created by Scheduler Editor for unpublishing");
    $assert->pageTextNotContains("$entityTypeId created by Scheduler Viewer for publishing");
    $assert->pageTextNotContains("$entityTypeId created by Scheduler Viewer for unpublishing");

    // Access the scheduled tab for a user who cannot shedule any content.
    // @todo This is currently not allowed as the tab will be empty, nothing there
    // Alter this if necessary when the permissions are finalised.
    $this->drupalGet("user/{$this->schedulerViewer->id()}/$url_end");
    $assert->statusCodeEquals(403);
  }

  /**
   * Tests the scheduled content overview.
   *
   * @dataProvider dataStandardEntityTypes()
   */
  public function testViewScheduledContentOverview($entityTypeId, $bundle) {
    $this->createScheduledItems($entityTypeId, $bundle);
    $scheduled_url = ($entityTypeId == 'node') ? 'admin/content/scheduled' : "admin/content/$entityTypeId/scheduled";
    $assert = $this->assertSession();

    // Try to access the scheduled content overview as an anonymous visitor.
    $this->drupalGet($scheduled_url);
    $assert->statusCodeEquals(403);

    // Try to access the scheduled content overview as a user who has no
    // scheduler permissions. This should not be possible.
    $this->drupalLogin($this->webUser);
    $this->drupalGet($scheduled_url);
    $assert->statusCodeEquals(403);

    // Try to access the scheduled content overview as a user with only
    // 'schedule publishing of {type}' permission. This should not be possible.
    $this->drupalLogin($this->schedulerEditor);
    $this->drupalGet($scheduled_url);
    $assert->statusCodeEquals(403);

    // Access the scheduled content overview as a user who only has
    // 'view scheduled {type}' permission. This is allowed and they should see
    // the scheduled published and unpublished content by all users.
    $this->drupalLogin($this->schedulerViewer);
    $this->drupalGet($scheduled_url);
    $assert->statusCodeEquals(200);
    $assert->pageTextContains("$entityTypeId created by Scheduler Editor for publishing");
    $assert->pageTextContains("$entityTypeId created by Scheduler Editor for unpublishing");
    $assert->pageTextContains("$entityTypeId created by Scheduler Viewer for publishing");
    $assert->pageTextContains("$entityTypeId created by Scheduler Viewer for unpublishing");
  }

}
