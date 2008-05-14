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
  pubdate DATETIME NOT NULL,
  updated TIMESTAMP NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug(80))
);

CREATE TABLE  {$prefix}postinfo  (
  post_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (post_id,name)
);

CREATE TABLE  {$prefix}posttype (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  active TINYINT(1) DEFAULT 1,
  PRIMARY KEY (id)
);

CREATE TABLE  {$prefix}poststatus (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  internal TINYINT(1),
  PRIMARY KEY (id)
);

CREATE TABLE  {$prefix}options (
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (name)
);

CREATE TABLE  {$prefix}users (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY username (username)
);

CREATE TABLE  {$prefix}userinfo (
  user_id SMALLINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT,
  PRIMARY KEY (user_id,name)
);

CREATE TABLE  {$prefix}tags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tag_text VARCHAR(255) NOT NULL,
  tag_slug VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY tag_text (tag_text)
);

CREATE TABLE  {$prefix}tag2post (
  tag_id INT UNSIGNED NOT NULL,
  post_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (tag_id,post_id),
  KEY post_id (post_id)
);

CREATE TABLE  {$prefix}comments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  url VARCHAR(255) NULL,
  ip INT UNSIGNED NOT NULL,
  content TEXT,
  status SMALLINT UNSIGNED NOT NULL,
  date TIMESTAMP NOT NULL,
  type SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY post_id (post_id)
);

CREATE TABLE  {$prefix}commentinfo (
  comment_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  type SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  value TEXT NULL,
  PRIMARY KEY (comment_id,name)
);

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
  PRIMARY KEY (rule_id)
);

CREATE TABLE {$prefix}crontab (
  cron_id INT unsigned NOT NULL auto_increment,
  name VARCHAR(255) NOT NULL,
  callback VARCHAR(255) NOT NULL,
  last_run VARCHAR(255) NOT NULL,
  next_run VARCHAR(255) NOT NULL,
  increment VARCHAR(255) NOT NULL,
  start_time VARCHAR(255) NOT NULL,
  end_time VARCHAR(255) NOT NULL,
  result VARCHAR(255) NOT NULL,
  notify VARCHAR(255) NOT NULL,
  cron_class TINYINT unsigned NOT NULL default '0',
  description TEXT NULL,
  PRIMARY KEY (cron_id)
);

CREATE TABLE {$prefix}log (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NULL DEFAULT NULL,
  type_id INT NOT NULL,
  severity_id TINYINT NOT NULL,
  message VARCHAR(255) NOT NULL,
  data BLOB NULL,
  timestamp DATETIME NOT NULL,
  ip INT UNSIGNED NOT NULL, 
  PRIMARY KEY (id)
);

CREATE TABLE {$prefix}log_types (
  id INT NOT NULL AUTO_INCREMENT,
  module VARCHAR(100) NOT NULL,
  type VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY module_type (module,type)
);

CREATE TABLE {$prefix}groups (
  id INT unsigned not null auto_increment,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY name (name)
);

CREATE TABLE {$prefix}permissions (
  id INT unsigned not null auto_increment,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255),
  PRIMARY KEY (id),
  UNIQUE KEY name (name)
);

CREATE TABLE {$prefix}users_groups (
  id INT unsigned not null auto_increment,
  user_id INT unsigned not null,
  group_id INT unsigned not null,
  PRIMARY KEY (id),
  KEY user_group (user_id,group_id)
);

CREATE TABLE {$prefix}groups_permissions (
  id INT unsigned not null auto_increment,
  group_id INT unsigned not null,
  permission_id INT unsigned not null,
  denied TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY group_permission (group_id,permission_id)
);

CREATE TABLE {$prefix}sessions  (
  token varchar(255) NOT NULL,
  subnet INT NOT NULL DEFAULT 0,
  expires INT UNSIGNED NOT NULL DEFAULT 0,
  ua VARCHAR(255) NOT NULL,
  data MEDIUMTEXT,
  user_id SMALLINT UNSIGNED,
  PRIMARY KEY (token),
  UNIQUE KEY token (token)
);

