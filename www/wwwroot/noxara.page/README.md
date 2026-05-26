# NOXARA Deployment Guide (aaPanel, Nginx, PHP 8.2, MySQL 8.x)

Welcome to the production release of **NOXARA** – a premium, highly secure mobile cloud-mining platform built using PHP Native 8.2, PDO MySQL, and aaPanel Nginx VPS configurations.

---

## 📂 Final Directory Tree Structure

All files are neatly structured inside your root web directory `/www/wwwroot/noxara.page`:

```text
/www/wwwroot/noxara.page
├── index.php                           # Main dynamic unified router, gates and controllers
├── README.md                           # This setup manual
├── assets/
│   ├── css/                            # UI stylesheets (Tailwind powered)
│   ├── js/                             # Dynamic Vanilla JS scripts (animations & UI)
│   ├── img/                            # Static banner sliders and logo renders
│   └── icons/                          # Premium unified SVG vector icons
├── uploads/                            # Dynamic file repository (PHP executions are BLOCKED here)
│   ├── welcome/                        # Onboarding banner layers
│   ├── banners/                        # Carousel home images
│   ├── promos/                         # Promotional events thumbnails
│   ├── chat/                           # Support tickets uploaded screenshots
│   ├── payment/                        # Payment receipts fallback
│   └── qris/                           # Saved Cashify static templates
├── config/
│   ├── app.php                         # Global app adjustments (Timezone: Asia/Jakarta)
│   └── database.php                    # Private MySQL login settings
├── includes/
│   ├── db.php                          # Secured PDO connection handler
│   ├── session.php                     # Secure PHP Session config & anti-hijacking layers
│   ├── csrf.php                        # Anti-Cross Site Request Forgery token managers
│   ├── captcha.php                     # Lightweight mathematical CAPTCHA generator
│   └── helpers.php                     # Global formatting tools and escape methods
├── database/
│   ├── schema.sql                      # Complete MySQL 55+ tables architecture
│   └── seed.sql                        # Default seed metrics (Admins, default VIPs, category miners)
├── cron/
│   ├── mining-cron.php                 # Distributes hourly miners dividend
│   ├── product-expire-cron.php         # Clean up expired lease miners active_until
│   ├── cashify-payment-cron.php        # Synchronizes payments via Cashify QRIS APIs
│   ├── vip-sync-cron.php               # Solves membership level synchronization
│   └── backup-cron.php                 # Automates VPS mysql backups to storage
├── storage/
│   ├── logs/                           # System cron audits and error logs
│   ├── cache/                          # Temporary configurations cache
│   └── backups/                        # Database daily exports
└── nginx/
    └── noxara.conf                     # Custom Nginx site-block security settings
```

---

## 🚀 Step-by-Step Deployment Protocol

### Step 1: Initialize aaPanel and Prepare VPS
1. Log into your **aaPanel Admin Console**.
2. Go to **App Store** and ensure you have installed:
   - **Nginx** (any stable variant)
   - **PHP 8.2**
   - **MySQL 8.x**
   - **phpMyAdmin**

### Step 2: Create Web Server Block
1. Navigate to the **Website** tab in aaPanel.
2. Click **Add Site**:
   - **Domain**: `noxara.page`
   - **Document Root**: `/www/wwwroot/noxara.page`
   - **Database**: Select **MySQL** -> create database with credentials:
     - **Database Name**: `noxara_Jaka22`
     - **Username**: `noxara_Jaka22`
     - **Password**: `Jakakece12`
   - **PHP Version**: Choose `PHP-82`

### Step 3: Populate Databases (import schema & seed)
1. In aaPanel, choose the **Database** tab or open **phpMyAdmin**.
2. Click **Import** on `noxara_Jaka22` database:
   - Run `/www/wwwroot/noxara.page/database/schema.sql` first.
   - Run `/www/wwwroot/noxara.page/database/seed.sql` to populate default miners, settings, and accounts.

### Step 4: Setup SSL (Secure HTTPs)
1. In aaPanel website settings for `noxara.page`, choose **SSL** option.
2. Choose **Let's Encrypt**:
   - Check domains: `noxara.page` and `www.noxara.page`.
   - Click **Apply** to generate and auto-renew the certificate.
   - Check the **Force HTTPS** toggle to redirect traffic securely.

### Step 5: Inject aaPanel Nginx Security Block
To block direct access to system components (`config`, `includes`, `cron`, etc.), you **must** apply our custom security rules:
1. Open the website configuration panel for `noxara.page` inside aaPanel.
2. Go to the **Config** tab or **URL Rewrite** / **Configuration file** section.
3. Paste the contents of `/www/wwwroot/noxara.page/nginx/noxara.conf` directly inside your server block, and reload Nginx.
4. *Verify:* Try navigating to `https://noxara.page/config/database.php`. It must return a **403 Forbidden** error.

### Step 6: Configure aaPanel Cron Jobs
Set up automated processes to keep payments, mining payouts, and system states up to date. Go to the **Cron** tab in aaPanel and add these 5 cron jobs:

1. **Reconcile Payments (Cashify)** (Runs every minute):
   - **Type**: Shell Script
   - **Name**: Noxara Cashify Payment Polling
   - **Script**: `php /www/wwwroot/noxara.page/cron/cashify-payment-cron.php`
   - **Period**: Every 1 minute

2. **Automate Mining Payouts** (Runs every minute):
   - **Type**: Shell Script
   - **Name**: Noxara Mining Dividend Automation
   - **Script**: `php /www/wwwroot/noxara.page/cron/mining-cron.php`
   - **Period**: Every 1 minute

3. **Check Leases Expiration** (Runs hourly):
   - **Type**: Shell Script
   - **Name**: Noxara Lease Kontrak Expiry
   - **Script**: `php /www/wwwroot/noxara.page/cron/product-expire-cron.php`
   - **Period**: N-Hours (1 Hour)

4. **Verify VIP Level drifts** (Runs daily at midnight):
   - **Type**: Shell Script
   - **Name**: Noxara VIP Level Doctor
   - **Script**: `php /www/wwwroot/noxara.page/cron/vip-sync-cron.php`
   - **Period**: Every Day at 00:10

5. **Local DB Backup** (Runs daily):
   - **Type**: Shell Script
   - **Name**: Noxara Daily SQL Backups
   - **Script**: `php /www/wwwroot/noxara.page/cron/backup-cron.php`
   - **Period**: Every Day at 01:00

---

## 🔒 Security Operations Checklist

1. **Strict Root Protection:** The production domain is bound directly to `/www/wwwroot/noxara.page`. The private folders `config/` and `storage/` are hidden via custom Nginx location rule blocks.
2. **AntiPHP Upload Gate:** Files uploaded inside `uploads/chat/` are stripped of PHP execution capabilities via Nginx configurations to prevent remote shell exploits.
3. **Prepared Statements Check:** All SQL commands run through secure PDO parameters. No SQL strings are concatenated directly.
4. **Idempotent Ledgers:** Double-payouts for mining profit distribution, referral rewards, and Cashify deposits are audited using the unique `idempotency_key` constraint.

---

## 🧪 Testing and Troubleshooting

### Test A: Default Credentials
After seed initialization, you can test authentication on your mobile phone screen instantly:
- **Default User Account**: Username: `jaka` | Password: `Jakakece12` (starts with Rp15.000 shopping balance)
- **Default Admin Account**: Username: `admin_noxara` | Password: `admin12345`

### Test B: Cashify Deposit Simulation
1. Request a deposit of Rp50,000 from the user dashboard.
2. The Cashify API coordinates the invoice with static details.
3. Pay via QRIS simulator. The `cashify-payment-cron.php` automatically fetches status, updates balances, and awards Level 1 VIP automatically.
