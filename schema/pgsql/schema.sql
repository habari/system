CREATE SEQUENCE {$prefix}posts_pkey_seq;
CREATE TABLE {$prefix}posts (
  id BIGINT NOT NULL DEFAULT nextval('{$prefix}posts_pkey_seq'),
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
ALTER SEQUENCE {$prefix}posts_pkey_seq OWNED BY {$prefix}posts.id;

CREATE TABLE {$prefix}postinfo (
  post_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  type INTEGER NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (post_id,name)
);

CREATE SEQUENCE {$prefix}posttype_pkey_seq;
CREATE TABLE {$prefix}posttype (
  id INTEGER NOT NULL DEFAULT nextval('{$prefix}posttype_pkey_seq'),
  name VARCHAR(255) NOT NULL,
  active SMALLINT DEFAULT 1,
  PRIMARY KEY (id)
);
ALTER SEQUENCE {$prefix}posttype_pkey_seq OWNED BY {$prefix}posttype.id;

CREATE SEQUENCE {$prefix}poststatus_pkey_seq;
CREATE TABLE {$prefix}poststatus (
  id INTEGER NOT NULL DEFAULT nextval('{$prefix}poststatus_pkey_seq'),
  name VARCHAR(255) NOT NULL,
  internal SMALLINT,
  PRIMARY KEY (id)
);
ALTER SEQUENCE {$prefix}poststatus_pkey_seq OWNED BY {$prefix}poststatus.id;

CREATE TABLE {$prefix}options (
  name VARCHAR(255) NOT NULL,
  type INTEGER NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (name)
);

CREATE SEQUENCE {$prefix}users_pkey_seq;
CREATE TABLE {$prefix}users (
  id INTEGER NOT NULL DEFAULT nextval('{$prefix}users_pkey_seq'),
  username VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (username)
);
ALTER SEQUENCE {$prefix}users_pkey_seq OWNED BY {$prefix}users.id;

CREATE TABLE {$prefix}userinfo (
  user_id SMALLINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  type INTEGER NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (user_id,name)
);

CREATE SEQUENCE {$prefix}tags_pkey_seq;
CREATE TABLE {$prefix}tags (
  id BIGINT NOT NULL DEFAULT nextval('{$prefix}tags_pkey_seq'),
  tag_text VARCHAR(255) NOT NULL,
  tag_slug VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (tag_text)
);
ALTER SEQUENCE {$prefix}tags_pkey_seq OWNED BY {$prefix}tags.id;

CREATE TABLE {$prefix}tag2post (
  tag_id BIGINT NOT NULL,
  post_id BIGINT NOT NULL,
  PRIMARY KEY (tag_id,post_id)
);

CREATE INDEX {$prefix}tag2post_post_id_key ON {$prefix}tag2post (
  post_id
);

CREATE SEQUENCE {$prefix}comments_pkey_seq;
CREATE TABLE {$prefix}comments (
  id BIGINT NOT NULL DEFAULT nextval('{$prefix}comments_pkey_seq'),
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
ALTER SEQUENCE {$prefix}comments_pkey_seq OWNED BY {$prefix}comments.id;

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

CREATE SEQUENCE {$prefix}rewrite_rules_pkey_seq;
CREATE TABLE {$prefix}rewrite_rules (
  rule_id INTEGER NOT NULL DEFAULT nextval('{$prefix}rewrite_rules_pkey_seq'),
  name VARCHAR(255) NOT NULL,
  parse_regex VARCHAR(255) NOT NULL,
  build_str VARCHAR(255) NOT NULL,
  handler VARCHAR(255) NOT NULL,
  action VARCHAR(255) NOT NULL,
  priority INTEGER NOT NULL,
  is_active INTEGER NOT NULL DEFAULT 0,
  rule_class INTEGER NOT NULL DEFAULT 0,
  description TEXT NULL,
  PRIMARY KEY (rule_id)
);
ALTER SEQUENCE {$prefix}rewrite_rules_pkey_seq OWNED BY {$prefix}rewrite_rules.rule_id;

CREATE SEQUENCE {$prefix}crontab_pkey_seq;
CREATE TABLE {$prefix}crontab (
  cron_id INTEGER NOT NULL DEFAULT nextval('{$prefix}crontab_pkey_seq'),
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
  PRIMARY KEY (cron_id)
);
ALTER SEQUENCE {$prefix}crontab_pkey_seq OWNED BY {$prefix}crontab.cron_id;

CREATE SEQUENCE {$prefix}log_pkey_seq;
CREATE TABLE {$prefix}log (
  id BIGINT NOT NULL DEFAULT nextval('{$prefix}log_pkey_seq'),
  user_id INTEGER NULL DEFAULT NULL,
  type_id INTEGER NOT NULL,
  severity_id SMALLINT NOT NULL,
  message VARCHAR(255) NOT NULL,
  data BYTEA NULL,
  timestamp TIMESTAMP NOT NULL,
  ip BIGINT NOT NULL DEFAULT 0, 
  PRIMARY KEY (id)
);
ALTER SEQUENCE {$prefix}log_pkey_seq OWNED BY {$prefix}log.id;

CREATE SEQUENCE {$prefix}log_types_pkey_seq;
CREATE TABLE {$prefix}log_types (
  id INTEGER NOT NULL DEFAULT nextval('{$prefix}log_types_pkey_seq'),
  module VARCHAR(100) NOT NULL,
  type VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (module,type)
);
ALTER SEQUENCE {$prefix}log_types_pkey_seq OWNED BY {$prefix}log_types.id;

CREATE SEQUENCE {$prefix}groups_pkey_seq;
CREATE TABLE {$prefix}groups (
  id INTEGER NOT NULL DEFAULT nextval('{$prefix}groups_pkey_seq'),
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (name)
);
ALTER SEQUENCE {$prefix}groups_pkey_seq OWNED BY {$prefix}groups.id;

CREATE SEQUENCE {$prefix}permissions_pkey_seq;
CREATE TABLE {$prefix}permissions (
  id INTEGER NOT NULL DEFAULT nextval('{$prefix}permissions_pkey_seq'),
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255),
  PRIMARY KEY (id),
  UNIQUE (name)
);
ALTER SEQUENCE {$prefix}permissions_pkey_seq OWNED BY {$prefix}permissions.id;

CREATE SEQUENCE {$prefix}users_groups_pkey_seq;
CREATE TABLE {$prefix}users_groups (
  id INTEGER NOT NULL DEFAULT nextval('{$prefix}users_groups_pkey_seq'),
  user_id INTEGER NOT NULL,
  group_id INTEGER NOT NULL,
  PRIMARY KEY (id)
);
ALTER SEQUENCE {$prefix}users_groups_pkey_seq OWNED BY {$prefix}users_groups.id;

CREATE INDEX {$prefix}users_groups_user_group_key ON {$prefix}users_groups (
  user_id, 
  group_id
);

CREATE SEQUENCE {$prefix}groups_permissions_pkey_seq;
CREATE TABLE {$prefix}groups_permissions (
  id INTEGER NOT NULL DEFAULT nextval('{$prefix}groups_permissions_pkey_seq'),
  group_id INTEGER NOT NULL,
  permission_id INTEGER NOT NULL,
  denied SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE (group_id,permission_id)
);
ALTER SEQUENCE {$prefix}groups_permissions_pkey_seq OWNED BY {$prefix}groups_permissions.id;

CREATE TABLE {$prefix}sessions (
  token varchar(255) NOT NULL,
  subnet INTEGER NOT NULL DEFAULT 0,
  expires BIGINT NOT NULL DEFAULT 0,
  ua VARCHAR(255) NOT NULL,
  data TEXT,
  user_id INTEGER,
  PRIMARY KEY (token)
);

