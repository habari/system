CREATE TABLE {$prefix}posts ( 
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL
,	slug VARCHAR(255) NOT NULL
,	content_type SMALLINT NOT NULL
,	title VARCHAR(255) NOT NULL
,	guid VARCHAR(255) NOT NULL
,	content TEXT NOT NULL
,	cached_content LONGTEXT NOT NULL
,	user_id SMALLINT NOT NULL
,	status SMALLINT NOT NULL
,	pubdate TIMESTAMP NOT NULL 
,	updated TIMESTAMP NOT NULL
);
CREATE UNIQUE INDEX slug ON {$prefix}posts(slug);

CREATE TABLE {$prefix}postinfo  ( 
	post_id INTEGER NOT NULL
,	name VARCHAR(255) NOT NULL
,	type SMALLINT NOT NULL DEFAULT 0
,	value TEXT
, PRIMARY KEY (post_id, name)
);

CREATE TABLE  {$prefix}posttype ( 
	name VARCHAR(255) NOT NULL 
,	type SMALLINT NOT NULL DEFAULT 0
, PRIMARY KEY (name)
);

INSERT INTO  {$prefix}posttype VALUES('entry', 0);
INSERT INTO  {$prefix}posttype VALUES('page', 1);

CREATE TABLE  {$prefix}poststatus ( 
	name VARCHAR(255) NOT NULL 
,	type SMALLINT NOT NULL DEFAULT 0
, PRIMARY KEY (name)
);

INSERT INTO  {$prefix}poststatus VALUES ('draft', 0);
INSERT INTO  {$prefix}poststatus VALUES ('published', 1); 
INSERT INTO  {$prefix}poststatus VALUES ('private', 2);

CREATE TABLE  {$prefix}options (
	name VARCHAR(255) NOT NULL
,	type SMALLINT NOT NULL DEFAULT 0
,	value TEXT
, PRIMARY KEY (name)
);

CREATE TABLE  {$prefix}users (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL
,	username VARCHAR(255) NOT NULL
,	email VARCHAR(255) NOT NULL
,	password VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX username ON {$prefix}users(username);

CREATE TABLE  {$prefix}userinfo ( 
	user_id INTEGER NOT NULL
,	name VARCHAR(255) NOT NULL
,	type SMALLINT NOT NULL DEFAULT 0
,	value TEXT
, PRIMARY KEY (user_id, name)
);

CREATE TABLE  {$prefix}tags (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL 
,	tag_text VARCHAR(255) NOT NULL
, tag_slug VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX tag_text ON {$prefix}tags(tag_text);

CREATE TABLE  {$prefix}tag2post (
  tag_id INTEGER NOT NULL
, post_id INTEGER NOT NULL
, PRIMARY KEY (tag_id, post_id)
);
CREATE INDEX tag2post_post_id ON {$prefix}tag2post(post_id);

CREATE TABLE  {$prefix}themes (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL 
, name VARCHAR(255) NOT NULL
, version VARCHAR(255) NOT NULL
, template_engine VARCHAR(255) NOT NULL
, theme_dir VARCHAR(255) NOT NULL
, is_active SMALLINT NOT NULL DEFAULT 0
);

INSERT INTO  {$prefix}themes (
  name
, version
, template_engine
, theme_dir
, is_active
) VALUES (
  'k2'
, '1.0'
, 'rawphpengine'
, 'k2'
, 1
);

CREATE TABLE  {$prefix}comments (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL
,	post_id INTEGER NOT NULL
,	name VARCHAR(255) NOT NULL
,	email VARCHAR(255) NOT NULL
,	url VARCHAR(255) NULL
,	ip INTEGER NOT NULL
,	content TEXT
,	status SMALLINT NOT NULL
,	date TIMESTAMP NOT NULL
,	type SMALLINT NOT NULL
);
CREATE INDEX comments_post_id ON {$prefix}comments(post_id);

CREATE TABLE  {$prefix}commentinfo ( 
	comment_id INTEGER NOT NULL
,	name VARCHAR(255) NOT NULL
,	type SMALLINT NOT NULL DEFAULT 0
,	value TEXT NULL
, PRIMARY KEY (comment_id, name)
);

CREATE TABLE {$prefix}rewrite_rules (
	rule_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL 
, name VARCHAR(255) NOT NULL
, parse_regex VARCHAR(255) NOT NULL
, build_str VARCHAR(255) NOT NULL
, handler VARCHAR(255) NOT NULL
, action VARCHAR(255) NOT NULL
, priority SMALLINT NOT NULL
, is_active SMALLINT NOT NULL DEFAULT 0
, is_system SMALLINT NOT NULL DEFAULT 0
, description TEXT NULL
);

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('display_posts_at_page','/^page\/([\\d]+)[\/]{0,1}$/i','page/{$page}'
,'UserThemeHandler','display_posts',1,'Displays posts.  Page (of post) parameter is passed in URL');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('display_posts_by_date','/([1,2]{1}[\d]{3})\/([\d]{2})\/([\d]{2})[\/]{0,1}$/','{$year}/{$month}/{$day}'
,'UserThemeHandler','display_posts',2,'Displays posts for a specific date.');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('display_posts_by_month','/([1,2]{1}[\d]{3})\/([\d]{2})[\/]{0,1}$/','{$year}/{$month}'
,'UserThemeHandler','display_posts',3,'Displays posts for a specific month.');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('display_posts_by_year','/([1,2]{1}[\d]{3})[\/]{0,1}$/','{$year}'
,'UserThemeHandler','display_posts',4,'Displays posts for a specific year.');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('display_feed_by_type','/^feed\/(atom|rs[sd])[\/]{0,1}$/i','feed/{$feed_type}'
,'FeedHandler','display_feed',5,'Return feed per specified feed type');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('display_posts_by_tag','/^tag\/([^\/]*)[\/]{0,1}$/i','tag/{$tag}'
,'UserThemeHandler','display_posts',5,'Return posts matching specified tag');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('admin','/^admin[\/]*([^\/]*)[\/]{0,1}$/i','admin/{$page}'
,'AdminHandler','admin',6,'An admin action');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('user','/^user\/([^\/]*)[\/]{0,1}$/i','user/{$page}'
,'UserHandler','{$page}',7,'A user action or display, for instance the login screen');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('display_posts_by_slug','/([^\/]+)[\/]{0,1}$/i','{$slug}'
,'UserThemeHandler','display_posts',99,'Return posts matching specified slug');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('index_page','//',''
,'UserThemeHandler','display_posts',1000,'Homepage (index) display');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('rsd','/^rsd$/i','rsd'
,'AtomHandler','rsd',1,'RSD output');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('introspection','/^atom$/i','atom'
,'AtomHandler','introspection',1,'Atom introspection');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('collection','/^atom\/(.+)[\/]{0,1}$/i','atom/{$index}'
,'AtomHandler','collection',1,'Atom collection');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('search','/^search$/i','search'
,'UserThemeHandler','search',8,'Searches posts');

INSERT INTO {$prefix}rewrite_rules
(name, parse_regex, build_str, handler, action, priority, description)
VALUES ('comment','/^([0-9]+)\/feedback[\/]{0,1}$/i','{$id}/feedback'
,'FeedbackHandler','add_comment',8,'Adds a comment to a post');

UPDATE {$prefix}rewrite_rules SET is_active=1;
