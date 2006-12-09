<?php
$queries = array(
'CREATE TABLE ' . DB::o()->posts . ' ( 
	id smallint AUTO_INCREMENT NOT NULL UNIQUE,
	slug VARCHAR(255) NOT NULL PRIMARY KEY, 
	title VARCHAR(255), 
	guid VARCHAR(255) NOT NULL, 
	content LONGTEXT, 
	user_id smallint, 
	status smallint,
	pubdate TIMESTAMP, 
	updated TIMESTAMP
	);',
'CREATE TABLE ' . DB::o()->options . ' (
	name varchar(50) PRIMARY KEY NOT NULL UNIQUE,
	type integer DEFAULT 0,
	value text
	);',
'CREATE TABLE ' . DB::o()->users . ' (
	id smallint AUTO_INCREMENT NOT NULL UNIQUE,
	username varchar(20) PRIMARY KEY NOT NULL UNIQUE,
	email varchar(30) NOT NULL,
	password varchar(40) NOT NULL
	);',
'CREATE TABLE ' . DB::o()->tags . ' (
	slug varchar(255) NOT NULL,
	tag varchar(30) NOT NULL,
	KEY slug (slug),
	KEY tag (tag)
);',
'CREATE TABLE ' . DB::o()->comments . ' (
	id int AUTO_INCREMENT NOT NULL UNIQUE,
	post_slug varchar(255) NOT NULL,
	name varchar(255),
	email varchar(255),
	url varchar(255),
	ip varchar(255),
	content text,
	status int,
	date TIMESTAMP,
	type int
);'
);
?>
