<?php
$queries = array(
'CREATE TABLE habari__posts ( 
	id smallint AUTO_INCREMENT NOT NULL UNIQUE,
	slug VARCHAR(255) NOT NULL PRIMARY KEY, 
	title VARCHAR(255), 
	guid VARCHAR(255) NOT NULL, 
	content LONGTEXT, 
	author VARCHAR(255) NOT NULL, 
	status VARCHAR(50) NOT NULL, 
	pubdate TIMESTAMP, 
	updated TIMESTAMP
	);',
'CREATE TABLE habari__options (
	name varchar(50) PRIMARY KEY NOT NULL UNIQUE,
	type integer DEFAULT 0,
	value text
	);',
'CREATE TABLE habari__users (
	id smallint AUTO_INCREMENT NOT NULL UNIQUE,
	username varchar(20) PRIMARY KEY NOT NULL UNIQUE,
	nickname varchar(30) NOT NULL,
	email varchar(30) NOT NULL,
	password varchar(40) NOT NULL
	);',
'CREATE TABLE habari__tags (
	slug varchar(255) PRIMARY KEY NOT NULL,
	tag varchar(30) NOT NULL,
	KEY tag (tag)
);',
'CREATE TABLE habari__comments (
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
