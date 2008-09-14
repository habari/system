ALTER TABLE {$prefix}posts ALTER COLUMN pubdate TYPE VARCHAR(25);
ALTER TABLE {$prefix}posts ALTER COLUMN pubdate SET NOT NULL;
ALTER TABLE {$prefix}posts ALTER COLUMN updated TYPE VARCHAR(25);
ALTER TABLE {$prefix}posts ALTER COLUMN updated SET NOT NULL;
ALTER TABLE {$prefix}posts ADD COLUMN modified INT UNSIGNED NOT NULL;
ALTER TABLE {$prefix}comments ALTER COLUMN date TYPE VARCHAR(25);
ALTER TABLE {$prefix}comments ALTER COLUMN date SET NOT NULL;
ALTER TABLE {$prefix}log ALTER COLUMN timestamp TYPE VARCHAR(25);
ALTER TABLE {$prefix}log ALTER COLUMN timestamp SET NOT NULL;

UPDATE {$prefix}posts SET pubdate = UNIX_TIMESTAMP(pubdate);
UPDATE {$prefix}posts SET updated = UNIX_TIMESTAMP(updated);
UPDATE {$prefix}posts SET modified = updated;
UPDATE {$prefix}comments SET date = UNIX_TIMESTAMP(date);
UPDATE {$prefix}log SET timestamp = UNIX_TIMESTAMP(timestamp);

ALTER TABLE {$prefix}posts ALTER COLUMN pubdate TYPE INT;
ALTER TABLE {$prefix}posts ALTER COLUMN pubdate SET NOT NULL;
ALTER TABLE {$prefix}posts ALTER COLUMN updated TYPE INT;
ALTER TABLE {$prefix}posts ALTER COLUMN updated SET NOT NULL;
ALTER TABLE {$prefix}comments ALTER COLUMN date TYPE INT;
ALTER TABLE {$prefix}comments ALTER COLUMN date SET NOT NULL;
ALTER TABLE {$prefix}log ALTER COLUMN timestamp TYPE INT;
ALTER TABLE {$prefix}log ALTER COLUMN timestamp SET NOT NULL;

ALTER TABLE {$prefix}crontab ALTER COLUMN last_run TYPE INT;
ALTER TABLE {$prefix}crontab ALTER COLUMN next_run TYPE INT;
ALTER TABLE {$prefix}crontab ALTER COLUMN next_run SET NOT NULL;
ALTER TABLE {$prefix}crontab ALTER COLUMN increment TYPE INT;
ALTER TABLE {$prefix}crontab ALTER COLUMN increment SET NOT NULL;
ALTER TABLE {$prefix}crontab ALTER COLUMN start_time TYPE INT;
ALTER TABLE {$prefix}crontab ALTER COLUMN start_time SET NOT NULL;
ALTER TABLE {$prefix}crontab ALTER COLUMN end_time TYPE INT;

UPDATE {$prefix}crontab SET last_run=NULL WHERE last_run=0;
UPDATE {$prefix}crontab SET end_time=NULL WHERE end_time=0;

DROP TABLE {$prefix}permissions;
DROP TABLE {$prefix}groups_permissions;

CREATE SEQUENCE {$preifx}permissions_pkey_seq;
CREATE TABLE {$prefix}permissions (
  id INTEGER NOT NULL DEFAULT nextval('{$prefix}permissions_pkey_seq'),
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY (id)
);
ALTER SEQUENCE {$prefix}permissions_pkey_seq OWNED BY {$prefix}permissions.id;

INSERT INTO {$prefix}permissions (name) VALUES
  ('denied'),
  ('read'),
  ('write'),
  ('full');
