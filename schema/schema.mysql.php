<?php
$queries = array(
'CREATE TABLE ' . DB::o()->posts . ' ( 
	id INT UNSIGNED NOT NULL AUTO_INCREMENT
,	slug VARCHAR(255) NOT NULL
,	content_type SMALLINT UNSIGNED NOT NULL
,	title VARCHAR(255) NOT NULL
,	guid VARCHAR(255) NOT NULL
,	content LONGTEXT NOT NULL
,	user_id SMALLINT UNSIGNED NOT NULL
,	status SMALLINT UNSIGNED NOT NULL
,	pubdate DATETIME NOT NULL 
,	updated TIMESTAMP NOT NULL
, PRIMARY KEY (id)
, UNIQUE INDEX (slug(80))
	);',
'CREATE TABLE ' . DB::o()->postinfo . ' ( 
	post_id INT UNSIGNED NOT NULL
,	name VARCHAR(50) NOT NULL
,	type SMALLINT UNSIGNED NOT NULL DEFAULT 0
,	value TEXT
, PRIMARY KEY (post_id, name)
	);',
'CREATE TABLE ' . DB::o()->posttype . ' ( 
	name VARCHAR(255) NOT NULL 
,	type SMALLINT UNSIGNED NOT NULL DEFAULT 0
, PRIMARY KEY (name)
	);',
'INSERT INTO ' . DB::o()->posttype . ' VALUES
		(\'entry\', 0),
		(\'page\', 1);',
'CREATE TABLE ' . DB::o()->poststatus . ' ( 
	name VARCHAR(255) NOT NULL 
,	type SMALLINT UNSIGNED NOT NULL DEFAULT 0
, PRIMARY KEY (name)
	);',
'INSERT INTO ' . DB::o()->poststatus . ' VALUES
		(\'draft\', 0),
		(\'published\', 1), 
		(\'private\', 1);',
'CREATE TABLE ' . DB::o()->options . ' (
	name VARCHAR(50) NOT NULL
,	type SMALLINT UNSIGNED NOT NULL DEFAULT 0
,	value TEXT
, PRIMARY KEY (name)
	);',
'CREATE TABLE ' . DB::o()->users . ' (
	id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT
,	username VARCHAR(20) NOT NULL
,	email VARCHAR(30) NOT NULL
,	password VARCHAR(40) NOT NULL
, PRIMARY KEY (id)
, UNIQUE INDEX (username)
	);',
'CREATE TABLE ' . DB::o()->userinfo . ' ( 
	user_id SMALLINT UNSIGNED NOT NULL
,	name VARCHAR(50) NOT NULL
,	type SMALLINT UNSIGNED NOT NULL DEFAULT 0
,	value TEXT
, PRIMARY KEY (user_id, name)
	);',
'CREATE TABLE ' . DB::o()->tags . ' (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT
, tag_text VARCHAR(50) NOT NULL
, PRIMARY KEY (id)
, UNIQUE INDEX (tag_text)	
);',
'CREATE TABLE ' . DB::o()->tag2post . ' (
  tag_id INT UNSIGNED NOT NULL
, post_id INT UNSIGNED NOT NULL
, PRIMARY KEY (tag_id, post_id)
, INDEX (post_id)
);',
'CREATE TABLE ' . DB::o()->themes . ' (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT
, name VARCHAR(80) NOT NULL
, version VARCHAR(10) NOT NULL
, template_engine VARCHAR(40) NOT NULL
, theme_dir VARCHAR(255) NOT NULL
, is_active TINYINT UNSIGNED NOT NULL DEFAULT 0
, PRIMARY KEY (id)
);',
'INSERT INTO ' . DB::o()->themes . ' (
  id
, name
, version
, template_engine
, theme_dir
, is_active
) VALUES (
  NULL
, \'k2\'
, \'1.0\'
, \'rawphpengine\'
, \'k2\'
, 1
);',
'CREATE TABLE ' . DB::o()->comments . ' (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT
,	post_id INT UNSIGNED NOT NULL
,	name VARCHAR(100) NOT NULL
,	email VARCHAR(100) NOT NULL
,	url VARCHAR(255) NULL
,	ip INT UNSIGNED NOT NULL
,	content TEXT
,	status TINYINT UNSIGNED NOT NULL
,	date TIMESTAMP NOT NULL
,	type SMALLINT UNSIGNED NOT NULL
, PRIMARY KEY (id)
, INDEX (post_id)
);',
'CREATE TABLE ' . DB::o()->commentinfo . ' ( 
	comment_id INT UNSIGNED NOT NULL
,	name VARCHAR(50) NOT NULL
,	type SMALLINT UNSIGNED NOT NULL DEFAULT 0
,	value TEXT NULL
, PRIMARY KEY (comment_id, name)
	);',
);
?>
