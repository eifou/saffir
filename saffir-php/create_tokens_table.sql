-- Run this in phpMyAdmin on your "saffir" database
-- It adds the token table needed for login sessions

CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `token_id`     varchar(50)  NOT NULL,
  `user_id`      varchar(50)  NOT NULL,
  `token_hash`   varchar(64)  NOT NULL,   -- SHA-256 of the raw token
  `created_at`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` datetime     DEFAULT NULL,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `pat_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
