CREATE TABLE {$prefix}posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  slug VARCHAR(255) NOT NULL,
  content_type SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  guid VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  cached_content LONGTEXT NOT NULL,
  user_id SMALLINT UNSIGNED NOT NULL,
  status SMALLINT UNSIGNED NOT NULL,
  pubdate DATETIME NOT NULL,
  updated TIMESTAMP NOT NULL
);
CREATE UNIQUE INDEX slug ON {$prefix}posts(slug);

CREATE TABLE {$prefix}postinfo  (
  post_id INTEGER UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
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
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (name)
);

CREATE TABLE {$prefix}users (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  username VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX username ON {$prefix}users(username);

CREATE TABLE {$prefix}userinfo (
  user_id SMALLINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (user_id, name)
);

CREATE TABLE {$prefix}tags (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  tag_text VARCHAR(255) NOT NULL,
  tag_slug VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX tag_text ON {$prefix}tags(tag_text);

CREATE TABLE {$prefix}tag2post (
  tag_id INTEGER UNSIGNED NOT NULL,
  post_id INTEGER UNSIGNED NOT NULL,
  PRIMARY KEY (tag_id, post_id)
);
CREATE INDEX tag2post_post_id ON {$prefix}tag2post(post_id);

CREATE TABLE {$prefix}comments (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  post_id INTEGER UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  url VARCHAR(255) NULL,
  ip INTEGER SIGNED NOT NULL,
  content TEXT,
  status SMALLINT UNSIGNED NOT NULL,
  date TIMESTAMP NOT NULL,
  type SMALLINT UNSIGNED NOT NULL
);
CREATE INDEX comments_post_id ON {$prefix}comments(post_id);

CREATE TABLE {$prefix}commentinfo (
  comment_id INTEGER UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
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
  priority SMALLINT UNSIGNED NOT NULL,
  is_active SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  rule_class SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  description TEXT NULL
);

CREATE TABLE {$prefix}crontab (
  cron_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  callback VARCHAR(255) NOT NULL,
  last_run VARCHAR(255) NOT NULL,
  next_run VARCHAR(255) NOT NULL,
  increment VARCHAR(255) NOT NULL,
  start_time VARCHAR(255) NOT NULL,
  end_time VARCHAR(255) NOT NULL,
  result VARCHAR(255) NOT NULL,
  notify VARCHAR(255) NOT NULL,
  cron_class TINYINT UNSIGNED NOT NULL DEFAULT 0,
  description TEXT NULL
);

CREATE TABLE {$prefix}log (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  user_id INTEGER NULL DEFAULT NULL,
  type_id INTEGER NOT NULL,
  severity_id TINYINT NOT NULL,
  message VARCHAR(255) NOT NULL,
  data BLOB NULL DEFAULT NULL,
  timestamp DATETIME NOT NULL,
  ip INTEGER NOT NULL
);

CREATE TABLE {$prefix}log_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  module VARCHAR(100) NOT NULL,
  type VARCHAR(100) NOT NULL
);
CREATE UNIQUE INDEX module_type ON {$prefix}log_types(module, type);

CREATE TABLE {$prefix}groups (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX name ON {$prefix}groups(name);

CREATE TABLE {$prefix}permissions (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255)
);
CREATE UNIQUE INDEX name ON {$prefix}permissions(name);

CREATE TABLE {$prefix}users_groups (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  user_id INTEGER unsigned not null,
  group_id INTEGER unsigned not null
);
CREATE INDEX user_group ON {$prefix}users_groups(user_id,group_id);

CREATE TABLE {$prefix}groups_permissions (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  group_id INTEGER unsigned not null,
  permission_id INTEGER unsigned not null,
  denied TINYINT UNSIGNED NOT NULL DEFAULT 0
);
CREATE UNIQUE INDEX group_permission ON {$prefix}groups_permissions(group_id,permission_id);

CREATE TABLE {$prefix}sessions  (
  token VARCHAR(255) NOT NULL,
  subnet INTEGER not null,
  expires INTEGER unsigned not null,
  ua VARCHAR(255) NOT NULL,
  user_id INTEGER,
  data TEXT
);
CREATE UNIQUE INDEX token_key ON {$prefix}sessions(token);
