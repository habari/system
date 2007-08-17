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
	name VARCHAR(255) NOT NULL
);

INSERT INTO {$prefix}posttype (name) VALUES('entry');
INSERT INTO {$prefix}posttype (name) VALUES('page');

CREATE TABLE {$prefix}poststatus (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	name VARCHAR(255) NOT NULL
);

INSERT INTO {$prefix}poststatus (name) VALUES ('deleted');
INSERT INTO {$prefix}poststatus (name) VALUES ('draft');
INSERT INTO {$prefix}poststatus (name) VALUES ('published');
INSERT INTO {$prefix}poststatus (name) VALUES ('private');

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

CREATE TABLE {$prefix}themes (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	name VARCHAR(255) NOT NULL,
	version VARCHAR(255) NOT NULL,
	template_engine VARCHAR(255) NOT NULL,
	theme_dir VARCHAR(255) NOT NULL,
	is_active TINYINT UNSIGNED NOT NULL DEFAULT 0
);

INSERT INTO {$prefix}themes (
	name,
	version,
	template_engine,
	theme_dir,
	is_active
) VALUES (
	'k2',
	'1.0',
	'rawphpengine',
	'k2',
	1
);

CREATE TABLE {$prefix}comments (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	post_id INTEGER UNSIGNED NOT NULL,
	name VARCHAR(255) NOT NULL,
	email VARCHAR(255) NOT NULL,
	url VARCHAR(255) NULL,
	ip INTEGER UNSIGNED NOT NULL,
	content TEXT,
	status TINYINT UNSIGNED NOT NULL,
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
	is_active TINYINT UNSIGNED NOT NULL DEFAULT 0,
	rule_class TINYINT UNSIGNED NOT NULL DEFAULT 0,
	description TEXT NULL
);

INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('display_entries_by_date', '%^(?P<year>[1,2]{1}[\d]{3})(?:/(?P<month>[\d]{2}))?(?:/(?P<day>[\d]{2}))?(?:/page/(?P<page>\d+))?/?$%i', '{$year}/({$month}/)({$day}/)(page/{$page}/)', 'UserThemeHandler', 'display_date', 2, 1, 1, 'Displays posts for a specific date.');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('display_feed_by_type', '/^feed\/(?P<feed_type>atom|rs[sd])[\/]?$/i', 'feed/{$feed_type}', 'FeedHandler', 'display_feed', 5, 1, 0, 'Return feed per specified feed type');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('admin', '/^admin[\/]*(?P<page>[^\/]*)[\/]?$/i', 'admin/({$page})', 'AdminHandler', 'admin', 6, 1, 0, 'An admin action');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('userprofile', '/^admin\/(?P<page>user)\/(?P<user>[^\/]+)\/?$/', 'admin/{$page}/{$user}', 'AdminHandler', 'admin', 4, 1, 1, 'The profile page for a specific user');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('user', '/^user\/(?P<page>[^\/]*)[\/]?$/i', 'user/{$page}', 'UserHandler', '{$page}', 7, 1, 0, 'A user action or display, for instance the login screen');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('display_entry', '%^(?P<slug>[^/]+)(?:/page/(?P<page>\d+))?/?$%i', '{$slug}(/page/{$page}/)', 'UserThemeHandler', 'display_post', 100, 1, 1, 'Return entry matching specified slug');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('display_page', '%^(?P<slug>[^/]+)(?:/page/(?P<page>\d+))?/?$%i', '{$slug}(/page/{$page}/)', 'UserThemeHandler', 'display_post', 100, 1, 1, 'Return page matching specified slug');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('index_page', '/^(?:|page\/(?P<page>\d+)[\/]?)$/', '(page/{$page})', 'UserThemeHandler', 'display_home', 1000, 1, 1, 'Homepage (index) display');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('rsd', '/^rsd$/i', 'rsd', 'AtomHandler', 'rsd', 1, 1, 0, 'RSD output');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('introspection', '/^atom$/i', 'atom', 'AtomHandler', 'introspection', 1, 1, 0, 'Atom introspection');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('collection', '/^atom\/(?P<index>.+)[\/]?$/i', 'atom/{$index}', 'AtomHandler', 'collection', 8, 1, 0, 'Atom collection');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('search', '/^search(?:\/(?P<criteria>[^\/]+))?(?:\/page\/(?P<page>\d+))?$/i', 'search(/{$criteria})(/page/{$page}/)', 'UserThemeHandler', 'search', 8, 1, 1, 'Searches posts');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('comment', '/^(?P<id>[0-9]+)\/feedback[\/]?$/i', '{$id}/feedback', 'FeedbackHandler', 'add_comment', 8, 1, 0, 'Adds a comment to a post');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('ajax', '/^ajax\/(?P<context>[^\/]+)[\/]?$/i', 'ajax/{$context}', 'AjaxHandler', 'ajax', 8, 1, 0, 'Ajax handling');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('auth_ajax', '/^auth_ajax\/(?P<context>[^\/]+)[\/]?$/i', 'auth_ajax/{$context}', 'AjaxHandler', 'auth_ajax', 8, 1, 0, 'Authenticated ajax handling');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('entry', '/(?P<slug>[^\/]+)\/atom$/i', '{$slug}/atom', 'AtomHandler', 'entry', 8, 1, 0, 'Atom Publishing Protocol');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('entry_comments', '/^(?P<slug>[^\/]+)\/atom\/comments$/i', '{$slug}/atom/comments', 'AtomHandler', 'entry_comments', 8, 1, 0, 'Entry comments');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('comments', '/^atom\/comments$/i', 'atom/comments', 'AtomHandler', 'comments', 7, 1, 0, 'Entries comments');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('tag_collection', '/^tag\/(?P<tag>[^\/]+)\/atom$/i', 'tag/{$tag}/atom', 'AtomHandler', 'tag_collection', 8, 1, 0, 'Atom Tag Collection');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('display_entries_by_tag', '%^tag/(?P<tag>[^/]*)(?:/page/(?P<page>\d+))?[/]?$%i', 'tag/{$tag}(/page/{$page}/)', 'UserThemeHandler', 'display_tag', 5, 1, 1, 'Return posts matching specified tag');
INSERT INTO {$prefix}rewrite_rules (name, parse_regex, build_str, handler, action, priority, is_active, rule_class, description) VALUES ('xmlrpc', '%^xmlrpc/?%i', 'xmlrpc', 'XMLRPCServer', 'xmlrpc_call', 8, 1, 0, 'Handle incoming XMLRPC requests.');

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
	timestamp DATETIME NOT NULL
);

CREATE TABLE {$prefix}log_types (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	module VARCHAR(100) NOT NULL,
	type VARCHAR(100) NOT NULL
);
CREATE UNIQUE INDEX module_type ON {$prefix}log_types(module, type);

INSERT INTO {$prefix}log_types (module, type) VALUES ('habari', 'default');
INSERT INTO {$prefix}log_types (module, type) VALUES ('habari', 'authentication');
INSERT INTO {$prefix}log_types (module, type) VALUES ('habari', 'content');
INSERT INTO {$prefix}log_types (module, type) VALUES ('habari', 'comment');

CREATE TABLE {$prefix}groups (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX name ON {$prefix}groups(name);

CREATE TABLE {$prefix}permissions (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL
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
