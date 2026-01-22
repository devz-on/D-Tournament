-- Database schema for D-Tournament
-- Create your database first (example):
-- CREATE DATABASE tournament CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE tournament;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT NOT NULL,
  `data1` VARCHAR(255) NOT NULL DEFAULT '',
  `data2` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `profile` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `picture` VARCHAR(255) NOT NULL,
  `status` ENUM('unused', 'used') NOT NULL DEFAULT 'unused',
  PRIMARY KEY (`id`),
  UNIQUE KEY `profile_picture_unique` (`picture`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `teams` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `team_name` VARCHAR(100) NOT NULL,
  `igl_name` VARCHAR(100) NOT NULL,
  `igl_email` VARCHAR(150) NOT NULL,
  `igl_wa` VARCHAR(20) NOT NULL,
  `f_player` VARCHAR(100) NOT NULL,
  `s_player` VARCHAR(100) NOT NULL,
  `t_player` VARCHAR(100) NOT NULL,
  `frth_player` VARCHAR(100) NOT NULL,
  `profile` VARCHAR(255) NOT NULL,
  `team_label` VARCHAR(20) NOT NULL,
  `email_verify` ENUM('verified', 'unverified') NOT NULL DEFAULT 'unverified',
  `payment` ENUM('success', 'pending') NOT NULL DEFAULT 'pending',
  `screenshot` VARCHAR(255) DEFAULT NULL,
  `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_label_unique` (`team_label`),
  UNIQUE KEY `team_name_unique` (`team_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `winners` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `team_id` INT NOT NULL,
  `position` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `winner_team_unique` (`team_id`),
  CONSTRAINT `winners_team_fk` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notification` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `team_name` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('read', 'unread') NOT NULL DEFAULT 'unread',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contact` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `query` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `team_label` VARCHAR(20) NOT NULL,
  `razorpay_order_id` VARCHAR(100) NOT NULL,
  `razorpay_payment_id` VARCHAR(100) DEFAULT NULL,
  `razorpay_signature` VARCHAR(255) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'created',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payments_team_label_idx` (`team_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tournaments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `map_name` VARCHAR(150) NOT NULL,
  `entry_fee` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `prize_pool` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `start_time` DATETIME NOT NULL,
  `status` ENUM('draft', 'published', 'completed', 'archived') NOT NULL DEFAULT 'draft',
  `room_id` VARCHAR(100) DEFAULT NULL,
  `room_password` VARCHAR(100) DEFAULT NULL,
  `room_open_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tournament_entries` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tournament_id` INT NOT NULL,
  `team_id` INT NOT NULL,
  `entry_fee` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `status` ENUM('joined', 'cancelled', 'completed') NOT NULL DEFAULT 'joined',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tournament_team_unique` (`tournament_id`, `team_id`),
  KEY `tournament_entries_team_idx` (`team_id`),
  CONSTRAINT `tournament_entries_tournament_fk` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tournament_entries_team_fk` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `team_wallets` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `team_id` INT NOT NULL,
  `balance` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `winnings_balance` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_wallet_unique` (`team_id`),
  CONSTRAINT `team_wallets_team_fk` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `team_id` INT NOT NULL,
  `type` ENUM('credit', 'debit', 'winnings') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `source` VARCHAR(100) NOT NULL DEFAULT 'manual',
  `note` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `wallet_transactions_team_idx` (`team_id`),
  CONSTRAINT `wallet_transactions_team_fk` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default settings:
-- id=1: site view counter
-- id=2: tournament status (data1=start|ended), data2=token
-- id=3: admin username/password
-- id=4: WhatsApp group link
INSERT INTO `settings` (`id`, `data1`, `data2`) VALUES
  (1, '0', ''),
  (2, 'ended', ''),
  (3, 'admin', 'admin12345'),
  (4, 'https://chat.whatsapp.com/your-invite', '')
ON DUPLICATE KEY UPDATE
  `data1` = VALUES(`data1`),
  `data2` = VALUES(`data2`);

INSERT INTO `profile` (`picture`, `status`) VALUES
  ('1.jpg', 'unused'),
  ('10.jpg', 'unused'),
  ('11.jpg', 'unused'),
  ('12.jpg', 'unused'),
  ('13.jpg', 'unused'),
  ('14.jpg', 'unused'),
  ('15.jpg', 'unused'),
  ('16.jpg', 'unused'),
  ('17.jpg', 'unused'),
  ('18.jpg', 'unused'),
  ('19.jpg', 'unused'),
  ('2.png', 'unused'),
  ('20.jpg', 'unused'),
  ('21.jpg', 'unused'),
  ('22.jpg', 'unused'),
  ('23.jpg', 'unused'),
  ('24.jpg', 'unused'),
  ('25.jpg', 'unused'),
  ('26.jpg', 'unused'),
  ('27.jpg', 'unused'),
  ('28.jpg', 'unused'),
  ('29.jpg', 'unused'),
  ('3.jpg', 'unused'),
  ('30.jpg', 'unused'),
  ('31.jpg', 'unused'),
  ('32.jpg', 'unused'),
  ('33.jpg', 'unused'),
  ('34.jpg', 'unused'),
  ('35.jpg', 'unused'),
  ('36.jpg', 'unused'),
  ('37.png', 'unused'),
  ('38.png', 'unused'),
  ('39.png', 'unused'),
  ('4.png', 'unused'),
  ('40.png', 'unused'),
  ('41.png', 'unused'),
  ('42.png', 'unused'),
  ('43.png', 'unused'),
  ('44.png', 'unused'),
  ('45.png', 'unused'),
  ('46.png', 'unused'),
  ('47.png', 'unused'),
  ('48.png', 'unused'),
  ('49.png', 'unused'),
  ('5.jpg', 'unused'),
  ('50.png', 'unused'),
  ('6.jpg', 'unused'),
  ('7.jpg', 'unused'),
  ('8.jpg', 'unused'),
  ('9.jpg', 'unused')
ON DUPLICATE KEY UPDATE
  `status` = VALUES(`status`);
