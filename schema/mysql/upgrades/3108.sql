ALTER TABLE {$prefix}group_token_permissions CHANGE permission_id access_mask TINYINT UNSIGNED NOT NULL;
ALTER TABLE {$prefix}user_token_permissions CHANGE permission_id access_mask TINYINT UNSIGNED NOT NULL;

ALTER TABLE {$prefix}tokens ADD COLUMN token_type INT UNSIGNED NOT NULL DEFAULT 0 AFTER description;
ALTER TABLE {$prefix}tokens ADD COLUMN token_group VARCHAR(255) NULL AFTER token_type;