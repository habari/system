CREATE TABLE {$prefix}posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(255) NOT NULL,
  content_type SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  guid VARCHAR(255) NOT NULL,
  content LONGTEXT NOT NULL,
  cached_content LONGTEXT NOT NULL,
  user_id SMALLINT UNSIGNED NOT NULL,
  status SMALLINT UNSIGNED NOT NULL,
  pubdate INT UNSIGNED NOT NULL,
  updated INT UNSIGNED NOT NULL,
  modified INT UNSIGNED NOT NULL,
  input_formats VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug(80))
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}postinfo  (
  post_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (post_id,name)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}posttype (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  active TINYINT(1) DEFAULT 1,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}poststatus (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  internal TINYINT(1),
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}options (
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (name)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}users (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY username (username)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}userinfo (
  user_id SMALLINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (user_id,name)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}comments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  url VARCHAR(255) NULL,
  ip VARCHAR(45) NOT NULL,
  content TEXT,
  status SMALLINT UNSIGNED NOT NULL,
  date INT UNSIGNED NOT NULL,
  type SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY post_id (post_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}commenttype (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  active TINYINT(1) DEFAULT 1,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}commentstatus (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  internal TINYINT(1),
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}commentinfo (
  comment_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT NULL,
  PRIMARY KEY (comment_id,name)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}rewrite_rules (
  rule_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  parse_regex VARCHAR(255) NOT NULL,
  build_str VARCHAR(255) NOT NULL,
  handler VARCHAR(255) NOT NULL,
  action VARCHAR(255) NOT NULL,
  priority SMALLINT UNSIGNED NOT NULL,
  is_active SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  rule_class SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  description TEXT NULL,
  parameters TEXT NULL,
  PRIMARY KEY (rule_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}crontab (
  cron_id INT unsigned NOT NULL auto_increment,
  name VARCHAR(255) NOT NULL,
  callback VARCHAR(255) NOT NULL,
  last_run INT UNSIGNED,
  next_run INT UNSIGNED NOT NULL,
  increment INT UNSIGNED NOT NULL,
  start_time INT UNSIGNED NOT NULL,
  end_time INT UNSIGNED,
  result VARCHAR(255) NOT NULL,
  notify VARCHAR(255) NOT NULL,
  failures INT UNSIGNED NOT NULL DEFAULT 0,
  active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  cron_class TINYINT unsigned NOT NULL DEFAULT 0,
  description TEXT NULL,
  PRIMARY KEY (cron_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}log (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NULL DEFAULT NULL,
  type_id INT NOT NULL,
  severity_id TINYINT NOT NULL,
  message VARCHAR(255) NOT NULL,
  data BLOB NULL,
  timestamp INT UNSIGNED NOT NULL,
  ip VARCHAR(45) NOT NULL,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}log_types (
  id INT NOT NULL AUTO_INCREMENT,
  module VARCHAR(100) NOT NULL,
  type VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY module_type (module,type)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}groups (
  id INT unsigned not null auto_increment,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY name (name)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}users_groups (
  id INT unsigned not null auto_increment,
  user_id INT unsigned not null,
  group_id INT unsigned not null,
  PRIMARY KEY (id),
  UNIQUE KEY user_group (user_id,group_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}sessions  (
  token varchar(255) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  expires INT UNSIGNED NOT NULL DEFAULT 0,
  ua VARCHAR(255) NOT NULL,
  data MEDIUMTEXT,
  user_id SMALLINT UNSIGNED,
  PRIMARY KEY (token)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}terms (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  term VARCHAR(255) NOT NULL,
  term_display VARCHAR(255) NOT NULL,
  vocabulary_id INT UNSIGNED NOT NULL,
  mptt_left INT SIGNED NOT NULL,
  mptt_right INT SIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ix_mptt (vocabulary_id, mptt_right, mptt_left),
  UNIQUE KEY ix_term (vocabulary_id, term)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE  {$prefix}terminfo (
  term_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT NULL,
  PRIMARY KEY (term_id,name)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}vocabularies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  features TEXT,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}object_terms (
  object_id INT UNSIGNED NOT NULL,
  term_id INT UNSIGNED NOT NULL,
  object_type_id INT NOT NULL,
  PRIMARY KEY (object_id,term_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}object_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(50),
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

INSERT INTO {$prefix}object_types (name) VALUES
  ('post');

CREATE TABLE {$prefix}tokens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  token_type INT UNSIGNED NOT NULL DEFAULT 0,
  token_group VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX name (name)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}post_tokens (
  post_id INT UNSIGNED NOT NULL,
  token_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, token_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}group_token_permissions (
  group_id INT UNSIGNED NOT NULL,
  token_id INT UNSIGNED NOT NULL,
  access_mask TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (group_id, token_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}user_token_permissions (
  user_id INT UNSIGNED NOT NULL,
  token_id INT UNSIGNED NOT NULL,
  access_mask TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, token_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}scopes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  criteria TEXT NOT NULL,
	description TEXT NULL,
	priority TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}blocks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  type VARCHAR(255) NOT NULL,
  data TEXT NULL,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE {$prefix}blocks_areas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  block_id INT UNSIGNED NOT NULL,
  area VARCHAR(255) NOT NULL,
  scope_id INT UNSIGNED NOT NULL,
	display_order INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;