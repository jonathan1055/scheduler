CONTENTS OF THIS FILE
----------------------

  * Introduction
  * Requirements
  * Recommended modules
  * Installation
  * Configuration
  * Troubleshooting
  * Maintainers


INTRODUCTION
------------

Scheduler gives content editors the ability to schedule nodes to be published
and unpublished at specified dates and times in the future.

  * For a fuller description of the module, visit the project page:
    https://drupal.org/project/scheduler


REQUIREMENTS
------------

  * Scheduler uses the following Drupal 8 Core components:
    Actions, Datetime, Field, Node, Text, Filter, User, System, Views.
  * There are no special requirements outside core.


RECOMMENDED MODULES
-------------------

  * Rules (https://www.drupal.org/project/rules):
    Scheduler provides actions, conditions and events which can be used in Rules
    to build additional functionality.
  * Token (https://www.drupal.org/project/token):
    Scheduler provides tokens for the two scheduling dates.


INSTALLATION
------------
  * Install as you would normally install a contributed Drupal module. See:
    https://drupal.org/documentation/install/modules-themes/modules-8
    for further information.


CONFIGURATION
-------------

* Configure user permissions via Administration » People » Permissions
  URL: /admin/people/permissions#module-scheduler

   - View scheduled content list

     Users can always see their own scheduled content, via a tab on their user
     page. This permissions grants additional authority to see the full list of
     scheduled content by any author, providing they also have the core
     permission 'access content overview'.

   - Schedule content publication

     Users with this permission can enter dates and times for publishing and/or
     unpublishing, when editing nodes of types which are Scheduler-enabled.

   - Administer scheduler

     This permission allows the user to alter all Scheduler settings. It should
     therefore only be given to trusted admin roles.
     
* Configure the Scheduler options via Administration » Configuration » Content Authoring
  URL: /admin/config/content/scheduler
  
  - Basic settings for date format, date only and default time
  
  - Lightweight Cron, which gives sites admins the granularity to run Schedulers
    functions only, on more frequent crontab jobs.
    
  - Many settings are per content type, Administration » Structure » Content Types » Edit
    URL: /admin/structure/types
   

TROUBLESHOOTING
---------------

  * To submit bug reports and feature suggestions, or to track changes see:
    https://drupal.org/project/issues/scheduler


MAINTAINERS
-----------
The following are current active maintainers:
Jonathan Smith   https://www.drupal.org/u/jonathan1055
Pieter Frenssen  https://www.drupal.org/u/pfrenssen

The following are currently not active but still have maintainer authority:
Eric Schaefer    https://www.drupal.org/u/eric-schaefer
Rick Manelius    https://www.drupal.org/u/rickmanelius


This README has been completely re-written for Scheduler 8.x, based on the
template https://www.drupal.org/node/2181737 as at June 2016
