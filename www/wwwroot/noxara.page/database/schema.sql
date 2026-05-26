-- NOXARA DATABASE SCHEMA (MySQL 8.x)
-- Engine: InnoDB, Charset: utf8mb4

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'frozen', 'banned') DEFAULT 'active',
  `referred_by` BIGINT UNSIGNED DEFAULT NULL,
  `referral_code` VARCHAR(20) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_referral` (`referred_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Admins table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin', 'manager', 'cs') DEFAULT 'cs',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User Profiles table
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `full_name` VARCHAR(100) DEFAULT NULL,
  `avatar_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. User Bank Accounts
CREATE TABLE IF NOT EXISTS `user_bank_accounts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `bank_name` VARCHAR(100) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `account_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. User PINs
CREATE TABLE IF NOT EXISTS `user_pins` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `pin_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. PIN Reset Requests (via WhatsApp admin trigger)
CREATE TABLE IF NOT EXISTS `pin_reset_requests` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `resolver_admin_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`resolver_admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Balance Accounts (Sub-balances)
CREATE TABLE IF NOT EXISTS `balance_accounts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `main_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `bonus_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `profit_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `commission_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `locked_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `total_profit` DECIMAL(15, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Ledger Transactions (Secure bookkeeping)
CREATE TABLE IF NOT EXISTS `ledger_transactions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `idempotency_key` VARCHAR(150) NOT NULL UNIQUE,
  `type` VARCHAR(50) NOT NULL, -- 'topup', 'withdraw', 'purchase', 'dividend', 'referral', 'bonus', 'game'
  `main_delta` DECIMAL(15, 2) DEFAULT 0.00,
  `bonus_delta` DECIMAL(15, 2) DEFAULT 0.00,
  `profit_delta` DECIMAL(15, 2) DEFAULT 0.00,
  `commission_delta` DECIMAL(15, 2) DEFAULT 0.00,
  `locked_delta` DECIMAL(15, 2) DEFAULT 0.00,
  `description` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_ledger_type` (`type`),
  INDEX `idx_ledger_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Site Settings
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` LONGTEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Promo Counters (Counters in onboarding screen)
CREATE TABLE IF NOT EXISTS `promo_counters` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `users_joined` INT NOT NULL DEFAULT 5849,
  `total_deposits` DECIMAL(18, 2) DEFAULT 1284900000.00,
  `total_withdrawals` DECIMAL(18, 2) DEFAULT 842900000.00,
  `successful_transactions` INT NOT NULL DEFAULT 14820,
  `active_today` INT NOT NULL DEFAULT 1205,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Welcome Popup Settings
CREATE TABLE IF NOT EXISTS `welcome_popup_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `image_url` VARCHAR(255) DEFAULT '/uploads/welcome/promo_default.png',
  `title` VARCHAR(150) DEFAULT 'Selamat datang di Platform NOXARA',
  `description` TEXT,
  `whatsapp_group_link` VARCHAR(255) DEFAULT 'https://chat.whatsapp.com/noxara_default',
  `display_mode` ENUM('every_login', 'once_a_day', 'once_until_close') DEFAULT 'once_until_close',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Banners Slider
CREATE TABLE IF NOT EXISTS `banners` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `image_url` VARCHAR(255) NOT NULL,
  `redirect_url` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED DEFAULT NULL, -- NULL means global announcement
  `title` VARCHAR(150) NOT NULL,
  `content` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Information Pages (Accordion / Guide pages)
CREATE TABLE IF NOT EXISTS `information_pages` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category` ENUM('usage', 'about_vip', 'about_platform', 'faq', 'privacy', 'tos') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. APK/App Download Settings (NO APK builder - website button downloads designated APK link)
CREATE TABLE IF NOT EXISTS `app_download_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `apk_url` VARCHAR(255) DEFAULT '/uploads/app/noxara.apk',
  `app_version` VARCHAR(20) DEFAULT '1.0.0',
  `file_size` VARCHAR(20) DEFAULT '12.4 MB',
  `download_count` INT DEFAULT 3845,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Promos / Events Info
CREATE TABLE IF NOT EXISTS `promos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Live Chat Threads
CREATE TABLE IF NOT EXISTS `chat_threads` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `unread_by_admin` INT DEFAULT 0,
  `unread_by_user` INT DEFAULT 0,
  `is_resolved` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Live Chat Messages
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `thread_id` BIGINT UNSIGNED NOT NULL,
  `sender_type` ENUM('user', 'admin') NOT NULL,
  `sender_id` BIGINT UNSIGNED NOT NULL, -- references users.id or admins.id
  `message` TEXT,
  `attachment_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`thread_id`) REFERENCES `chat_threads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. VIP Levels
CREATE TABLE IF NOT EXISTS `vip_levels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `level_number` INT NOT NULL UNIQUE,
  `name` VARCHAR(50) NOT NULL,
  `badge_url` VARCHAR(255) DEFAULT NULL,
  `min_total_topup` DECIMAL(15, 2) NOT NULL,
  `min_withdrawal` DECIMAL(15, 2) NOT NULL,
  `with_fee_percent` DECIMAL(5, 2) DEFAULT 0.00,
  `can_access_games` TINYINT(1) DEFAULT 0,
  `can_access_vouchers` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. User VIP Status (Sync/Cache)
CREATE TABLE IF NOT EXISTS `user_vip_status` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `level_number` INT NOT NULL DEFAULT 0,
  `accumulated_topup` DECIMAL(15, 2) DEFAULT 0.00,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Vouchers
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('topup_bonus', 'product_discount', 'balance_claim') NOT NULL,
  `rewards_type` ENUM('fixed', 'percent') DEFAULT 'fixed',
  `value` DECIMAL(15, 2) NOT NULL,
  `min_transaction_amount` DECIMAL(15, 2) DEFAULT 0.00,
  `max_discount_amount` DECIMAL(15, 2) DEFAULT 0.00,
  `min_vip_level` INT DEFAULT 0,
  `total_quota` INT DEFAULT 100,
  `used_quota` INT DEFAULT 0,
  `valid_from` TIMESTAMP NULL,
  `valid_until` TIMESTAMP NULL,
  `per_user_limit` INT DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Voucher Usages
CREATE TABLE IF NOT EXISTS `voucher_usages` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `voucher_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `referenced_id` VARCHAR(50) DEFAULT NULL, -- order ID or topup ID
  `reward_amount` DECIMAL(15, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. VIP Games (Mini Game reward engine)
CREATE TABLE IF NOT EXISTS `vip_games` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `game_type` ENUM('scratch', 'puzzle', 'tapcoin') NOT NULL UNIQUE,
  `min_vip_level` INT DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `plays_limit_per_day` INT DEFAULT 1,
  `cooldown_seconds` INT DEFAULT 0,
  `game_duration_seconds` INT DEFAULT 30,
  `max_reward_per_session` DECIMAL(15, 2) DEFAULT 10000.00,
  `max_reward_per_day` DECIMAL(15, 2) DEFAULT 50000.00,
  `coin_value` DECIMAL(10, 2) DEFAULT 100.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. VIP Game Rewards
CREATE TABLE IF NOT EXISTS `vip_game_rewards` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `game_id` INT NOT NULL,
  `reward_amount` DECIMAL(15, 2) NOT NULL,
  `weight` INT NOT NULL DEFAULT 10,  -- Probability selection weight
  `is_zonk` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`game_id`) REFERENCES `vip_games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. VIP Game Sessions
CREATE TABLE IF NOT EXISTS `vip_game_sessions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_token` VARCHAR(150) NOT NULL UNIQUE,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `game_id` INT NOT NULL,
  `status` ENUM('init', 'completed', 'expired') DEFAULT 'init',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`game_id`) REFERENCES `vip_games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. VIP Game Plays (Results repository)
CREATE TABLE IF NOT EXISTS `vip_game_plays` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `reward_amount` DECIMAL(15, 2) DEFAULT 0.00,
  `game_data` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `vip_game_sessions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. Daily Bonus Calendar Settings
CREATE TABLE IF NOT EXISTS `daily_bonus_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `updated_by_admin` BIGINT UNSIGNED DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 28. Daily Bonus Rewards Configuration (Calendar 7 days loadout)
CREATE TABLE IF NOT EXISTS `daily_bonus_rewards` (
  `day_number` INT PRIMARY KEY, -- 1 to 7
  `reward_amount` DECIMAL(15, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 29. Daily Bonus Claims
CREATE TABLE IF NOT EXISTS `daily_bonus_claims` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `day_number` INT NOT NULL,
  `reward_amount` DECIMAL(15, 2) NOT NULL,
  `claimed_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uniq_user_day_date` (`user_id`, `claimed_date`),
  UNIQUE KEY `uniq_user_day_number` (`user_id`, `day_number`) -- resets after 7th day claim completes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 30. Product Categories
CREATE TABLE IF NOT EXISTS `product_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE, -- 'ordinary', 'medium', 'high'
  `label` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 31. Products
CREATE TABLE IF NOT EXISTS `products` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(15, 2) NOT NULL,
  `profit_per_day` DECIMAL(15, 2) NOT NULL,
  `duration_days` INT DEFAULT 30,
  `total_earnings` DECIMAL(15, 2) GENERATED ALWAYS AS (profit_per_day * duration_days) STORED,
  `roi_percent` DECIMAL(8, 2) GENERATED ALWAYS AS ((profit_per_day * duration_days) / price * 100) STORED,
  `stock` INT DEFAULT 999,
  `is_active` TINYINT(1) DEFAULT 1,
  `image_url` VARCHAR(255) DEFAULT '/assets/img/product_miner.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 32. User Active Products (Inventory)
CREATE TABLE IF NOT EXISTS `user_products` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `purchase_price` DECIMAL(15, 2) NOT NULL,
  `profit_per_day` DECIMAL(15, 2) NOT NULL,
  `dur_remaining` INT NOT NULL,
  `active_until` TIMESTAMP NOT NULL,
  `status` ENUM('active', 'expired', 'refunded') DEFAULT 'active',
  `last_mined_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 33. Product Purchases Log
CREATE TABLE IF NOT EXISTS `product_purchases` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `subtotal` DECIMAL(15, 2) NOT NULL,
  `discount` DECIMAL(15, 2) DEFAULT 0.00,
  `voucher_id` BIGINT UNSIGNED DEFAULT NULL,
  `total_paid` DECIMAL(15, 2) NOT NULL,
  `bonus_spent` DECIMAL(15, 2) DEFAULT 0.00,
  `main_spent` DECIMAL(15, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 34. Mining settings
CREATE TABLE IF NOT EXISTS `mining_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `cooldown_hours` INT DEFAULT 2, -- Default 2 hours countdown per mining session
  `daily_reset_time` VARCHAR(10) DEFAULT '00:01',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 35. Mining active sessions
CREATE TABLE IF NOT EXISTS `mining_sessions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `user_product_id` BIGINT UNSIGNED NOT NULL,
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ends_at` TIMESTAMP NOT NULL,
  `status` ENUM('running', 'completed', 'claimed') DEFAULT 'running',
  `profit_amount` DECIMAL(15, 2) NOT NULL,
  `idempotency_key` VARCHAR(150) NOT NULL UNIQUE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_product_id`) REFERENCES `user_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 36. Mining Profit Logs (Dedicated history tab)
CREATE TABLE IF NOT EXISTS `mining_profit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `session_id` BIGINT UNSIGNED NOT NULL,
  `profit_amount` DECIMAL(15, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`session_id`) REFERENCES `mining_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 37. Referral System Configuration
CREATE TABLE IF NOT EXISTS `referral_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 38. Referral Commission Rates (Configurable multi-level commission percentages)
CREATE TABLE IF NOT EXISTS `referral_commission_rates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `commission_type` ENUM('topup', 'product_purchase') NOT NULL,
  `level_1_percent` DECIMAL(5, 2) NOT NULL, -- Level 1 Uplines percentage
  `level_2_percent` DECIMAL(5, 2) NOT NULL, -- Level 2 Uplines percentage
  `level_3_percent` DECIMAL(5, 2) NOT NULL, -- Level 3 Uplines percentage
  UNIQUE KEY `uniq_comm_type` (`commission_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 39. User Referral Connections
CREATE TABLE IF NOT EXISTS `user_referrals` (
  `user_id` BIGINT UNSIGNED PRIMARY KEY,
  `referred_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 40. Referral Tree Tracker (Recursive indexing helper)
CREATE TABLE IF NOT EXISTS `referral_tree` (
  `ancestor_id` BIGINT UNSIGNED NOT NULL,
  `descendant_id` BIGINT UNSIGNED NOT NULL,
  `depth` INT NOT NULL,
  PRIMARY KEY (`ancestor_id`, `descendant_id`),
  FOREIGN KEY (`ancestor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`descendant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 41. Referral Commissions (Detailed payout list)
CREATE TABLE IF NOT EXISTS `referral_commissions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `recipient_user_id` BIGINT UNSIGNED NOT NULL,
  `trigger_user_id` BIGINT UNSIGNED NOT NULL,
  `type` ENUM('topup', 'product_purchase') NOT NULL,
  `reference_id` VARCHAR(50) NOT NULL,  -- topups.id or product_purchases.id
  `level_depth` INT NOT NULL,           -- 1, 2, or 3
  `source_amount` DECIMAL(15, 2) NOT NULL,
  `commission_rate` DECIMAL(5, 2) NOT NULL,
  `commission_amount` DECIMAL(15, 2) NOT NULL,
  `idempotency_key` VARCHAR(150) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`trigger_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 42. Withdrawals System
CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL,
  `fee_amount` DECIMAL(15, 2) NOT NULL,
  `payout_amount` DECIMAL(15, 2) NOT NULL,
  `bank_name` VARCHAR(100) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `account_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `vip_level` INT DEFAULT 0,
  `trans_id` VARCHAR(50) NOT NULL UNIQUE,
  `resolver_admin_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`resolver_admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 43. Cashify Integration Credentials
CREATE TABLE IF NOT EXISTS `cashify_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `cashify_base_url` VARCHAR(255) DEFAULT 'https://cashify.my.id',
  `cashify_api_version` VARCHAR(10) DEFAULT 'v2',
  `cashify_qris_id` VARCHAR(100) DEFAULT '1b935c41-bf43-4075-8f57-56b6cbfa2d07',
  `cashify_license_key` VARCHAR(255) DEFAULT 'cashify_261885e5c5f830e68f929de05e3bfdf72e118d859edc5419472f79a813eed3ea',
  `cashify_webhook_secret` VARCHAR(255) DEFAULT NULL,
  `cashify_package_ids` JSON DEFAULT NULL, -- default ["com.orderkuota.app"]
  `cashify_qr_type` VARCHAR(50) DEFAULT 'static',
  `cashify_payment_method` VARCHAR(50) DEFAULT 'qris',
  `cashify_use_qris` TINYINT(1) DEFAULT 1,
  `cashify_use_unique_code` TINYINT(1) DEFAULT 1,
  `cashify_expired_minutes` INT DEFAULT 15,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 44. Topup orders via Cashify QRIS integration
CREATE TABLE IF NOT EXISTS `topups` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `nominal` DECIMAL(15, 2) NOT NULL, -- pure amount requested
  `voucher_id` BIGINT UNSIGNED DEFAULT NULL,
  `voucher_bonus` DECIMAL(15, 2) DEFAULT 0.00,
  `unique_code` VARCHAR(10) DEFAULT NULL,
  `total_amount` DECIMAL(15, 2) NOT NULL, -- original amount + unique code difference
  `cashify_transaction_id` VARCHAR(100) DEFAULT NULL,
  `qr_string` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'expired', 'failed', 'cancelled') DEFAULT 'pending',
  `payment_display_method` VARCHAR(50) DEFAULT 'qris', -- BCA, DANA UI tags
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  `approved_at` TIMESTAMP NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 45. Deposit Display Layout Methods (BCA, Mandiri, DANA, etc. UX tags)
CREATE TABLE IF NOT EXISTS `deposit_display_methods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `method_slug` VARCHAR(50) NOT NULL UNIQUE,
  `label` VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `image_logo` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 46. Deposit Quick Amounts Shortcuts
CREATE TABLE IF NOT EXISTS `deposit_quick_amounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `amount` DECIMAL(15, 2) NOT NULL,
  `sort_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 47. Global Features active status configs
CREATE TABLE IF NOT EXISTS `feature_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `register` TINYINT(1) DEFAULT 1,
  `login` TINYINT(1) DEFAULT 1,
  `deposit` TINYINT(1) DEFAULT 1,
  `withdraw` TINYINT(1) DEFAULT 1,
  `products` TINYINT(1) DEFAULT 1,
  `mining` TINYINT(1) DEFAULT 1,
  `team` TINYINT(1) DEFAULT 1,
  `voucher` TINYINT(1) DEFAULT 1,
  `vip` TINYINT(1) DEFAULT 1,
  `game` TINYINT(1) DEFAULT 1,
  `daily_bonus` TINYINT(1) DEFAULT 1,
  `promo` TINYINT(1) DEFAULT 1,
  `live_chat` TINYINT(1) DEFAULT 1,
  `download_file` TINYINT(1) DEFAULT 1,
  `information` TINYINT(1) DEFAULT 1,
  `notifications` TINYINT(1) DEFAULT 1,
  `welcome_popup` TINYINT(1) DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 48. Menu grid configs (For dynamic homepage displays)
CREATE TABLE IF NOT EXISTS `menu_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  -- Grid menus
  `menu_vip` TINYINT(1) DEFAULT 1,
  `menu_voucher` TINYINT(1) DEFAULT 1,
  `menu_game` TINYINT(1) DEFAULT 1,
  `menu_daily_bonus` TINYINT(1) DEFAULT 1,
  `menu_contact_admin` TINYINT(1) DEFAULT 1,
  `menu_information` TINYINT(1) DEFAULT 1,
  `menu_download_file` TINYINT(1) DEFAULT 1,
  `menu_promo` TINYINT(1) DEFAULT 1,
  -- Bottom menus
  `nav_home` TINYINT(1) DEFAULT 1,
  `nav_team` TINYINT(1) DEFAULT 1,
  `nav_product` TINYINT(1) DEFAULT 1,
  `nav_mining` TINYINT(1) DEFAULT 1,
  `nav_transactions` TINYINT(1) DEFAULT 1,
  `nav_profile` TINYINT(1) DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 49. Maintenance Mode triggers
CREATE TABLE IF NOT EXISTS `maintenance_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 0,
  `message` VARCHAR(255) DEFAULT 'Website sedang dalam peningkatan server. Silakan kembali lagi nanti.',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 50. Frozen / Block accounts records
CREATE TABLE IF NOT EXISTS `user_freezes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `reason` TEXT NOT NULL,
  `admin_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 51. User Status History Logs
CREATE TABLE IF NOT EXISTS `user_status_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `previous_status` VARCHAR(20) NOT NULL,
  `new_status` VARCHAR(20) NOT NULL,
  `changed_by_admin_id` BIGINT UNSIGNED DEFAULT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by_admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 52. Admin Action Audit Logs (For critical compliance monitoring)
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` BIGINT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `action_type` VARCHAR(100) NOT NULL, -- 'approve_topup', 'reject_topup', 'edit_settings', 'unfreeze_user'
  `target_id` VARCHAR(50) DEFAULT NULL,
  `details` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 53. Rate limit: login attempts tracker
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `attempt_time` INT UNSIGNED NOT NULL,
  INDEX `idx_rate_limit` (`ip_address`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 54. Password resets via WhatsApp security callbacks
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `phone` VARCHAR(20) NOT NULL,
  `token` VARCHAR(100) NOT NULL UNIQUE,
  `status` ENUM('valid', 'used', 'expired') DEFAULT 'valid',
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 55. Cron execution records (Telemetry audits)
CREATE TABLE IF NOT EXISTS `cron_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cron_name` VARCHAR(100) NOT NULL, -- 'mining-cron', 'vip-sync-cron'
  `status` ENUM('success', 'failed') NOT NULL,
  `output_summary` TEXT,
  `duration_seconds` DECIMAL(10, 3) DEFAULT 0.000,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
