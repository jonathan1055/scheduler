CREATE TABLE scheduler (
  nid integer NOT NULL default '0',
  timestamp_posted integer NOT NULL default '0',
  timestamp_hidden integer NOT NULL default '0',
  PRIMARY KEY (nid)
);
