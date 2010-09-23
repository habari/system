ALTER TABLE {$prefix}posts ALTER COLUMN pubdate TYPE INT USING EXTRACT (EPOCH FROM pubdate);
ALTER TABLE {$prefix}posts ALTER COLUMN updated TYPE INT USING EXTRACT (EPOCH FROM updated);

ALTER TABLE {$prefix}posts ADD COLUMN modified INT;
UPDATE {$prefix}posts SET modified = updated;
ALTER TABLE {$prefix}posts ALTER COLUMN modified SET NOT NULL;

ALTER TABLE {$prefix}comments ALTER COLUMN date TYPE INT USING EXTRACT (EPOCH FROM date);
ALTER TABLE {$prefix}log ALTER COLUMN timestamp TYPE INT USING EXTRACT (EPOCH FROM timestamp);

ALTER TABLE {$prefix}crontab ALTER COLUMN last_run DROP NOT NULL;
ALTER TABLE {$prefix}crontab ALTER COLUMN end_time DROP NOT NULL;

-- I have no idea why these explicit casts should be necessary, but they seem to be.
ALTER TABLE {$prefix}crontab ALTER COLUMN last_run TYPE INT USING CASE WHEN last_run = '' THEN NULL
                                                                       ELSE last_run::int
                                                                  END;
ALTER TABLE {$prefix}crontab ALTER COLUMN next_run TYPE INT USING next_run::int;
ALTER TABLE {$prefix}crontab ALTER COLUMN increment TYPE INT USING increment::int;
ALTER TABLE {$prefix}crontab ALTER COLUMN start_time TYPE INT USING start_time::int;
ALTER TABLE {$prefix}crontab ALTER COLUMN end_time TYPE INT USING CASE WHEN end_time = '' THEN NULL
                                                                       ELSE end_time::int
                                                                  END;

UPDATE {$prefix}crontab SET last_run = NULL WHERE last_run = 0;
UPDATE {$prefix}crontab SET end_time = NULL WHERE end_time = 0;

DROP TABLE {$prefix}groups_permissions;
