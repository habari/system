CREATE TABLE {$prefix}object_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(50),
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

INSERT INTO {$prefix}object_types (name) VALUES
  ('post');

 CREATE TABLE {$prefix}terms (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  term VARCHAR(255) NOT NULL,
  term_display VARCHAR(255) NOT NULL,
  vocabulary_id INT UNSIGNED NOT NULL,
  mptt_left INT UNSIGNED NOT NULL,
  mptt_right INT UNSIGNED NOT NULL,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE {$prefix}vocabularies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  hierarchical TINYINT(1) UNSIGNED NOT NULL DEFAUlT 0,
  required TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE {$prefix}object_terms (
  object_id INT UNSIGNED NOT NULL,
  term_id INT UNSIGNED NOT NULL,
  object_type_id INT NOT NULL,
  PRIMARY KEY (object_id,term_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE {$prefix}tokens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX name (name)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE {$prefix}post_tokens (
  post_id INT UNSIGNED NOT NULL,
  token_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, token_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE {$prefix}group_token_permissions (
  group_id INT UNSIGNED NOT NULL,
  token_id INT UNSIGNED NOT NULL,
  permission_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (group_id, token_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE {$prefix}user_token_permissions (
  user_id INT UNSIGNED NOT NULL,
  token_id INT UNSIGNED NOT NULL,
  permission_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, token_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
