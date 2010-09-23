ALTER TABLE {$prefix}posts ADD modified INT UNSIGNED NOT NULL DEFAULT (0);
UPDATE {$prefix}posts SET pubdate = strftime( '%s', pubdate );
UPDATE {$prefix}posts SET updated = strftime( '%s', updated );
UPDATE {$prefix}posts SET modified = updated;

UPDATE {$prefix}comments SET date = strftime( '%s', date );
UPDATE {$prefix}crontab SET last_run = NULL WHERE last_run = 0;
UPDATE {$prefix}crontab SET end_time = NULL WHERE end_time = 0;
ALTER TABLE {$prefix}rewrite_rules ADD parameters TEXT NULL;
DROP TABLE {$prefix}groups_permissions;
