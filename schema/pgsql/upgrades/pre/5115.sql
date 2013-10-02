INSERT INTO {$prefix}commentstatus (internal, name) values (1, 'unapproved');
INSERT INTO {$prefix}commentstatus (internal, name) values (1, 'approved');
INSERT INTO {$prefix}commentstatus (internal, name) values (1, 'spam');
INSERT INTO {$prefix}commentstatus (internal, name) values (1, 'deleted');

INSERT INTO {$prefix}commenttype (name, active) values ('comment', 1);
INSERT INTO {$prefix}commenttype (name, active) values ('pingback', 1);
INSERT INTO {$prefix}commenttype (name, active) values ('trackback', 1);

UPDATE {$prefix}comments c, {$prefix}commentstatus cs SET c.status = cs.id WHERE c.status = 0 AND cs.name = 'unapproved';
UPDATE {$prefix}comments c, {$prefix}commentstatus cs SET c.status = cs.id WHERE c.status = 1 AND cs.name = 'approved';
UPDATE {$prefix}comments c, {$prefix}commentstatus cs SET c.status = cs.id WHERE c.status = 2 AND cs.name = 'spam';
UPDATE {$prefix}comments c, {$prefix}commentstatus cs SET c.status = cs.id WHERE c.status = 3 AND cs.name = 'deleted';

UPDATE {$prefix}comments c, {$prefix}commenttype ct SET c.type = ct.id WHERE c.type = 0 AND ct.name = 'comment';
UPDATE {$prefix}comments c, {$prefix}commenttype ct SET c.type = ct.id WHERE c.type = 1 AND ct.name = 'pingback';
UPDATE {$prefix}comments c, {$prefix}commenttype ct SET c.type = ct.id WHERE c.type = 2 AND ct.name = 'trackback';
