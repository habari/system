PRAGMA auto_vacuum = 1;

CREATE TABLE {$prefix}posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  slug VARCHAR(255) NOT NULL,
  content_type SMALLINTEGER NOT NULL,
  title VARCHAR(255) NOT NULL,
  guid VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  cached_content LONGTEXT NOT NULL,
  user_id SMALLINTEGER NOT NULL,
  status SMALLINTEGER NOT NULL,
  pubdate INTEGER NOT NULL,
  updated INTEGER NOT NULL,
  modified INTEGER NOT NULL,
  input_formats VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS slug ON {$prefix}posts(slug);

CREATE TABLE {$prefix}postinfo  (
  post_id INTEGER NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINTEGER NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (post_id, name)
);

CREATE TABLE {$prefix}posttype (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  active TINYINT(1) DEFAULT 1
);

CREATE TABLE {$prefix}poststatus (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  internal TINYINT(1)
);

CREATE TABLE {$prefix}options (
  name VARCHAR(255) NOT NULL,
  type SMALLINTEGER NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (name)
);

CREATE TABLE {$prefix}users (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  username VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS username ON {$prefix}users(username);

CREATE TABLE {$prefix}userinfo (
  user_id SMALLINTEGER NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINTEGER NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (user_id, name)
);

CREATE TABLE {$prefix}comments (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  post_id INTEGER NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  url VARCHAR(255) NULL,
  ip VARCHAR(45) NOT NULL,
  content TEXT,
  status SMALLINTEGER NOT NULL,
  date INTEGER NOT NULL,
  type SMALLINTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS comments_post_id ON {$prefix}comments(post_id);

CREATE TABLE {$prefix}commentinfo (
  comment_id INTEGER NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINTEGER NOT NULL DEFAULT 0,
  value TEXT NULL,
  PRIMARY KEY (comment_id, name)
);

CREATE TABLE {$prefix}rewrite_rules (
  rule_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  parse_regex VARCHAR(255) NOT NULL,
  build_str VARCHAR(255) NOT NULL,
  handler VARCHAR(255) NOT NULL,
  action VARCHAR(255) NOT NULL,
  priority SMALLINTEGER NOT NULL,
  is_active SMALLINTEGER NOT NULL DEFAULT 0,
  rule_class SMALLINTEGER NOT NULL DEFAULT 0,
  description TEXT NULL,
  parameters TEXT NULL
);

CREATE TABLE {$prefix}crontab (
  cron_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  callback VARCHAR(255) NOT NULL,
  last_run INTEGER,
  next_run INTEGER NOT NULL,
  increment INTEGER NOT NULL,
  start_time INTEGER NOT NULL,
  end_time INTEGER,
  result VARCHAR(255) NOT NULL,
  notify VARCHAR(255) NOT NULL,
  failures INTEGER NOT NULL DEFAULT 0,
  active TINYINTEGER NOT NULL DEFAULT 1,
  cron_class TINYINTEGER NOT NULL DEFAULT 0,
  description TEXT NULL
);

CREATE TABLE {$prefix}log (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  user_id INTEGER NULL DEFAULT NULL,
  type_id INTEGER NOT NULL,
  severity_id TINYINT NOT NULL,
  message VARCHAR(255) NOT NULL,
  data BLOB NULL,
  timestamp INTEGER NOT NULL,
  ip VARCHAR(45) NOT NULL
);

CREATE TABLE {$prefix}log_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  module VARCHAR(100) NOT NULL,
  type VARCHAR(100) NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS module_type ON {$prefix}log_types(module, type);

CREATE TABLE {$prefix}groups (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS group_name ON {$prefix}groups(name);

CREATE TABLE {$prefix}users_groups (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  user_id INTEGER NOT NULL,
  group_id INTEGER NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS user_group ON {$prefix}users_groups(user_id,group_id);

CREATE TABLE {$prefix}sessions  (
  token VARCHAR(255) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  expires INTEGER NOT NULL,
  ua VARCHAR(255) NOT NULL,
  user_id INTEGER,
  data TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS token_key ON {$prefix}sessions(token);

CREATE TABLE {$prefix}terms (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  term VARCHAR(255) NOT NULL,
  term_display VARCHAR(255) NOT NULL,
  vocabulary_id INTEGER NOT NULL,
  mptt_left INTEGER NOT NULL,
  mptt_right INTEGER NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS ix_mptt ON {$prefix}terms(vocabulary_id, mptt_right, mptt_left);
CREATE UNIQUE INDEX IF NOT EXISTS ix_term ON {$prefix}terms(vocabulary_id, term);

CREATE TABLE {$prefix}terminfo (
  term_id INTEGER NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINTEGER NOT NULL DEFAULT 0,
  value TEXT NULL,
  PRIMARY KEY (term_id, name)
);

CREATE TABLE {$prefix}vocabularies (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
	features TEXT
);

CREATE TABLE {$prefix}object_terms (
  object_id INTEGER NOT NULL,
  term_id INTEGER NOT NULL,
  object_type_id INTEGER NOT NULL,
  PRIMARY KEY (object_id,term_id)
);

CREATE TABLE {$prefix}object_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(50)
);

INSERT INTO {$prefix}object_types (name) VALUES
  ('post');

CREATE TABLE {$prefix}tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  token_type INT UNSIGNED NOT NULL DEFAULT 0,
  token_group VARCHAR(255) NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS name ON {$prefix}tokens(name);

CREATE TABLE {$prefix}post_tokens (
  post_id INTEGER NOT NULL,
  token_id INTEGER NOT NULL,
  PRIMARY KEY (post_id, token_id)
);

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

CREATE TABLE {$prefix}scopes (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  criteria TEXT NOT NULL,
	description TEXT NULL,
	priority TINYINT UNSIGNED NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS id ON {$prefix}group_token_permissions(group_id,token_id);

CREATE TABLE {$prefix}blocks (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  title VARCHAR(255) NOT NULL,
  type VARCHAR(255) NOT NULL,
  data TEXT NULL
);

CREATE TABLE {$prefix}blocks_areas (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  block_id INTEGER NOT NULL,
  area VARCHAR(255) NOT NULL,
  scope_id INTEGER NOT NULL,
	display_order INTEGER NOT NULL DEFAULT 0
);