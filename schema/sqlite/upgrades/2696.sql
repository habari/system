
CREATE TABLE IF NOT EXISTS {$prefix}terms (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  term VARCHAR(255) NOT NULL,
  term_display VARCHAR(255) NOT NULL,
  vocabulary_id INTEGER NOT NULL,
  mptt_left INTEGER NOT NULL,
  mptt_right INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS {$prefix}vocabularies (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  hierarchical TINYINT(1) NOT NULL DEFAUlT 0,
  required TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS {$prefix}object_terms (
  object_id INTEGER NOT NULL,
  term_id INTEGER NOT NULL,
  object_type_id INTEGER NOT NULL,
  PRIMARY KEY (object_id,term_id)
);

CREATE TABLE IF NOT EXISTS {$prefix}object_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(50)
);

INSERT INTO {$prefix}object_types (name) VALUES
  ('post');

CREATE TABLE IF NOT EXISTS {$prefix}tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS name ON {$prefix}tokens(name);

CREATE TABLE IF NOT EXISTS {$prefix}post_tokens (
  post_id INTEGER NOT NULL,
  token_id INTEGER NOT NULL,
  PRIMARY KEY (post_id, token_id)
);

CREATE TABLE IF NOT EXISTS {$prefix}group_token_permissions (
  group_id INTEGER NOT NULL,
  token_id INTEGER NOT NULL,
  permission_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (group_id, token_id)
);
CREATE UNIQUE INDEX IF NOT EXISTS group_permission ON {$prefix}group_token_permissions(group_id,token_id);

CREATE TABLE IF NOT EXISTS {$prefix}user_token_permissions (
  user_id INTEGER NOT NULL,
  token_id INTEGER NOT NULL,
  permission_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, token_id)
);
