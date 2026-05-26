-- NOXARA DEFAULT SEED DATA
-- PHP Native 8.2 & MySQL 8.x

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Insert default admin account (Password: admin12345)
INSERT INTO `admins` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'admin_noxara', 'admin@noxara.page', '$2y$10$wK6PjB/I76B093S8vG6LpeN8O723m0E9G3f9/1C5P9D46e9nE7E8O', 'superadmin');

-- 2. Insert default user (Password: jaka12345, Referral Code: JAKAFREE)
INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `status`, `referred_by`, `referral_code`) VALUES
(1, 'jaka', 'jaka@noxara.page', '081234567890', '$2y$10$wK6PjB/I76B093S8vG6LpeN8O723m0E9G3f9/1C5P9D46e9nE7E8O', 'active', NULL, 'JAKAFREE');

-- 3. Insert user profile for default user
INSERT INTO `user_profiles` (`user_id`, `full_name`, `avatar_url`) VALUES
(1, 'Jaka Kece', '/assets/img/avatar_default.png');

-- 4. Set initial balance for user (15,000 IDR welcome bonus)
INSERT INTO `balance_accounts` (`user_id`, `main_balance`, `bonus_balance`, `profit_balance`, `commission_balance`, `locked_balance`, `total_profit`) VALUES
(1, 0.00, 15000.00, 0.00, 0.00, 0.00, 0.00);

-- 5. Insert VIP level configurations
INSERT INTO `vip_levels` (`id`, `level_number`, `name`, `badge_url`, `min_total_topup`, `min_withdrawal`, `with_fee_percent`, `can_access_games`, `can_access_vouchers`, `is_active`) VALUES
(1, 0, 'VIP 0 Member', '/assets/icons/vip0.svg', 0.00, 100000.00, 10.00, 0, 0, 1),
(2, 1, 'VIP 1 Bronze', '/assets/icons/vip1.svg', 50000.00, 50000.00, 5.00, 1, 1, 1),
(3, 2, 'VIP 2 Silver', '/assets/icons/vip2.svg', 100000.00, 30000.00, 2.50, 1, 1, 1),
(4, 3, 'VIP 3 Gold', '/assets/icons/vip3.svg', 1000000.00, 0.00, 0.00, 1, 1, 1);

-- 6. Insert user vip status sync table
INSERT INTO `user_vip_status` (`user_id`, `level_number`, `accumulated_topup`) VALUES
(1, 0, 0.00);

-- 7. Insert Product Categories
INSERT INTO `product_categories` (`id`, `slug`, `label`) VALUES
(1, 'ordinary', 'Biasa'),
(2, 'medium', 'Medium'),
(3, 'high', 'High');

-- 8. Insert individual mining products (5 products per category, as required)
INSERT INTO `products` (`id`, `category_id`, `name`, `price`, `profit_per_day`, `duration_days`, `stock`, `is_active`) VALUES
-- Biasa Category
(1, 1, 'Noxara Basic Miner 1', 20000.00, 1500.00, 30, 999, 1),
(2, 1, 'Noxara Basic Miner 2', 50000.00, 4000.00, 30, 999, 1),
(3, 1, 'Noxara Basic Miner 3', 80000.00, 6500.00, 30, 999, 1),
(4, 1, 'Noxara Basic Miner 4', 120000.00, 10000.00, 30, 999, 1),
(5, 1, 'Noxara Basic Miner 5', 180000.00, 16000.00, 30, 999, 1),
-- Medium Category
(6, 2, 'Noxara Medium Miner 1', 250000.00, 23000.00, 30, 999, 1),
(7, 2, 'Noxara Medium Miner 2', 400000.00, 38000.00, 30, 999, 1),
(8, 2, 'Noxara Medium Miner 3', 600000.00, 60000.00, 30, 999, 1),
(9, 2, 'Noxara Medium Miner 4', 850000.00, 90000.00, 30, 999, 1),
(10, 2, 'Noxara Medium Miner 5', 1200000.00, 132000.00, 30, 999, 1),
-- High Category
(11, 3, 'Noxara High Miner 1', 2000000.00, 230000.00, 30, 999, 1),
(12, 3, 'Noxara High Miner 2', 3500000.00, 420000.00, 30, 999, 1),
(13, 3, 'Noxara High Miner 3', 5000000.00, 625000.00, 30, 999, 1),
(14, 3, 'Noxara High Miner 4', 8000000.00, 1050000.00, 30, 999, 1),
(15, 3, 'Noxara High Miner 5', 15000000.00, 2100000.00, 30, 999, 1);

-- 9. Insert Default Promotion Counter statistics
INSERT INTO `promo_counters` (`users_joined`, `total_deposits`, `total_withdrawals`, `successful_transactions`, `active_today`) VALUES
(6482, 1420750000.00, 984520000.00, 18520, 1485);

-- 10. Insert Welcome Popup Settings (WhatsApp Group configurations)
INSERT INTO `welcome_popup_settings` (`id`, `is_active`, `image_url`, `title`, `description`, `whatsapp_group_link`, `display_mode`) VALUES
(1, 1, '/uploads/welcome/promo_v2.png', 'Selamat datang di Platform NOXARA', 'Mari bergabung dengan grup WhatsApp resmi NOXARA untuk update bonus harian, kode promo terbaru, pertambangan miner modern, serta konsultasi CS 24/7!', 'https://chat.whatsapp.com/JakaKeceCommunity', 'once_until_close');

-- 11. Insert default banners
INSERT INTO `banners` (`image_url`, `redirect_url`, `sort_order`, `is_active`) VALUES
('/uploads/banners/banner1.jpg', '#', 1, 1),
('/uploads/banners/banner2.jpg', '#', 2, 1),
('/uploads/banners/banner3.jpg', '#', 3, 1);

-- 12. Insert Default Mini-Games setup
INSERT INTO `vip_games` (`id`, `game_type`, `min_vip_level`, `is_active`, `plays_limit_per_day`, `cooldown_seconds`, `game_duration_seconds`, `max_reward_per_session`, `max_reward_per_day`, `coin_value`) VALUES
(1, 'scratch', 1, 1, 3, 300, 0, 10000.00, 25000.00, 0.00),
(2, 'puzzle', 2, 1, 2, 1200, 60, 25000.00, 50000.00, 0.00),
(3, 'tapcoin', 3, 1, 1, 3600, 30, 50000.00, 10000.00, 250.00);

-- 13. Insert Default VIP Mini-game potential weights
INSERT INTO `vip_game_rewards` (`game_id`, `reward_amount`, `weight`, `is_zonk`) VALUES
(1, 0.00, 30, 1),
(1, 2500.00, 40, 0),
(1, 5000.00, 20, 0),
(1, 10000.00, 10, 0),
(2, 5000.00, 50, 0),
(2, 12500.00, 30, 0),
(2, 25000.00, 20, 0);

-- 14. Insert Daily Calendar Claim bonus tiers (7 days Calendar, Day 1 to 7)
INSERT INTO `daily_bonus_rewards` (`day_number`, `reward_amount`) VALUES
(1, 2000.00),
(2, 3000.00),
(3, 4000.00),
(4, 5000.00),
(5, 6000.00),
(6, 8000.00),
(7, 15000.00);

-- 15. Insert Cashify configurations
INSERT INTO `cashify_settings` (`id`, `cashify_base_url`, `cashify_api_version`, `cashify_qris_id`, `cashify_license_key`, `cashify_package_ids`, `cashify_qr_type`, `cashify_payment_method`, `cashify_use_qris`, `cashify_use_unique_code`, `cashify_expired_minutes`) VALUES
(1, 'https://cashify.my.id', 'v2', '1b935c41-bf43-4075-8f57-56b6cbfa2d07', 'cashify_261885e5c5f830e68f929de05e3bfdf72e118d859edc5419472f79a813eed3ea', '["com.orderkuota.app"]', 'static', 'qris', 1, 1, 15);

-- 16. Insert Referral Commission Rates defaults (3 tier levels)
INSERT INTO `referral_commission_rates` (`commission_type`, `level_1_percent`, `level_2_percent`, `level_3_percent`) VALUES
('topup', 10.00, 5.00, 2.00),
('product_purchase', 10.00, 4.00, 1.00);

-- 17. Insert Default Quick Deposits Option Buttons
INSERT INTO `deposit_quick_amounts` (`amount`, `sort_order`) VALUES
(50000.00, 1),
(100000.00, 2),
(200000.00, 3),
(500000.00, 4),
(1000000.00, 5);

-- 18. Insert Default Display Methods for deposits
INSERT INTO `deposit_display_methods` (`method_slug`, `label`, `is_active`, `image_logo`) VALUES
('qris', 'QRIS Mandiri/GOPAY/Shopee/OVO Instant', 1, '/assets/icons/qris.svg'),
('bca', 'Virtual Account BCA (Instant)', 1, '/assets/icons/bca.svg'),
('bni', 'Virtual Account BNI (Instant)', 1, '/assets/icons/bni.svg'),
('dana', 'DANA E-Wallet Fast Link', 1, '/assets/icons/dana.svg'),
('gopay', 'GoPay Cashless Direct', 1, '/assets/icons/gopay.svg');

-- 19. Insert default site details
INSERT INTO `site_settings` (`key`, `value`) VALUES
('contact_cs_whatsapp', '+6281234567890'),
('download_apk_link', '/uploads/app/noxara.apk'),
('marquee_announce', 'Selamat datang di NOXARA - Platform Penyewaan Mesin Mining Crypto Modern #1 di Indonesia! Nikmati bonus pendaftaran Rp 15.000 dan komisi referral hingga 3 tingkat kedalaman! Hubungi admin CS jika butuh bantuan.'),
('maintenance_mode', '0');

-- 20. App Feature Toggle settings
INSERT INTO `feature_settings` (`id`, `register`, `login`, `deposit`, `withdraw`, `products`, `mining`, `team`, `voucher`, `vip`, `game`, `daily_bonus`, `promo`, `live_chat`, `download_file`, `information`, `notifications`, `welcome_popup`) VALUES
(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

-- 21. Dynamic Homepage Menu settings
INSERT INTO `menu_settings` (`id`, `menu_vip`, `menu_voucher`, `menu_game`, `menu_daily_bonus`, `menu_contact_admin`, `menu_information`, `menu_download_file`, `menu_promo`, `nav_home`, `nav_team`, `nav_product`, `nav_mining`, `nav_transactions`, `nav_profile`) VALUES
(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

-- 22. Maintenance settings
INSERT INTO `maintenance_settings` (`id`, `is_active`, `message`) VALUES
(1, 0, 'Sistem NOXARA sedang dalam pemeliharaan berkala untuk meningkatkan kenyamanan Anda. Silakan hubungi CS via WhatsApp untuk info lanjut.');

-- 23. Insert default Information Pages FAQ & Accordions
INSERT INTO `information_pages` (`category`, `title`, `content`, `sort_order`, `is_active`) VALUES
('faq', 'Apakah saldo bonus pendaftaran Rp15.000 bisa ditarik?', 'Tidak. Saldo bonus pendaftaran sebesar Rp15.000 hanya dapat digunakan untuk membeli produk mesin pertambangan (miner). Ketika Anda membeli produk, saldo bonus Anda akan dipotong terlebih dahulu sebelum saldo utama Anda digunakan.', 1, 1),
('faq', 'Bagaimana cara naik level VIP?', 'Sistem VIP akan dihitung secara otomatis berdasarkan TOTAL akumulasi isi ulang saldo sukses ( approved top-up ). Contoh: VIP 1 minimal top-up Rp50.000, VIP 2 minimal Rp100.000, dan VIP 3 minimal Rp1.000.000. Pembelian produk tidak akan menurunkan level VIP Anda.', 2, 1),
('faq', 'Apakah durasi produk berkurang walaupun saya tidak menambang?', 'Ya. Durasi pertambangan adalah default 30 hari berjalan konstan. Jika Anda lupa mengklik tombol "Mulai Mining" pada hari tersebut, profit harian Anda untuk hari itu akan hangus tetapi durasi produk akan tetap berkurang.', 3, 1),
('about_vip', 'Manfaat Utama Skema VIP NOXARA', 'VIP 0: Biaya penarikan 10%, minimal penarikan Rp100.000, tidak memiliki akses voucher dan game.\nVIP 1: Biaya penarikan 5%, minimal penarikan Rp50.000, akses voucher VIP 1 dan Scratch Card harian.\nVIP 2: Biaya penarikan 2.5%, minimal penarikan Rp30.000, akses voucher VIP 1+2 & Puzzle game harian.\nVIP 3: Biaya penarikan 0% (Gratis), minimal penarikan Rp0 (Tanpa Batas), akses seluruh voucher, game & TapCoin berkemampuan tinggi.', 1, 1),
('usage', 'Cara Mengoperasikan Mesin Tambang NOXARA', '1. Lakukan pendaftaran akun dan dapatkan modal gratis Rp15.000.\n2. Lakukan deposit/isi ulang saldo utama (mulai Rp50.000) untuk memulai penyewaan mesin tambang.\n3. Masuk ke halaman Produk (+) lalu pilih mesin pertambangan Anda.\n4. Setelah sukses membeli, masuk ke tab "Mining". Klik tombol "Mulai Pertambangan".\n5. Pertambangan akan berlangsung selama default countdown 2 jam. Setelah 2 jam, klik tombol "Klaim Profit" atau tunggu cron penambangan mendistribusikan profit harian Anda ke saldo utama.', 1, 1),
('privacy', 'Kebijakan Privasi NOXARA', 'Informasi akun Anda dienkripsi penuh menggunakan algoritma sandi bcrypt satu arah (password_hash). Kami tidak membagikan ataupun menyewakan nomor telepon, nomor rekening bank, dan rincian transaksi penarikan Anda ke pihak ketiga manapun. Keamanan dan kenyamanan Anda adalah prioritas mutlak kami.', 1, 1),
('tos', 'Syarat & Ketentuan Penggunaan Layanan', '1. Setiap pengguna hanya diizinkan memiliki 1 akun aktif tunggal. Penggunaan akun ganda (multi-accounting) untuk kecurangan komisi rabat akan mengakibatkan pembekuan saldo secara sepihak oleh admin.\n2. Semua pengajuan withdraw diproses maksimal 1x24 jam.\n3. Platform NOXARA berhak mengubah persentase komisi, skema bonus, dan ketersediaan mesin tambang kapan saja berkoordinasi dengan kebijakan pasar pertambangan.', 1, 1);

-- 24. Insert default diagnostic vouchers
INSERT INTO `vouchers` (`id`, `code`, `type`, `rewards_type`, `value`, `min_transaction_amount`, `max_discount_amount`, `min_vip_level`, `total_quota`, `used_quota`, `valid_from`, `valid_until`, `per_user_limit`, `is_active`) VALUES
(1, 'BONUSTOPUP', 'topup_bonus', 'fixed', 10000.00, 50000.00, 0.00, 1, 100, 0, '2026-01-01 00:00:00', '2027-12-31 23:59:59', 1, 1),
(2, 'MINERDISCOUNT', 'product_discount', 'percent', 10.00, 20000.00, 15000.00, 2, 200, 0, '2026-01-01 00:00:00', '2027-12-31 23:59:59', 1, 1),
(3, 'CLAIM5K', 'balance_claim', 'fixed', 5000.00, 0.00, 0.00, 1, 500, 0, '2026-01-01 00:00:00', '2027-12-31 23:59:59', 1, 1);

SET FOREIGN_KEY_CHECKS = 1;
