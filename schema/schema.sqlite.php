<?php
$queries = array(
'BEGIN',
'CREATE TABLE ' . DB::o()->posts . ' ( 
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE,
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
	slug VARCHAR(255) NOT NULL,
	name varchar(50) NOT NULL,
	type smallint DEFAULT 0,
	value text
	);',
'CREATE TABLE ' . DB::o()->posttype . ' ( 
	name VARCHAR(255) NOT NULL PRIMARY KEY,
	type smallint DEFAULT 0
	);',
'INSERT INTO ' . DB::o()->posttype . ' VALUES (\'entry\', 0);',
'INSERT INTO ' . DB::o()->posttype . ' VALUES (\'page\', 1);',
'CREATE TABLE ' . DB::o()->poststatus . ' ( 
	name VARCHAR(255) NOT NULL PRIMARY KEY,
	type smallint DEFAULT 0
	);',
'INSERT INTO ' . DB::o()->poststatus . ' VALUES (\'draft\', 0);',
'INSERT INTO ' . DB::o()->poststatus . ' VALUES (\'published\', 1);',
'INSERT INTO ' . DB::o()->poststatus . ' VALUES (\'private\', 1);',
'CREATE TABLE ' . DB::o()->options . ' (
	name varchar(50) PRIMARY KEY NOT NULL UNIQUE,
	type integer DEFAULT 0,
	value text
	);',
'CREATE TABLE ' . DB::o()->users . ' (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE,
	username varchar(20) NOT NULL UNIQUE,
	email varchar(30) NOT NULL,
	password varchar(40) NOT NULL
	);',
'CREATE TABLE ' . DB::o()->userinfo . ' ( 
	user_id VARCHAR(255) NOT NULL,
	name varchar(50) NOT NULL,
	type smallint DEFAULT 0,
	value text
	);',
'CREATE TABLE ' . DB::o()->tags . ' (
	slug varchar(255) NOT NULL,
	tag varchar(30) NOT NULL
);',
'CREATE TABLE ' . DB::o()->comments . ' (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE,
	post_slug varchar(255) NOT NULL,
	name varchar(255),
	email varchar(255),
	url varchar(255),
	ip varchar(255),
	content text,
	status int,
	date TIMESTAMP,
	type int
);',
'CREATE TABLE ' . DB::o()->commentinfo . ' ( 
	comment_id VARCHAR(255) NOT NULL,
	name varchar(50) NOT NULL,
	type smallint DEFAULT 0,
	value text
	);',
'COMMIT'
);
?>
