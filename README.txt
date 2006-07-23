// $Id$

README
--------------------------------------------------------------------------
This module allows nodes to be published and unpublished on specified dates.
If JSCalendar is enabled (part of the JSTools module 
<http://drupal.org/node/57285>), a popup Javascript
calendar is used to select the date and time for (un)publishing of nodes, 
otherwise it defaults to text input.


This module has been completely rewritten for Drupal 4.7 by:

Ted Serbinski <hello [at] tedserbinski.com>
  aka "m3avrck" on http://drupal.org


This module was originally written for Drupal 4.5.0 by:

Moshe Weitzman <weitzman [at] tejasa.com>
Gabor Hojtsy <goba [at] php.net>
Tom Dobes <tomdobes [at] purdue.edu>




INSTALLATION
--------------------------------------------------------------------------
1. Copy the scheduler.module to your modules directory
2. Enable module, database schemas should be setup automatically. If they
   aren't, use the following SQL:
   
   MySQL:
     CREATE TABLE {scheduler} (
       nid int(10) unsigned NOT NULL,
       publish_on int(11) NOT NULL default '0',
       unpublish_on int(11) NOT NULL default '0',
       PRIMARY KEY (nid)
     ) /*!40100 DEFAULT CHARACTER SET utf8 */;
  
   PostgresSQL:           
     CREATE TABLE {scheduler} (
       nid integer NOT NULL default '0',
       publish_on integer NOT NULL default '0',
       unpublish_on integer NOT NULL default '0',
       PRIMARY KEY (nid)
     );
     
3. Grant users the permission "schedule (un)publishing of nodes" so they can
   set when the nodes they create are to be (un)published.