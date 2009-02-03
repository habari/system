CREATE TEMPORARY TABLE tokens_tmp (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  token_type INT UNSIGNED NOT NULL DEFAULT 0,
  token_group VARCHAR(255) NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS name ON tokens_tmp(name);

CREATE TEMPORARY TABLE group_token_permissions_tmp (
  group_id INTEGER NOT NULL,
  token_id INTEGER NOT NULL,
  access_mask TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (group_id, token_id)
);
CREATE UNIQUE INDEX IF NOT EXISTS group_permission ON group_token_permissions_tmp(group_id,token_id);

CREATE TEMPORARY TABLE user_token_permissions_tmp (
  user_id INTEGER NOT NULL,
  token_id INTEGER NOT NULL,
  access_mask TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, token_id)
);

INSERT INTO tokens_tmp SELECT id, name, description, 0 as token_type, NULL as token_group FROM {$prefix}tokens;
INSERT INTO group_token_permissions_tmp SELECT group_id, token_id, permission_id AS access_mask FROM {$prefix}group_token_permissions;
INSERT INTO user_token_permissions_tmp SELECT user_id, token_id, permission_id AS access_mask FROM {$prefix}user_token_permissions;

DROP TABLE {$prefix}tokens;
DROP TABLE {$prefix}group_token_permissions;
DROP TABLE {$prefix}user_token_permissions;

CREATE TABLE {$prefix}tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  token_type INT UNSIGNED NOT NULL DEFAULT 0,
  token_group VARCHAR(255) NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS name ON {$prefix}tokens(name);

CREATE TABLE {$prefix}group_token_permissions (
  group_id INTEGER NOT NULL,
  token_id INTEGER NOT NULL,
  access_mask TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (group_id, token_id)
);
CREATE UNIQUE INDEX IF NOT EXISTS group_permission ON {$prefix}group_token_permissions(group_id,token_id);

CREATE TABLE {$prefix}user_token_permissions (
  user_id INTEGER NOT NULL,
  token_id INTEGER NOT NULL,
  access_mask TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, token_id)
);

INSERT INTO tokens_tmp SELECT * FROM {$prefix}tokens;
INSERT INTO group_token_permissions_tmp SELECT * FROM {$prefix}group_token_permissions;
INSERT INTO user_token_permissions_tmp SELECT * FROM {$prefix}user_token_permissions;