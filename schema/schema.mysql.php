<?php
$queries = array(
'CREATE TABLE ' . DB::o()->posts . ' ( 
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	slug VARCHAR(255) NOT NULL,
	content_type SMALLINT UNSIGNED NOT NULL,
	title VARCHAR(255) NOT NULL, 
	guid VARCHAR(255) NOT NULL, 
	content LONGTEXT, 
	user_id SMALLINT, 
	status SMALLINT UNSIGNED NOT NULL,
	pubdate TIMESTAMP, 
	updated TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE INDEX (slug)
	);',
'CREATE TABLE ' . DB::o()->postinfo . ' ( 
	post_id INT UNSIGNED NOT NULL,
	name VARCHAR(50) NOT NULL,
	type SMALLINT DEFAULT 0,
	value TEXT,
	PRIMARY KEY (post_id, name)
	);',
'CREATE TABLE ' . DB::o()->posttype . ' ( 
	name VARCHAR(255) NOT NULL PRIMARY KEY,
	type SMALLINT DEFAULT 0
	);',
'INSERT INTO ' . DB::o()->posttype . ' VALUES
		(\'entry\', 0),
		(\'page\', 1);',
'CREATE TABLE ' . DB::o()->poststatus . ' ( 
	name VARCHAR(255) NOT NULL PRIMARY KEY,
	type SMALLINT DEFAULT 0
	);',
'INSERT INTO ' . DB::o()->poststatus . ' VALUES
		(\'draft\', 0),
		(\'published\', 1), 
		(\'private\', 1);',
'CREATE TABLE ' . DB::o()->options . ' (
	name VARCHAR(50) PRIMARY KEY NOT NULL UNIQUE,
	type INTEGER DEFAULT 0,
	value TEXT
	);',
'CREATE TABLE ' . DB::o()->users . ' (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	username VARCHAR(20) NOT NULL,
	email VARCHAR(30) NOT NULL,
	password VARCHAR(40) NOT NULL,
	PRIMARY KEY (id), 
	UNIQUE INDEX (username)
	);',
'CREATE TABLE ' . DB::o()->userinfo . ' ( 
	user_id INT UNSIGNED NOT NULL, 
	name VARCHAR(50) NOT NULL,
	type SMALLINT DEFAULT 0,
	value TEXT,
	PRIMARY KEY (user_id, name)
	);',
'CREATE TABLE ' . DB::o()->tags . ' (
	slug VARCHAR(255) NOT NULL,
	tag VARCHAR(30) NOT NULL,
	KEY slug (slug),
	KEY tag (tag)
);',
'CREATE TABLE ' . DB::o()->comments . ' (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	post_slug VARCHAR(255) NOT NULL,
	name VARCHAR(255),
	email VARCHAR(255),
	url VARCHAR(255),
	ip VARCHAR(255),
	content TEXT,
	status INT,
	date TIMESTAMP,
	type INT,
	PRIMARY KEY (id)
);',
'CREATE TABLE ' . DB::o()->commentinfo . ' ( 
	comment_id INT UNSIGNED NOT NULL,
	name VARCHAR(50) NOT NULL,
	type SMALLINT DEFAULT 0,
	value TEXT,
	PRIMARY KEY (comment_id, name)
	);',
);
?>
