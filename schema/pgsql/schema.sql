CREATE TABLE {$prefix}posts (
  id BIGSERIAL,
  slug VARCHAR(255) NOT NULL,
  content_type INTEGER NOT NULL,
  title VARCHAR(255) NOT NULL,
  guid VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  cached_content TEXT NOT NULL,
  user_id INTEGER NOT NULL,
  status INTEGER NOT NULL,
  pubdate TIMESTAMP NOT NULL,
  updated TIMESTAMP NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (slug)
);

CREATE TABLE {$prefix}postinfo (
  post_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  type INTEGER NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (post_id,name)
);

CREATE TABLE {$prefix}posttype (
  id SERIAL,
  name VARCHAR(255) NOT NULL,
  active SMALLINT DEFAULT 1,
  PRIMARY KEY (id)
);

CREATE TABLE {$prefix}poststatus (
  id SERIAL,
  name VARCHAR(255) NOT NULL,
  internal SMALLINT,
  PRIMARY KEY (id)
);

CREATE TABLE {$prefix}options (
  name VARCHAR(255) NOT NULL,
  type INTEGER NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (name)
);

CREATE TABLE {$prefix}users (
  id SERIAL,
  username VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (username)
);

CREATE TABLE {$prefix}userinfo (
  user_id SMALLINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  type INTEGER NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (user_id,name)
);

CREATE TABLE {$prefix}tags (
  id BIGSERIAL,
  tag_text VARCHAR(255) NOT NULL,
  tag_slug VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (tag_text)
);

CREATE TABLE {$prefix}tag2post (
  tag_id BIGINT NOT NULL,
  post_id BIGINT NOT NULL,
  PRIMARY KEY (tag_id,post_id)
);

CREATE INDEX {$prefix}tag2post_post_id_key ON {$prefix}tag2post (
  post_id
);

CREATE TABLE {$prefix}comments (
  id BIGSERIAL,
  post_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  url VARCHAR(255) NULL,
  ip BIGINT NOT NULL,
  content TEXT,
  status INTEGER NOT NULL,
  date TIMESTAMP NOT NULL,
  type INTEGER NOT NULL,
  PRIMARY KEY (id)
);

CREATE INDEX {$prefix}comments_post_id_key ON {$prefix}comments (
  post_id
);

CREATE TABLE {$prefix}commentinfo (
  comment_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  type INTEGER NOT NULL DEFAULT 0,
  value TEXT NULL,
  PRIMARY KEY (comment_id,name)
);

CREATE TABLE {$prefix}rewrite_rules (
  id SERIAL,
  name VARCHAR(255) NOT NULL,
  parse_regex VARCHAR(255) NOT NULL,
  build_str VARCHAR(255) NOT NULL,
  handler VARCHAR(255) NOT NULL,
  action VARCHAR(255) NOT NULL,
  priority INTEGER NOT NULL,
  is_active INTEGER NOT NULL DEFAULT 0,
  rule_class INTEGER NOT NULL DEFAULT 0,
  description TEXT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE {$prefix}crontab (
  id SERIAL,
  name VARCHAR(255) NOT NULL,
  callback VARCHAR(255) NOT NULL,
  last_run VARCHAR(255) NOT NULL,
  next_run VARCHAR(255) NOT NULL,
  increment VARCHAR(255) NOT NULL,
  start_time VARCHAR(255) NOT NULL,
  end_time VARCHAR(255) NOT NULL,
  result VARCHAR(255) NOT NULL,
  notify VARCHAR(255) NOT NULL,
  cron_class SMALLINT NOT NULL DEFAULT 0,
  description TEXT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE {$prefix}log (
  id BIGSERIAL,
  user_id INTEGER NULL DEFAULT NULL,
  type_id INTEGER NOT NULL,
  severity_id SMALLINT NOT NULL,
  message VARCHAR(255) NOT NULL,
  data BYTEA NULL,
  timestamp TIMESTAMP NOT NULL,
  ip BIGINT NOT NULL DEFAULT 0, 
  PRIMARY KEY (id)
);

CREATE TABLE {$prefix}log_types (
  id SERIAL,
  module VARCHAR(100) NOT NULL,
  type VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (module,type)
);

CREATE TABLE {$prefix}groups (
  id SERIAL,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (name)
);

CREATE TABLE {$prefix}permissions (
  id SERIAL,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255),
  PRIMARY KEY (id),
  UNIQUE (name)
);

CREATE TABLE {$prefix}users_groups (
  id SERIAL,
  user_id INTEGER NOT NULL,
  group_id INTEGER NOT NULL,
  PRIMARY KEY (id)
);

CREATE INDEX {$prefix}users_groups_user_group_key ON {$prefix}users_groups (
  user_id, 
  group_id
);

CREATE TABLE {$prefix}groups_permissions (
  id SERIAL,
  group_id INTEGER NOT NULL,
  permission_id INTEGER NOT NULL,
  denied SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE (group_id,permission_id)
);

CREATE TABLE {$prefix}sessions (
  token varchar(255) NOT NULL,
  subnet INTEGER NOT NULL DEFAULT 0,
  expires BIGINT NOT NULL DEFAULT 0,
  ua VARCHAR(255) NOT NULL,
  data TEXT,
  user_id INTEGER,
  PRIMARY KEY (token)
);

