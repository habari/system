/* tested on 2.8.17 and 3.3.6 */

CREATE TABLE {$prefix}posts ( 
	id INTEGER PRIMARY KEY
,	slug VARCHAR(255) NOT NULL
,	content_type INTEGER NOT NULL
,	title VARCHAR(255) NOT NULL
,	guid VARCHAR(255) NOT NULL
,	content LONGTEXT NOT NULL
,	user_id SMALLINT NOT NULL
,	status SMALLINT NOT NULL
,	pubdate TIMESTAMP NOT NULL 
,	updated TIMESTAMP NOT NULL
, UNIQUE (slug)
);


CREATE TABLE  {$prefix}postinfo  ( 
	post_id INTEGER NOT NULL
,	name VARCHAR(50) NOT NULL
,	type SMALLINT NOT NULL DEFAULT 0
,	value TEXT
, PRIMARY KEY (post_id, name)
);


CREATE TABLE  {$prefix}posttype ( 
	name VARCHAR(255) NOT NULL 
,	type SMALLINT NOT NULL DEFAULT 0
, PRIMARY KEY (name)
);


INSERT INTO  {$prefix}posttype VALUES ("entry", 0);


INSERT INTO  {$prefix}posttype VALUES ("page", 1);


CREATE TABLE  {$prefix}poststatus ( 
	name VARCHAR(255) NOT NULL 
,	type SMALLINT NOT NULL DEFAULT 0
, PRIMARY KEY (name)
);


INSERT INTO  {$prefix}poststatus VALUES ("draft", 0);


INSERT INTO  {$prefix}poststatus VALUES ("published", 1);


INSERT INTO  {$prefix}poststatus VALUES ("private", 1);


CREATE TABLE  {$prefix}options (
	name VARCHAR(50) NOT NULL
,	type SMALLINT NOT NULL DEFAULT 0
,	value TEXT
, PRIMARY KEY (name)
);


CREATE TABLE  {$prefix}users (
	id INTEGER PRIMARY KEY
,	username VARCHAR(20) NOT NULL
,	email VARCHAR(30) NOT NULL
,	password VARCHAR(40) NOT NULL
, UNIQUE (username)
);


CREATE TABLE  {$prefix}userinfo ( 
	user_id INTEGER NOT NULL
,	name VARCHAR(50) NOT NULL
,	type SMALLINT NOT NULL DEFAULT 0
,	value TEXT
, PRIMARY KEY (user_id, name)
);


CREATE TABLE  {$prefix}tags (
  id INTEGER PRIMARY KEY
, tag_text VARCHAR(50) NOT NULL
, UNIQUE (tag_text)	
);


CREATE TABLE  {$prefix}tag2post (
  tag_id INTEGER  NOT NULL
, post_id INTEGER NOT NULL
, PRIMARY KEY (tag_id, post_id)
);


CREATE INDEX idx_tag2post_post_id on {$prefix}tag2post (post_id);


CREATE TABLE  {$prefix}themes (
  id INTEGER PRIMARY KEY
, name VARCHAR(80) NOT NULL
, version VARCHAR(10) NOT NULL
, template_engine VARCHAR(40) NOT NULL
, theme_dir VARCHAR(255) NOT NULL
, is_active SMALLINT NOT NULL DEFAULT 0
);


INSERT INTO  {$prefix}themes (
  id
, name
, version
, template_engine
, theme_dir
, is_active
) VALUES (
  NULL
, "k2"
, "1.0"
, "rawphpengine"
, "k2"
, 1
);


CREATE TABLE  {$prefix}comments (
	id INTEGER PRIMARY KEY
,	post_id INTEGER NOT NULL
,	name VARCHAR(100) NOT NULL
,	email VARCHAR(100) NOT NULL
,	url VARCHAR(255) NULL
,	ip INTEGER NOT NULL
,	content TEXT
,	status SMALLINT NOT NULL
,	date TIMESTAMP NOT NULL
,	type SMALLINT NOT NULL
);


CREATE INDEX idx_comments_post_id on {$prefix}comments (post_id);


CREATE TABLE  {$prefix}commentinfo ( 
	comment_id INTEGER NOT NULL
,	name VARCHAR(50) NOT NULL
,	type SMALLINT NOT NULL DEFAULT 0
,	value TEXT NULL
, PRIMARY KEY (comment_id, name)
);

