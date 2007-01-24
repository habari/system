<?php
$queries = array(
'BEGIN',
'CREATE TABLE ' . DB::o()->posts . ' ( 
	id INTEGER PRIMARY KEY,
	slug VARCHAR(255) NOT NULL UNIQUE, 
	content_type INTEGER,
	title VARCHAR(255), 
	guid VARCHAR(255) NOT NULL, 
	content LONGTEXT, 
	user_id INTEGER, 
	status smallint,
	pubdate TIMESTAMP, 
	updated TIMESTAMP
	);',
'CREATE TABLE ' . DB::o()->postinfo . ' ( 
	post_id INTEGER NOT NULL,
	name VARCHAR(50) NOT NULL,
	type SMALLINT DEFAULT 0,
	value TEXT,
	PRIMARY KEY (post_id, name)
	);',
'CREATE TABLE ' . DB::o()->posttype . ' ( 
	name VARCHAR(255) NOT NULL PRIMARY KEY,
	type SMALLINT DEFAULT 0
	);',
'INSERT INTO ' . DB::o()->posttype . ' VALUES (\'entry\', 0);',
'INSERT INTO ' . DB::o()->posttype . ' VALUES (\'page\', 1);',
'CREATE TABLE ' . DB::o()->poststatus . ' ( 
	name VARCHAR(255) NOT NULL PRIMARY KEY,
	type SMALLINT DEFAULT 0
	);',
'INSERT INTO ' . DB::o()->poststatus . ' VALUES (\'draft\', 0);',
'INSERT INTO ' . DB::o()->poststatus . ' VALUES (\'published\', 1);',
'INSERT INTO ' . DB::o()->poststatus . ' VALUES (\'private\', 1);',
'CREATE TABLE ' . DB::o()->options . ' (
	name VARCHAR(50) PRIMARY KEY NOT NULL UNIQUE,
	type INTEGER DEFAULT 0,
	value TEXT
	);',
'CREATE TABLE ' . DB::o()->users . ' (
	id INTEGER PRIMARY KEY,
	username VARCHAR(20) NOT NULL UNIQUE,
	email VARCHAR(30) NOT NULL,
	password VARCHAR(40) NOT NULL
	);',
'CREATE TABLE ' . DB::o()->userinfo . ' ( 
	user_id INTEGER NOT NULL,
	name VARCHAR(50) NOT NULL,
	type SMALLINT DEFAULT 0,
	value TEXT,
	PRIMARY KEY (user_id, name)
	);',
'CREATE TABLE ' . DB::o()->tags . ' (
	slug VARCHAR(255) NOT NULL,
	tag VARCHAR(30) NOT NULL
);',
'CREATE TABLE ' . DB::o()->comments . ' (
	id INTEGER PRIMARY KEY,
	post_slug VARCHAR(255) NOT NULL,
	name VARCHAR(255),
	email VARCHAR(255),
	url VARCHAR(255),
	ip VARCHAR(255),
	content TEXT,
	status INT,
	date TIMESTAMP,
	type INT
);',
'CREATE TABLE ' . DB::o()->commentinfo . ' ( 
	comment_id INTEGER NOT NULL,
	name VARCHAR(50) NOT NULL,
	type SMALLINT DEFAULT 0,
	value TEXT,
	PRIMARY KEY (comment_id, name)
	);',
'COMMIT'
);
?>
