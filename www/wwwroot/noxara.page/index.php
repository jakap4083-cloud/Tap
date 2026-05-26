<?php
/**
 * NOXARA - Premium Mobile Web Application Index Router
 * Target Client: Mobile Browser Chrome / Safari
 * Max Width: 430px - 480px (Centered Responsive layout)
 * Theme: Blue & Black Premium
 */

// 1. Core systems boots
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/captcha.php';
require_once __DIR__ . '/includes/helpers.php';

// Safe include PDO
$pdo = null;
try {
    $pdo = require_once __DIR__ . '/includes/db.php';
} catch (Exception $e) {
    // Database connection issues are handled gracefully in db.php
}

// 2. Query maintenance triggers & global site settings
$is_maintenance = false;
$marquee_text = "Selamat datang di NOXARA - Platform Penyewaan Mesin Mining Crypto Modern #1 di Indonesia!";
$whatsapp_cs = "https://wa.me/6281234567890";
$apk_link = "/uploads/app/noxara.apk";

if ($pdo) {
    try {
        $st = $pdo->query("SELECT `key`, `value` FROM `site_settings`");
        while ($r = $st->fetch()) {
            if ($r['key'] === 'maintenance_mode') {
                $is_maintenance = ((int)$r['value'] === 1);
            } elseif ($r['key'] === 'marquee_announce') {
                $marquee_text = $r['value'];
            } elseif ($r['key'] === 'contact_cs_whatsapp') {
                $whatsapp_cs = "https://wa.me/" . ltrim($r['value'], '+');
            } elseif ($r['key'] === 'download_apk_link') {
                $apk_link = $r['value'];
            }
        }
    } catch (Exception $err) {}
}

if ($is_maintenance) {
    http_response_code(503);
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>NOXARA - Maintenance Mode</title>
        <style>
            body { font-family: sans-serif; background: #030712; color: #F9FAFB; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; text-align: center; }
            .box { max-width: 400px; padding: 2rem; background: #111827; border: 1px solid #374151; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.5); }
            h1 { color: #3B82F6; margin-bottom: 1rem; }
            p { color: #9CA3AF; line-height: 1.5; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>Sistem Sedang Maintenance</h1>
            <p>Sistem NOXARA sedang dalam pemeliharaan berkala untuk meningkatkan kenyamanan Anda. Silakan kembali lagi nanti atau hubungi CS via WhatsApp.</p>
        </div>
    </body>
    </html>';
    exit();
}

// 3. Simple Dynamic Route Handler
$page = $_GET['page'] ?? 'home';
$action = $_GET['action'] ?? null;
$error_message = $_SESSION['flash_error'] ?? null;
$success_message = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Simple rate limiter verification for auth actions
function check_rate_limit($pdo, $ip, $username): bool {
    if (!$pdo) return true;
    try {
        $now = time();
        $ago = $now - 60; // 1 minute limit
        $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
        $stmt->execute([$ip, $ago]);
        $count = $stmt->fetch()['attempts'];
        if ($count > 10) { // Limit 10 requests per minute
            return false;
        }
        $ins = $pdo->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, ?)");
        $ins->execute([$ip, $username, $now]);
    } catch (Exception $e) {}
    return true;
}

// 4. Handle logical form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    // Handle Login Submit
    if ($action === 'login_submit') {
        $username_or_phone = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $captcha = (int)($_POST['captcha'] ?? 0);
        $terms = isset($_POST['terms']);
        $csrf_token = $_POST['csrf_token'] ?? '';

        if (!validate_csrf_token($csrf_token)) {
            $_SESSION['flash_error'] = "Kesalahan Token CSRF.";
            redirect("?page=login");
        }

        if (!verify_captcha($captcha)) {
            $_SESSION['flash_error'] = "Jawaban Captcha tidak tepat.";
            redirect("?page=login");
        }

        if (!$terms) {
            $_SESSION['flash_error'] = "Anda wajib menyetujui Syarat & Ketentuan.";
            redirect("?page=login");
        }

        // Validate user in DB
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR phone = ? OR email = ?");
            $stmt->execute([$username_or_phone, $username_or_phone, $username_or_phone]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'frozen') {
                    $_SESSION['flash_error'] = "Akun Anda sedang dibekukan oleh Admin.";
                    redirect("?page=login");
                } elseif ($user['status'] === 'banned') {
                    $_SESSION['flash_error'] = "Akun Anda diblokir permanen.";
                    redirect("?page=login");
                }

                // Setup session
                regenerate_session_safely();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'user';
                $_SESSION['show_welcome'] = true;

                // Success loading transitional page
                redirect("?page=loading_redirect");
            } else {
                $_SESSION['flash_error'] = "Username, Nomor HP, atau Sandi Anda salah.";
                redirect("?page=login");
            }
        } else {
            // Simulator mock login for testing ease
            if ($username_or_phone === 'jaka' && $password === 'Jakakece12') {
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = 'jaka';
                $_SESSION['role'] = 'user';
                $_SESSION['show_welcome'] = true;
                redirect("?page=loading_redirect");
            } else {
                $_SESSION['flash_error'] = "Gagal login. Gunakan default: jaka / Jakakece12";
                redirect("?page=login");
            }
        }
    }

    // Handle Register Submit
    if ($action === 'register_submit') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $ref_code = trim($_POST['referral_code'] ?? '');
        $captcha = (int)($_POST['captcha'] ?? 0);
        $csrf_token = $_POST['csrf_token'] ?? '';

        if (!validate_csrf_token($csrf_token)) {
            $_SESSION['flash_error'] = "Token CSRF tidak cocok.";
            redirect("?page=register");
        }

        if (!verify_captcha($captcha)) {
            $_SESSION['flash_error'] = "Pemeriksaan Captcha Matematika Gagal.";
            redirect("?page=register");
        }

        if (strlen($username) < 4 || strlen($password) < 8) {
            $_SESSION['flash_error'] = "Sandi minimal 8 karakter, dan Username minimal 4 karakter.";
            redirect("?page=register");
        }

        if ($password !== $confirm_password) {
            $_SESSION['flash_error'] = "Ganti Sandi tidak sesuai konfirmasi.";
            redirect("?page=register");
        }

        if ($pdo) {
            try {
                // Check unique email/phone/user
                $stmt_chk = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE username = ? OR email = ? OR phone = ?");
                $stmt_chk->execute([$username, $email, $phone]);
                if ($stmt_chk->fetch()['total'] > 0) {
                    $_SESSION['flash_error'] = "Username, Email, atau Nomor HP sudah terdaftar di sistem.";
                    redirect("?page=register");
                }

                // Validate referral upline
                $upline_id = null;
                if (!empty($ref_code)) {
                    $stmt_ref = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                    $stmt_ref->execute([$ref_code]);
                    $upl = $stmt_ref->fetch();
                    if ($upl) {
                        $upline_id = $upl['id'];
                    } else {
                        $_SESSION['flash_error'] = "Kode undangan / referral tidak valid.";
                        redirect("?page=register");
                    }
                }

                // Register user
                $new_ref_code = 'NOX' . strtoupper(substr(md5($username . time()), 0, 8));
                $pass_hash = password_hash($password, PASSWORD_BCRYPT);
                
                $pdo->beginTransaction();
                
                $reg = $pdo->prepare("INSERT INTO users (username, email, phone, password, referred_by, referral_code) VALUES (?, ?, ?, ?, ?, ?)");
                $reg->execute([$username, $email, $phone, $pass_hash, $upline_id, $new_ref_code]);
                $new_id = $pdo->lastInsertId();

                // Profile card
                $prof = $pdo->prepare("INSERT INTO user_profiles (user_id, full_name, avatar_url) VALUES (?, ?, '/assets/img/avatar_default.png')");
                $prof->execute([$new_id]);

                // Create balances setup (Default Rp 15.000 Welcome Bonus, as configured)
                $bal_bonus = 15000.00;
                $bal = $pdo->prepare("INSERT INTO balance_accounts (user_id, main_balance, bonus_balance) VALUES (?, 0.00, ?)");
                $bal->execute([$new_id, $bal_bonus]);

                // Add to referral connection if upline
                if ($upline_id) {
                    $ref_con = $pdo->prepare("INSERT INTO user_referrals (user_id, referred_by) VALUES (?, ?)");
                    $ref_con->execute([$new_id, $upline_id]);
                    
                    // Recursive lookup insertions
                    $rec_ins = $pdo->prepare("INSERT INTO referral_tree (ancestor_id, descendant_id, depth) VALUES (?, ?, 1)");
                    $rec_ins->execute([$upline_id, $new_id]);
                    
                    // Pull upper ancestors depths
                    $anc_pull = $pdo->prepare("SELECT ancestor_id, depth FROM referral_tree WHERE descendant_id = ?");
                    $anc_pull->execute([$upline_id]);
                    $ancestors = $anc_pull->fetchAll();
                    
                    $ans_ins = $pdo->prepare("INSERT INTO referral_tree (ancestor_id, descendant_id, depth) VALUES (?, ?, ?)");
                    foreach ($ancestors as $a) {
                        $new_depth = (int)$a['depth'] + 1;
                        if ($new_depth <= 3) { // Maximum depth monitored is 3 Levels
                            $ans_ins->execute([$a['ancestor_id'], $new_id, $new_depth]);
                        }
                    }
                }

                // Create default VIP status cache
                $vip_ins = $pdo->prepare("INSERT INTO user_vip_status (user_id, level_number, accumulated_topup) VALUES (?, 0, 0.00)");
                $vip_ins->execute([$new_id]);

                // Record initial ledger bonus
                $led = $pdo->prepare("INSERT INTO ledger_transactions (user_id, idempotency_key, type, bonus_delta, description) VALUES (?, ?, 'bonus', ?, ?)");
                $led->execute([$new_id, "welcome_bonus_{$new_id}", 'bonus', $bal_bonus, "Bonus Pendaftaran Anggota Baru NOXARA"]);

                $pdo->commit();
                $_SESSION['flash_success'] = "Pendaftaran berhasil! Akun Anda telah dibuat. Silakan masuk.";
                redirect("?page=login");

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['flash_error'] = "Terjadi kegagalan sistem pendaftaran: " . $e->getMessage();
                redirect("?page=register");
            }
        } else {
            $_SESSION['flash_success'] = "Pendaftaran berhasil (MOCK SIMULATION)! Silakan login.";
            redirect("?page=login");
        }
    }
}

// 5. Build dynamic outputs
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NOXARA - Modern Pertambangan Cloud</title>
    <!-- Tailwind imports -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy_dark: '#030712',
                        navy_card: '#111827',
                        navy_light: '#1F2937',
                        blue_primary: '#3B82F6',
                        blue_active: '#60A5FA',
                        accent_cyan: '#06B6D4',
                        text_main: '#F9FAFB',
                        text_sub: '#9CA3AF',
                        border_line: '#374151',
                        custom_success: '#10B981',
                        custom_danger: '#EF4444',
                        custom_warning: '#F59E0B'
                    }
                }
            }
        }
    </script>
    <style>
        /* CSS resets for HP native preview look on large screens */
        body {
            background-color: #0b0f19;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            color: #F9FAFB;
        }
        .phone-container {
            width: 100%;
            max-width: 480px;
            background-color: #030712;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 0 40px rgba(0,0,0,0.8);
            border-left: 1px solid #1e293b;
            border-right: 1px solid #1e293b;
            padding-bottom: 74px; /* bottom bar space */
        }
        /* Hide scrollbar */
        ::-webkit-scrollbar {
            width: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="phone-container">
    
    <!-- HEADER -->
    <header class="p-4 bg-navy_card border-b border-border_line flex items-center justify-between sticky top-0 z-40">
        <div class="flex items-center gap-2">
            <span class="text-blue_primary font-extrabold text-2xl tracking-wider">NOXARA</span>
            <span class="text-[10px] bg-blue_primary/20 text-blue_primary px-1.5 py-0.5 rounded font-mono border border-blue_primary/30">PHP NATIVE</span>
        </div>
        
        <div class="flex items-center gap-2">
            <a href="?page=notifications" class="relative p-1 bg-navy_light rounded-lg border border-border_line">
                <!-- Lucide-style Bell SVG icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-text_main"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path></svg>
                <span class="absolute top-0 right-0 w-2 h-2 bg-custom_danger rounded-full ring-2 ring-navy_card"></span>
            </a>
            
            <a href="?page=live_chat" class="p-1 bg-navy_light rounded-lg border border-border_line">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-text_main"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </a>
        </div>
    </header>

    <!-- MAIN VIEW AREA -->
    <main class="flex-grow p-4 flex flex-col gap-4">
        
        <!-- Flash Alert system -->
        <?php if ($error_message): ?>
            <div class="p-3 bg-custom_danger/15 border border-custom_danger/30 text-custom_danger rounded-lg text-xs flex gap-2 items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span><?= esc($error_message) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="p-3 bg-custom_success/15 border border-custom_success/30 text-custom_success rounded-lg text-xs flex gap-2 items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><?= esc($success_message) ?></span>
            </div>
        <?php endif; ?>

        <?php
        // Simple Dynamic Content Inclusions / Simulated UI views in one single-entry PHP file
        switch ($page) {
            case 'login':
                $captcha_data = generate_math_captcha();
                ?>
                <div class="flex-grow flex flex-col justify-center gap-6 py-6" id="loginForm">
                    <div class="text-center">
                        <h2 class="text-3xl font-extrabold tracking-wider text-blue_primary">NOXARA</h2>
                        <p class="text-text_sub text-xs mt-1">Platform Pertambangan Aset Digital Berdaya Saing Tinggi</p>
                    </div>

                    <form action="?action=login_submit" method="POST" class="bg-navy_card p-5 rounded-xl border border-border_line flex flex-col gap-4">
                        <?= render_csrf_input() ?>
                        
                        <div>
                            <label class="block text-xs text-text_sub mb-1">Username / Phone / Email</label>
                            <input type="text" name="username" required placeholder="Contoh: jaka" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main focus:outline-none focus:ring-1 focus:ring-blue_primary">
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs text-text_sub">Kata Sandi</label>
                                <a href="?page=forgot_password" class="text-[11px] text-blue_primary hover:underline">Lupa Sandi PIN?</a>
                            </div>
                            <input type="password" name="password" required placeholder="Masukkan kata sandi" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main focus:outline-none focus:ring-1 focus:ring-blue_primary">
                        </div>

                        <!-- Captcha wrapper -->
                        <div class="bg-navy_dark p-3 rounded-lg border border-border_line flex justify-between items-center">
                            <span class="text-sm font-mono text-blue_active"><?= $captcha_data['question'] ?></span>
                            <input type="number" name="captcha" required placeholder="Hasil" class="w-20 bg-navy_card border border-border_line rounded-md px-2 py-1 text-center text-sm text-text_main focus:ring-1 focus:ring-blue_primary">
                        </div>

                        <div class="flex items-start gap-2">
                            <input type="checkbox" name="terms" id="terms_agree" required class="mt-1 rounded border-border_line bg-navy_dark text-blue_primary focus:ring-0">
                            <label for="terms_agree" class="text-[11px] text-text_sub leading-tight">Saya memahami dan menyetujui seluruh <a href="?page=information&cat=tos" class="text-blue_primary hover:underline">Syarat Layanan</a> dan <a href="?page=information&cat=privacy" class="text-blue_primary hover:underline">Ketentuan Privasi</a> NOXARA.</label>
                        </div>

                        <button type="submit" class="w-full bg-blue_primary hover:bg-blue_active text-text_main font-semibold py-2.5 rounded-lg text-sm transition mt-2">Masuk Sekarang</button>
                    </form>

                    <p class="text-center text-xs text-text_sub">Belum memiliki akun? <a href="?page=register" class="text-blue_primary font-bold hover:underline">Daftar Baru</a></p>

                    <!-- Platform statistic counters -->
                    <div class="bg-navy_card p-4 rounded-xl border border-border_line flex flex-col gap-3 mt-4">
                        <h4 class="text-xs text-text_sub font-bold uppercase tracking-wider text-center border-b border-border_line pb-2">Status Operasional NOXARA</h4>
                        <div class="grid grid-cols-2 gap-3 text-center">
                            <div class="bg-navy_dark/50 p-2 rounded border border-border_line">
                                <span class="text-[10px] text-text_sub block">Anggota Bergabung</span>
                                <span class="text-sm font-bold text-blue_active">5.849+ User</span>
                            </div>
                            <div class="bg-navy_dark/50 p-2 rounded border border-border_line">
                                <span class="text-[10px] text-text_sub block">Hari Operasional</span>
                                <span class="text-sm font-bold text-blue_active">358 Hari</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                break;

            case 'register':
                $captcha_data = generate_math_captcha();
                ?>
                <div class="flex-grow flex flex-col justify-center gap-6 py-4">
                    <div class="text-center">
                        <h2 class="text-3xl font-extrabold tracking-wider text-blue_primary">BUAT AKUN</h2>
                        <p class="text-text_sub text-xs mt-1">Dapatkan modal pendaftaran gratis Rp 15.000!</p>
                    </div>

                    <form action="?action=register_submit" method="POST" class="bg-navy_card p-5 rounded-xl border border-border_line flex flex-col gap-3.5">
                        <?= render_csrf_input() ?>
                        
                        <div>
                            <label class="block text-xs text-text_sub mb-1">Nama Pengguna (Username)</label>
                            <input type="text" name="username" required placeholder="Min 4 huruf, tanpa spasi" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main focus:outline-none focus:ring-1 focus:ring-blue_primary">
                        </div>

                        <div>
                            <label class="block text-xs text-text_sub mb-1">Alamat Email</label>
                            <input type="email" name="email" required placeholder="Contoh: user@email.com" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main focus:outline-none focus:ring-1 focus:ring-blue_primary">
                        </div>

                        <div>
                            <label class="block text-xs text-text_sub mb-1">Nomor WhatsApp Aktif</label>
                            <input type="text" name="phone" required placeholder="Contoh: 08123456789" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main focus:outline-none focus:ring-1 focus:ring-blue_primary">
                        </div>

                        <div>
                            <label class="block text-xs text-text_sub mb-1">Kata Sandi Akun</label>
                            <input type="password" name="password" required placeholder="Min 8 karakter" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main focus:outline-none focus:ring-1 focus:ring-blue_primary">
                        </div>

                        <div>
                            <label class="block text-xs text-text_sub mb-1">Ulangi Kata Sandi</label>
                            <input type="password" name="confirm_password" required placeholder="Ulangi sandi baru" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main focus:outline-none focus:ring-1 focus:ring-blue_primary">
                        </div>

                        <div>
                            <label class="block text-xs text-text_sub mb-1">Kode Undangan / Referral (Opsional)</label>
                            <input type="text" name="referral_code" placeholder="Contoh: JAKAFREE" value="<?= htmlspecialchars($_GET['ref'] ?? '') ?>" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main focus:outline-none focus:ring-1 focus:ring-blue_primary">
                        </div>

                        <!-- Captcha wrapper -->
                        <div class="bg-navy_dark p-3 rounded-lg border border-border_line flex justify-between items-center">
                            <span class="text-sm font-mono text-blue_active"><?= $captcha_data['question'] ?></span>
                            <input type="number" name="captcha" required placeholder="Hasil" class="w-20 bg-navy_card border border-border_line rounded-md px-2 py-1 text-center text-sm text-text_main focus:ring-1 focus:ring-blue_primary">
                        </div>

                        <button type="submit" class="w-full bg-blue_primary hover:bg-blue_active text-text_main font-semibold py-2.5 rounded-lg text-sm transition mt-2">Daftar Akun Baru</button>
                    </form>

                    <p class="text-center text-xs text-text_sub">Sudah terdaftar? <a href="?page=login" class="text-blue_primary font-bold hover:underline">Masuk Aplikasi</a></p>
                </div>
                <?php
                break;

            case 'forgot_password':
                ?>
                <div class="bg-navy_card p-5 rounded-xl border border-border_line text-center flex flex-col gap-4 my-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-custom_warning mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <h3 class="text-lg font-bold">Lupa PIN / Password?</h3>
                    <p class="text-xs text-text_sub leading-relaxed">Demi alasan privasi dan keamanan sistem perbankan ledger NOXARA, proses pemulihan kata sandi dilakukan secara terverifikasi melalui petugas Customer Service kami di WhatsApp.</p>
                    <a href="<?= $whatsapp_cs ?>" target="_blank" class="w-full bg-custom_success text-text_main py-2.5 rounded-lg font-semibold text-sm hover:opacity-90 flex justify-center items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-text_main"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        Hubungi CS WhatsApp Resmi
                    </a>
                </div>
                <?php
                break;

            case 'loading_redirect':
                // Loading screen before home
                ?>
                <div class="flex-grow flex flex-col items-center justify-center gap-4 py-16 text-center">
                    <div class="relative w-16 h-16">
                        <div class="absolute inset-0 rounded-full border-4 border-blue_primary/20"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-t-blue_primary animate-spin"></div>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-blue_primary">Menyiapkan akun kamu...</h4>
                        <p class="text-xs text-text_sub mt-1">Mengautentikasi kunci enkripsi data ledger</p>
                    </div>
                    <script>
                        setTimeout(() => {
                            window.location.href = "?page=home";
                        }, 1500);
                    </script>
                </div>
                <?php
                break;

            case 'home':
            default:
                // Direct User Dashboard
                ?>
                <!-- Welcome Promo Popup Modal (Controlled locally or by DB session flags) -->
                <?php if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] === true): ?>
                    <?php $_SESSION['show_welcome'] = false; // display only once ?>
                    <div id="welcome_popup" class="fixed inset-0 bg-black/85 flex items-center justify-center p-6 z-50 animate-fade-in">
                        <div class="bg-navy_card border border-border_line rounded-xl overflow-hidden w-full max-w-sm flex flex-col shadow-2xl">
                            <div class="p-4 bg-blue_primary/10 border-b border-border_line flex justify-between items-center">
                                <span class="font-bold text-sm text-blue_primary">Selamat datang di Platform NOXARA</span>
                                <button onclick="document.getElementById('welcome_popup').remove()" class="text-text_sub hover:text-text_main">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                </button>
                            </div>
                            <div class="p-5 flex flex-col gap-4 text-center">
                                <div class="w-full h-32 bg-navy_dark rounded-lg flex items-center justify-center border border-border_line">
                                    <span class="text-xs text-text_sub font-mono">Welcome Image Asset</span>
                                </div>
                                <p class="text-xs text-text_sub leading-relaxed">Akses Mesin Pertambangan Aset Digital dengan penarikan dana instan dan kemudahan deposit otomatis QRIS terintegrasi penuh.</p>
                                <a href="https://chat.whatsapp.com/JakaKeceCommunity" target="_blank" class="w-full bg-custom_success text-text_main font-medium py-2 rounded-lg text-sm flex justify-center items-center gap-2 hover:opacity-90">
                                    <!-- Simple Whatsapp custom SVG -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                    Gabung WhatsApp Group Resmi
                                </a>
                                <button onclick="document.getElementById('welcome_popup').remove()" class="w-full bg-navy_light border border-border_line hover:bg-border_line text-text_sub py-1.5 rounded-lg text-xs mt-1">Saya mengerti, tutup</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Marquee promotional banner -->
                <div class="bg-blue_primary/15 border border-blue_primary/25 rounded-lg p-2 overflow-hidden flex items-center gap-2">
                    <span class="bg-blue_primary text-text_main text-[9px] px-1 rounded uppercase font-bold animate-pulse">BERITA</span>
                    <marquee class="text-xs text-blue_active tracking-wide" scrollamount="4"><?= htmlspecialchars($marquee_text) ?></marquee>
                </div>

                <!-- USER BALANCE PANEL -->
                <div class="bg-navy_card border border-border_line rounded-xl p-5 flex flex-col gap-4 relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-blue_primary/5 rounded-full blur-xl translate-x-6 -translate-y-6"></div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 bg-blue_primary/20 rounded-full flex items-center justify-center text-blue_active border border-blue_primary/30">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </div>
                            <div>
                                <span class="text-[11px] text-text_sub block">Halo, Anggota Mulia</span>
                                <span class="text-sm font-bold text-text_main">jaka <span id="user_vip_badge" class="text-[10px] bg-blue_primary/20 text-blue_active px-1 rounded ml-1 border border-blue_primary/40 font-bold uppercase">VIP 0</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Balance breakdown -->
                    <div class="grid grid-cols-2 gap-4 border-t border-border_line pt-4">
                        <div>
                            <span class="text-[10px] text-text_sub block uppercase tracking-wider">Saldo Utama (Dapat Tarik)</span>
                            <span id="user_main_balance" class="text-lg font-extrabold text-blue_primary">Rp 0</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-text_sub block uppercase tracking-wider text-right">Saldo Bonus (Belanja)</span>
                            <span id="user_bonus_balance" class="text-lg font-extrabold text-blue_active text-right block">Rp 15.000</span>
                        </div>
                    </div>

                    <!-- Deposit and Withdraw Quick Buttons -->
                    <div class="grid grid-cols-2 gap-3 mt-1">
                        <a href="?page=deposit" class="w-full bg-blue_primary hover:bg-blue_active text-text_main py-2 rounded-lg font-semibold text-xs flex justify-center items-center gap-1.5 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-text_main"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Isi Ulang (Deposit)
                        </a>
                        <a href="?page=withdraw" class="w-full bg-navy_light border border-border_line hover:bg-border_line text-text_main py-2 rounded-lg font-semibold text-xs flex justify-center items-center gap-1.5 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-text_main"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Tarik Tunai (Withdraw)
                        </a>
                    </div>
                </div>

                <!-- SLIDER BANNER -->
                <div class="relative w-full h-32 bg-navy_card rounded-xl border border-border_line overflow-hidden flex items-center justify-center">
                    <span class="text-xs text-text_sub font-mono">Slide Promo Banner [1, 2, 3]</span>
                    <div class="absolute bottom-2 inset-x-0 flex justify-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue_primary"></span>
                        <span class="w-2 h-2 rounded-full bg-border_line"></span>
                        <span class="w-2 h-2 rounded-full bg-border_line"></span>
                    </div>
                </div>

                <!-- 8 GRID FEATURES MENUS -->
                <div class="grid grid-cols-4 gap-3 bg-navy_card p-4 rounded-xl border border-border_line text-center">
                    <a href="?page=vip" class="flex flex-col items-center gap-1 hover:opacity-85">
                        <div class="w-10 h-10 bg-yellow-500/10 border border-yellow-500/20 text-yellow-500 rounded-lg flex items-center justify-center">
                            <!-- Star Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                        <span class="text-[10px] text-text_sub">Tingkat VIP</span>
                    </a>

                    <a href="?page=voucher" class="flex flex-col items-center gap-1 hover:opacity-85">
                        <div class="w-10 h-10 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-lg flex items-center justify-center">
                            <!-- Tag icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                        </div>
                        <span class="text-[10px] text-text_sub">Klaim Kupon</span>
                    </a>

                    <a href="?page=games" class="flex flex-col items-center gap-1 hover:opacity-85">
                        <div class="w-10 h-10 bg-purple-500/10 border border-purple-500/20 text-purple-400 rounded-lg flex items-center justify-center">
                            <!-- Game Controller -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="12" x2="10" y2="12"></line><line x1="8" y1="10" x2="8" y2="14"></line><line x1="15" y1="13" x2="15.01" y2="13"></line><line x1="18" y1="11" x2="18.01" y2="11"></line><rect x="2" y="6" width="20" height="12" rx="3"></rect></svg>
                        </div>
                        <span class="text-[10px] text-text_sub">Roda Game</span>
                    </a>

                    <a href="?page=daily_bonus" class="flex flex-col items-center gap-1 hover:opacity-85">
                        <div class="w-10 h-10 bg-pink-500/10 border border-pink-500/20 text-pink-400 rounded-lg flex items-center justify-center">
                            <!-- Calendar Claim -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <span class="text-[10px] text-text_sub">Bonus Harian</span>
                    </a>

                    <a href="<?= $whatsapp_cs ?>" target="_blank" class="flex flex-col items-center gap-1 hover:opacity-85">
                        <div class="w-10 h-10 bg-green-500/10 border border-green-500/20 text-green-400 rounded-lg flex items-center justify-center">
                            <!-- Helpline -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        </div>
                        <span class="text-[10px] text-text_sub">Helpline Wa</span>
                    </a>

                    <a href="?page=information" class="flex flex-col items-center gap-1 hover:opacity-85">
                        <div class="w-10 h-10 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-lg flex items-center justify-center">
                            <!-- Info book icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        </div>
                        <span class="text-[10px] text-text_sub">Panduan APP</span>
                    </a>

                    <a href="?page=download_screen" class="flex flex-col items-center gap-1 hover:opacity-85">
                        <div class="w-10 h-10 bg-red-500/10 border border-red-500/20 text-red-400 rounded-lg flex items-center justify-center">
                            <!-- Download App -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 15v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        </div>
                        <span class="text-[10px] text-text_sub">Unduh File</span>
                    </a>

                    <a href="?page=promo_events" class="flex flex-col items-center gap-1 hover:opacity-85">
                        <div class="w-10 h-10 bg-orange-500/10 border border-orange-500/20 text-orange-400 rounded-lg flex items-center justify-center">
                            <!-- Promos -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        </div>
                        <span class="text-[10px] text-text_sub">Promo Event</span>
                    </a>
                </div>

                <!-- PLATFORM TOTAL NUMBERS COUNCILS -->
                <div class="bg-navy_card border border-border_line rounded-xl p-4 flex flex-col gap-3">
                    <span class="text-xs uppercase tracking-wider text-blue_primary font-extrabold flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        Eksplorasi Statistik Platform
                    </span>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="bg-navy_dark/40 border border-border_line p-2.5 rounded-lg">
                            <span class="text-[10px] text-text_sub block">Total Isi Ulang Anggota</span>
                            <span class="text-xs font-bold text-text_main block mt-0.5">Rp 1.420.750.000</span>
                        </div>
                        <div class="bg-navy_dark/40 border border-border_line p-2.5 rounded-lg">
                            <span class="text-[10px] text-text_sub block">Penarikan Sukses Terbayar</span>
                            <span class="text-xs font-bold text-text_main block mt-0.5">Rp 984.524.000</span>
                        </div>
                    </div>
                </div>

                <!-- RECENT ACTIVITIES AND REMINDERS -->
                <div class="bg-navy_card border border-border_line rounded-xl p-4 flex flex-col gap-3">
                    <span class="text-xs uppercase tracking-wider text-blue_active font-bold">Aktivitas Terbaru</span>
                    <div id="recent_activities_list" class="text-center py-4 text-xs text-text_sub">
                        Belum ada aktivitas terbaru.
                    </div>
                </div>
                <?php
                break;
                
            case 'vip':
                ?>
                <div class="flex flex-col gap-4">
                    <div class="text-center border-b border-border_line pb-2 mb-2">
                        <h3 class="text-lg font-bold text-blue_primary">Skema Keanggotaan VIP</h3>
                        <p class="text-xs text-text_sub">Tingkatkan total isi ulang sukses Anda untuk naik tingkat otomatis</p>
                    </div>
                    <div class="flex flex-col gap-3">
                        <div class="bg-navy_card p-4 rounded-xl border border-border_line flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-sm text-yellow-500">VIP 0 Member (Default)</h4>
                                <span class="text-xs text-text_sub">Total Depo: Rp 0 - Rp 49.999</span>
                            </div>
                            <span class="text-xs font-bold text-custom_danger">Biaya WD: 10%</span>
                        </div>
                        <div class="bg-navy_card p-4 rounded-xl border border-border_line flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-sm text-yellow-400">VIP 1 Bronze</h4>
                                <span class="text-xs text-text_sub">Min Depo: Rp 50.000</span>
                            </div>
                            <span class="text-xs font-bold text-blue_active">Biaya WD: 5%</span>
                        </div>
                        <div class="bg-navy_card p-4 rounded-xl border border-border_line flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-sm text-gray-300">VIP 2 Silver</h4>
                                <span class="text-xs text-text_sub">Min Depo: Rp 100.000</span>
                            </div>
                            <span class="text-xs font-bold text-blue_active">Biaya WD: 2.5%</span>
                        </div>
                        <div class="bg-navy_card p-4 rounded-xl border border-border_line flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-sm text-yellow-300">VIP 3 Gold</h4>
                                <span class="text-xs text-text_sub">Min Depo: Rp 1.000.000</span>
                            </div>
                            <span class="text-xs font-bold text-custom_success">Biaya WD: 0%</span>
                        </div>
                    </div>
                </div>
                <?php
                break;

            case 'products':
                ?>
                <div class="flex flex-col gap-4 text-left">
                    <div class="text-center">
                        <span class="text-xs text-blue_active font-bold uppercase tracking-widest block mb-1">PRODUK SEWA KELAS DUNIA</span>
                        <h3 class="text-lg font-black text-blue_primary">Cloud Mining Hardware</h3>
                        <p class="text-[11px] text-text_sub mt-0.5">Sewa daya komputasi lepas pantai, dapatkan pendapatan harian instan.</p>
                    </div>

                    <div class="flex flex-col gap-3.5">
                        
                        <!-- Product Ordinary -->
                        <div class="bg-navy_card border border-border_line p-4 rounded-xl flex flex-col gap-3.5 relative overflow-hidden">
                            <span class="absolute top-0 right-0 bg-blue_primary/20 text-blue_active text-[8px] font-mono font-bold uppercase tracking-wider px-2 py-1 rounded-bl">Lite Tier</span>
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 bg-blue_primary/10 border border-blue_primary/20 rounded-lg flex items-center justify-center text-blue_primary shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                                </div>
                                <div class="flex-grow">
                                    <h4 class="text-sm font-black text-text_main leading-snug">Ordinary Miner Node 1.0</h4>
                                    <span class="text-[10px] text-text_sub">Sewa komputasi kilat hemat daya</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 py-2 border-y border-border_line/60 text-center font-mono text-[10px]">
                                <div>
                                    <span class="text-text_sub block text-[9px] uppercase">Harga Sewa</span>
                                    <span class="text-blue_active font-extrabold text-xs block">Rp 50.000</span>
                                </div>
                                <div>
                                    <span class="text-text_sub block text-[9px] uppercase">Hasil Harian</span>
                                    <span class="text-custom_success font-extrabold text-xs block">Rp 2.000</span>
                                </div>
                                <div>
                                    <span class="text-text_sub block text-[9px] uppercase">Durasi Aktif</span>
                                    <span class="text-text_main font-extrabold text-xs block">30 Hari</span>
                                </div>
                            </div>
                            <button onclick="rentPhpMiner('MIN-01', 'Ordinary Miner 1.0', 50000, 2000)" class="w-full bg-blue_primary hover:bg-blue_active text-text_main py-2 rounded-lg font-bold text-xs shadow-lg transition">Sewa Node Sekarang</button>
                        </div>

                        <!-- Product Medium -->
                        <div class="bg-navy_card border border-border_line p-4 rounded-xl flex flex-col gap-3.5 relative overflow-hidden">
                            <span class="absolute top-0 right-0 bg-yellow-500/10 text-yellow-500 text-[8px] font-mono font-bold uppercase tracking-wider px-2 py-1 rounded-bl">Premium Tier</span>
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 bg-yellow-500/10 border border-yellow-500/20 rounded-lg flex items-center justify-center text-yellow-500 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"></path><path d="M2 17l10 5 10-5"></path><path d="M2 12l10 5 10-5"></path></svg>
                                </div>
                                <div class="flex-grow">
                                    <h4 class="text-sm font-black text-text_main leading-snug">Medium Power Grid 2.0</h4>
                                    <span class="text-[10px] text-text_sub">Daya hash rate tinggi stabil tingkat lanjut</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 py-2 border-y border-border_line/60 text-center font-mono text-[10px]">
                                <div>
                                    <span class="text-text_sub block text-[9px] uppercase">Harga Sewa</span>
                                    <span class="text-yellow-500 font-extrabold text-xs block">Rp 200.000</span>
                                </div>
                                <div>
                                    <span class="text-text_sub block text-[9px] uppercase">Hasil Harian</span>
                                    <span class="text-custom_success font-extrabold text-xs block">Rp 9.000</span>
                                </div>
                                <div>
                                    <span class="text-text_sub block text-[9px] uppercase">Durasi Aktif</span>
                                    <span class="text-text_main font-extrabold text-xs block">30 Hari</span>
                                </div>
                            </div>
                            <button onclick="rentPhpMiner('MIN-02', 'Medium Power Grid 2.0', 200000, 9000)" class="w-full bg-yellow-600 hover:bg-yellow-500 text-text_main py-2 rounded-lg font-bold text-xs shadow-lg transition">Sewa Node Sekarang</button>
                        </div>

                        <!-- Product High -->
                        <div class="bg-navy_card border border-border_line p-4 rounded-xl flex flex-col gap-3.5 relative overflow-hidden">
                            <span class="absolute top-0 right-0 bg-red-500/15 text-red-400 text-[8px] font-mono font-bold uppercase tracking-wider px-2 py-1 rounded-bl">Enterprise Tier</span>
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 bg-red-500/10 border border-red-500/20 rounded-lg flex items-center justify-center text-red-400 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2 center"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                                </div>
                                <div class="flex-grow">
                                    <h4 class="text-sm font-black text-text_main leading-snug">Enterprise Core Node 3.0</h4>
                                    <span class="text-[10px] text-text_sub">Super komputasi paralel profit maksimal</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 py-2 border-y border-border_line/60 text-center font-mono text-[10px]">
                                <div>
                                    <span class="text-text_sub block text-[9px] uppercase">Harga Sewa</span>
                                    <span class="text-red-400 font-extrabold text-xs block">Rp 1.000.000</span>
                                </div>
                                <div>
                                    <span class="text-text_sub block text-[9px] uppercase">Hasil Harian</span>
                                    <span class="text-custom_success font-extrabold text-xs block">Rp 50.000</span>
                                </div>
                                <div>
                                    <span class="text-text_sub block text-[9px] uppercase">Durasi Aktif</span>
                                    <span class="text-text_main font-extrabold text-xs block">30 Hari</span>
                                </div>
                            </div>
                            <button onclick="rentPhpMiner('MIN-03', 'Enterprise Core 3.0', 1000000, 50000)" class="w-full bg-red-600 hover:bg-red-500 text-text_main py-2 rounded-lg font-bold text-xs shadow-lg transition">Sewa Node Sekarang</button>
                        </div>

                    </div>
                </div>

                <script>
                    function rentPhpMiner(code, name, price, profit) {
                        const balance = parseInt(localStorage.getItem('member_main_balance') || '0');
                        if (balance < price) {
                            playSystemBeep(320, 0.4);
                            alert("Saldo utama Anda (Rp " + balance.toLocaleString('id-ID') + ") tidak mencukupi untuk menyewa " + name + " seharga Rp " + price.toLocaleString('id-ID') + "! Mohon lakukan pengisian saldo (Deposit) terlebih dahulu.");
                            window.location.href = '?page=deposit';
                            return;
                        }

                        // Deduct balance
                        const newBal = balance - price;
                        localStorage.setItem('member_main_balance', String(newBal));

                        // Add to miners storage
                        let miners = [];
                        try {
                            miners = JSON.parse(localStorage.getItem('member_miners') || '[]');
                        } catch(e) { miners = []; }

                        miners.push({
                            id: 'UID-' + Date.now(),
                            code: code,
                            name: name,
                            price: price,
                            profit: profit,
                            rentAt: Date.now(),
                            lastClaim: Date.now(),
                            accumulated: 0
                        });
                        localStorage.setItem('member_miners', JSON.stringify(miners));

                        // Append ledger transaction
                        let ledgers = [];
                        try {
                            ledgers = JSON.parse(localStorage.getItem('member_ledger') || '[]');
                        } catch(e) { ledgers = []; }

                        const now = new Date();
                        const dateStr = now.toLocaleDateString('id-ID') + ' ' + now.toLocaleTimeString('id-ID');
                        
                        ledgers.unshift({
                            id: 'TX-' + Date.now(),
                            desc: 'Sewa ' + name + ' Sukses',
                            date: dateStr,
                            amount: price,
                            type: 'KEDELUWARSA' // DEBIT / EXPENDITURE label
                        });
                        localStorage.setItem('member_ledger', JSON.stringify(ledgers));

                        playSystemBeep(520, 0.1);
                        setTimeout(() => playSystemBeep(650, 0.1), 100);
                        setTimeout(() => playSystemBeep(780, 0.25), 200);

                        alert("Penyewaan " + name + " berhasil! Server sedang menyelaraskan ledger komputasi awan. Anda dapat memantau mesin Anda di menu Mining.");
                        window.location.href = '?page=mining';
                    }
                </script>
                <?php
                break;

            case 'mining':
                ?>
                <div class="flex flex-col gap-4 text-left">
                    <div class="bg-gradient-to-br from-navy_card to-navy_light border border-border_line rounded-xl p-5 text-center relative overflow-hidden flex flex-col items-center justify-center py-6">
                        <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(59,130,246,0.15),rgba(255,255,255,0))]"></div>
                        
                        <!-- Pulse Glowing 3D-Like Miner Sphere -->
                        <div class="relative w-16 h-16 bg-blue_primary/15 border border-blue_primary/30 rounded-full flex items-center justify-center text-blue_primary shadow-[0_0_20px_rgba(59,130,246,0.2)] animate-pulse mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin duration-3000"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                        </div>

                        <h3 class="text-sm font-black text-text_main tracking-wider uppercase">Pusat Rig Pertambangan</h3>
                        <p class="text-[10px] text-text_sub mt-1 leading-snug">Daya rigs cloud global server sedang aktif. Hasil komputasi diperbarui secara waktu-nyata.</p>
                    </div>

                    <h4 class="text-xs font-bold text-text_sub uppercase tracking-wider pl-1">Mesin Sewa Anda</h4>
                    <div id="php_owned_miners_list" class="flex flex-col gap-3">
                        <!-- POPULATED DYNAMICALLY -->
                        <div class="text-center py-8 bg-navy_card/40 border border-dashed border-border_line rounded-xl text-xs text-text_sub">
                            Mencari koneksi mesin hardware...
                        </div>
                    </div>
                </div>

                <script>
                    function renderPhpMinersArea() {
                        const target = document.getElementById('php_owned_miners_list');
                        if (!target) return;

                        let miners = [];
                        try {
                            miners = JSON.parse(localStorage.getItem('member_miners') || '[]');
                        } catch(e) {}

                        if (miners.length === 0) {
                            target.innerHTML = `
                                <div class="bg-navy_card p-6 rounded-xl border border-border_line text-center flex flex-col items-center gap-3 py-8">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-text_sub"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                    <span class="text-xs text-text_sub">Anda belum memiliki Rig Pertambangan yang aktif saat ini.</span>
                                    <a href="?page=products" class="mt-2 text-xs bg-blue_primary hover:bg-blue_active text-text_main px-4 py-2 rounded-lg font-bold shadow-lg transition">Mulai Sewa Node</a>
                                </div>
                            `;
                            return;
                        }

                        let html = '';
                        miners.forEach((m, idx) => {
                            // Calculate profits accumulated (simulated 1 coin per 2 seconds for high activity testing)
                            const elapsedSec = Math.floor((Date.now() - m.lastClaim) / 1000);
                            const dailyRate = m.profit;
                            // Rate per sec
                            const ratePerSec = dailyRate / 86400; 
                            // Give them speeded up mock multiplier for high-quality sandbox verification! 
                            // Let's multiply mock accumulation rate by 30 so they see money tick up live inside browser!
                            const mockAccrued = Math.floor(elapsedSec * ratePerSec * 1500); 
                            const claimable = Math.max(0, mockAccrued);

                            html += `
                                <div class="bg-navy_card border border-border_line p-4 rounded-xl flex flex-col gap-3 relative">
                                    <span class="absolute top-3 right-3 text-[9px] text-custom_success font-mono flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 bg-custom_success rounded-full animate-ping"></span>
                                        BERSELARAS
                                    </span>
                                    
                                    <div>
                                        <h4 class="text-xs font-black text-text_main block">${m.name}</h4>
                                        <span class="text-[9px] text-text_sub font-mono">ID Alat: ${m.id}</span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 bg-navy_dark/55 p-3 rounded-lg border border-border_line/65 text-left">
                                        <div>
                                            <span class="text-[8px] text-text_sub block uppercase">Tingkat Profit</span>
                                            <span class="text-[11px] font-bold text-blue_active">+Rp ${dailyRate.toLocaleString('id-ID')} / Hari</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-[8px] text-text_sub block uppercase">Profit Terkumpul</span>
                                            <span class="text-[11px] font-black text-custom_success font-mono">Rp ${claimable.toLocaleString('id-ID')}</span>
                                        </div>
                                    </div>

                                    <button onclick="claimPhpMinerRewards('${m.id}', ${claimable})" class="w-full ${claimable > 0 ? 'bg-custom_success hover:opacity-90' : 'bg-navy_light opacity-60 pointer-events-none'} py-2 rounded-lg text-[10px] font-bold tracking-wider uppercase transition text-text_main shadow-[0_4px_10px_rgba(16,185,129,0.1)]">
                                        Ambil Profit Instan
                                    </button>
                                </div>
                            `;
                        });
                        target.innerHTML = html;
                    }

                    function claimPhpMinerRewards(id, amount) {
                        if (amount <= 0) {
                            alert("Belum ada profit yang terkumpul! Tunggu beberapa detik agar daya hash rig menghasilkan saldo.");
                            return;
                        }

                        let miners = [];
                        try {
                            miners = JSON.parse(localStorage.getItem('member_miners') || '[]');
                        } catch(e) {}

                        const minerIdx = miners.findIndex(m => m.id === id);
                        if (minerIdx === -1) return;

                        // Reset claim clock
                        miners[minerIdx].lastClaim = Date.now();
                        localStorage.setItem('member_miners', JSON.stringify(miners));

                        // Topup main balance
                        const currentBal = parseInt(localStorage.getItem('member_main_balance') || '0');
                        localStorage.setItem('member_main_balance', String(currentBal + amount));

                        // Add transaction record
                        let ledgers = [];
                        try {
                            ledgers = JSON.parse(localStorage.getItem('member_ledger') || '[]');
                        } catch(e) {}

                        const now = new Date();
                        const dateStr = now.toLocaleDateString('id-ID') + ' ' + now.toLocaleTimeString('id-ID');

                        ledgers.unshift({
                            id: 'TX-' + Date.now(),
                            desc: 'Claim Hasil ' + miners[minerIdx].name,
                            date: dateStr,
                            amount: amount,
                            type: 'DEPOSIT' // INCOME
                        });
                        localStorage.setItem('member_ledger', JSON.stringify(ledgers));

                        playSystemBeep(520, 0.08);
                        setTimeout(() => playSystemBeep(780, 0.18), 85);

                        alert("Berhasil mengambil profit sebesar Rp " + amount.toLocaleString('id-ID') + " ke saldo utama Anda!");
                        renderPhpMinersArea();
                    }

                    // Keep rendering live tick-up updates every 1.5 seconds!
                    setInterval(renderPhpMinersArea, 1500);
                    // Initial render immediately
                    document.addEventListener('DOMContentLoaded', renderPhpMinersArea);
                </script>
                <?php
                break;

            case 'voucher':
                ?>
                <div class="flex flex-col gap-4">
                    <div class="text-center">
                        <h3 class="text-lg font-bold text-blue_primary">Klaim Kode Voucher</h3>
                        <p class="text-xs text-text_sub">Tukarkan kode unik Anda untuk bonus saldo instan</p>
                    </div>
                    <form onsubmit="alert('Simulasi Klaim: Berlaku untuk VIP1+. Tingkatkan level VIP Anda untuk klaim.'); return false;" class="bg-navy_card p-4 rounded-xl border border-border_line flex flex-col gap-3">
                        <input type="text" placeholder="Masukkan kode kupon (contoh: BONUSTOPUP)" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main focus:outline-none focus:ring-1 focus:ring-blue_primary">
                        <button type="submit" class="w-full bg-blue_primary hover:bg-blue_active text-text_main py-2 rounded-lg font-bold text-sm">Gunakan Voucher</button>
                    </form>
                </div>
                <?php
                break;
                
            case 'games':
                ?>
                <div class="flex flex-col gap-4 text-center">
                    <h3 class="text-lg font-bold text-blue_primary">VIP Reward Wheel & Games</h3>
                    <p class="text-xs text-text_sub">Mainkan mini game harian gratis tanpa memotong saldo akun</p>
                    
                    <div class="bg-navy_card p-5 rounded-xl border border-border_line text-center flex flex-col gap-4">
                        <div class="w-16 h-16 bg-purple-500/10 rounded-full flex items-center justify-center mx-auto text-purple-400 border border-purple-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                        <h4 class="font-bold text-sm">Akses Terkunci</h4>
                        <p class="text-xs text-text_sub">Mini games hanya dapat diakses oleh tingkat VIP 1 ke atas. Silakan lakukan isi ulang minimal Rp50.000 untuk naik level otomatis dan mengaktifkan fitur game ini.</p>
                        <a href="?page=deposit" class="bg-blue_primary py-2 rounded-lg text-xs font-semibold text-text_main hover:bg-blue_active">Isi Ulang Sekarang</a>
                    </div>
                </div>
                <?php
                break;
                
            case 'daily_bonus':
                ?>
                <div class="flex flex-col gap-4 text-center">
                    <h3 class="text-lg font-bold text-blue_primary font-mono">Kalender Klaim Harian</h3>
                    <p class="text-xs text-text_sub">Klaim bonus Anda berurutan selama 7 hari berturut-turut</p>
                    
                    <div class="grid grid-cols-4 gap-2.5 my-3">
                        <?php for ($day = 1; $day <= 7; $day++): ?>
                            <div class="bg-navy_card border border-border_line p-3 rounded-lg flex flex-col gap-1 items-center justify-center">
                                <span class="text-[10px] text-text_sub">Hari <?= $day ?></span>
                                <span class="text-xs font-bold text-blue_active">Rp <?= number_format($day * 1500, 0, ',', '.') ?></span>
                                <button onclick="alert('Klaim Sukses! Saldo bonus ditambahkan.')" class="mt-1 bg-blue_primary/15 hover:bg-blue_primary text-blue_active hover:text-text_main text-[8px] py-0.5 px-2 rounded-full border border-blue_primary/35 transition">Klaim</button>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php
                break;
                
            case 'download_screen':
                ?>
                <div class="bg-navy_card p-5 rounded-xl border border-border_line text-center flex flex-col gap-4 my-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-blue_primary mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <h3 class="text-lg font-extrabold text-text_main">Unduh Aplikasi Android APK</h3>
                    <p class="text-xs text-text_sub leading-relaxed">Nikmati akses yang lebih cepat dan lancar dengan mengunduh aplikasi mobile client yang dapat Anda pasang langsung di HP Android Anda.</p>
                    <a href="<?= htmlspecialchars($apk_link) ?>" class="w-full bg-blue_primary text-text_main py-2.5 rounded-lg font-bold text-sm hover:bg-blue_active">
                        Mulai Download APK (12.4 MB)
                    </a>
                </div>
                <?php
                break;

            case 'promo_events':
                ?>
                <div class="flex flex-col gap-4">
                    <h3 class="text-lg font-bold text-blue_primary text-center">Promo Event NOXARA</h3>
                    <div class="bg-navy_card border border-border_line rounded-xl overflow-hidden">
                        <div class="h-24 bg-gradient-to-r from-blue_primary/30 to-accent_cyan/20 flex items-center justify-center">
                            <span class="text-xs font-bold text-blue_active uppercase tracking-wider">Promo Referral Spektakuler</span>
                        </div>
                        <div class="p-4 flex flex-col gap-2">
                            <h4 class="font-bold text-sm text-text_main">Dapatkan Komisi Hingga 3 Tingkat</h4>
                            <p class="text-xs text-text_sub leading-relaxed">Undang teman Anda menyewa mesin tambang dan nikmati pembagian komisi instan langsung cair ke saldo utama Anda tanpa syarat rintangan.</p>
                        </div>
                    </div>
                </div>
                <?php
                break;
                
            case 'deposit':
                ?>
                <div class="flex flex-col gap-4 text-left">
                    <h3 class="text-sm uppercase tracking-wider text-blue_primary font-extrabold text-center">Pengisian Saldo (Deposit)</h3>
                    
                    <!-- DEPOSIT FORM SECTION -->
                    <div id="deposit_form_section" class="bg-navy_card p-5 rounded-xl border border-border_line flex flex-col gap-4">
                        <div>
                            <label class="block text-xs text-text_sub mb-1">Jumlah Nominal (IDR)</label>
                            <input id="deposit_amount_input" type="number" required placeholder="Minimal Rp 50.000" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2.5 text-sm text-text_main focus:outline-none focus:ring-1 focus:ring-blue_primary font-mono">
                            <div class="grid grid-cols-3 gap-2 mt-2">
                                <button type="button" onclick="setDepositAmountVal(50000)" class="bg-navy_dark text-xs py-1.5 rounded border border-border_line text-text_sub hover:border-blue_primary font-bold">Rp 50.000</button>
                                <button type="button" onclick="setDepositAmountVal(100000)" class="bg-navy_dark text-xs py-1.5 rounded border border-border_line text-text_sub hover:border-blue_primary font-bold">Rp 100.000</button>
                                <button type="button" onclick="setDepositAmountVal(500000)" class="bg-navy_dark text-xs py-1.5 rounded border border-border_line text-text_sub hover:border-blue_primary font-bold">Rp 500.000</button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs text-text_sub mb-2">Metode Pembayaran (Cashify Gateway)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" class="border border-blue_primary/50 bg-gradient-to-r from-blue_primary/15 to-blue_primary/5 p-2.5 rounded-lg text-left text-xs flex flex-col font-bold">
                                    <span class="text-[10px] text-blue_active">QRIS Otomatis</span>
                                    <span class="text-[8px] text-text_sub mt-0.5">E-Wallet & M-Banking</span>
                                </button>
                                <button type="button" disabled class="opacity-50 border border-border_line bg-navy_dark p-2.5 rounded-lg text-left text-xs flex flex-col">
                                    <span class="text-[10px]">BCA Virtual Account</span>
                                    <span class="text-[8px] text-text_sub mt-0.5">Segera Hadir</span>
                                </button>
                            </div>
                        </div>

                        <button onclick="handlePhpDepositSubmit()" class="w-full bg-blue_primary hover:bg-blue_active text-text_main py-2.5 rounded-lg text-sm font-bold mt-2 shadow-lg transition">Buat QRIS Cashify v2</button>
                    </div>

                    <!-- ACTIVE INVOICE SECTION (HIDDEN BY DEFAULT) -->
                    <div id="invoice_display_section" class="hidden bg-navy_card p-5 rounded-xl border border-border_line flex flex-col gap-4 text-center">
                        <span class="text-[9px] bg-yellow-500/15 border border-yellow-500/40 text-yellow-500 px-2 py-0.5 rounded-full w-max mx-auto font-mono uppercase font-bold tracking-wider animate-pulse">Menunggu Pembayaran</span>
                        
                        <div class="bg-navy_dark/60 p-3 rounded-lg border border-border_line flex flex-col gap-1">
                            <span class="text-[10px] text-text_sub block">Nominal yang wajib ditransfer:</span>
                            <div class="flex justify-center items-center gap-1.5">
                                <span id="inv_display_total" class="text-xl font-bold font-mono text-blue_active">Rp 0</span>
                                <button onclick="navigator.clipboard.writeText(rawInvoiceAmount); alert('Nominal disalin!');" class="p-1 bg-navy_light rounded border border-border_line hover:bg-border_line">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-text_main"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                </button>
                            </div>
                            <span class="text-[9px] text-custom_warning mt-1 leading-tight font-bold">*Mohon transfer sesuai angka unik ini agar deposit instan diselaraskan!</span>
                        </div>

                        <!-- Real API generated QR Code image -->
                        <div class="w-48 h-48 bg-white p-2 rounded-xl mx-auto flex items-center justify-center border-2 border-border_line relative shadow-lg">
                            <img id="qris_barcode_image" src="" alt="Scan QRIS" class="w-40 h-40" referrerPolicy="no-referrer">
                        </div>
                        <span class="text-[9px] text-text_sub italic leading-tight px-2">Pindai kode QRIS di atas dengan aplikasi E-Wallet (DANA, OVO, GoPay, ShopeePay) atau perbankan Anda.</span>

                        <div class="border-t border-border_line pt-3 text-left font-mono text-[10px] flex flex-col gap-1.5">
                            <div class="flex justify-between">
                                <span class="text-text_sub">ID Transaksi:</span>
                                <span id="inv_display_txid" class="text-text_main">CSFY-XXX</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-text_sub">Waktu Kedaluwarsa:</span>
                                <span id="inv_countdown" class="text-custom_danger font-bold">14:59</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5 mt-2">
                            <button id="qris_cancel_btn" onclick="cancelPhpInvoice()" class="py-2.5 bg-navy_light text-text_sub font-bold rounded-lg text-xs hover:bg-border_line">Batalkan</button>
                            <button id="qris_verify_btn" onclick="verifyPhpPayment()" class="py-2.5 bg-custom_success text-text_main font-bold rounded-lg text-xs flex items-center justify-center gap-1.5 hover:opacity-90">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-pulse"><polyline points="22 11.08 12 19 2 10"></polyline></svg>
                                Cek Pembayaran
                            </button>
                        </div>
                    </div>
                </div>

                <!-- OVERLAY SPINNER FOR SINKRONISASI -->
                <div id="checkout_loading_overlay" class="hidden fixed inset-0 bg-black/85 flex flex-col items-center justify-center gap-4 z-50">
                    <div class="relative w-12 h-12">
                        <div class="absolute inset-0 rounded-full border-4 border-blue_primary/10"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-t-blue_primary animate-spin"></div>
                    </div>
                    <div class="text-center">
                        <span id="loading_status_text" class="text-xs font-bold text-blue_active block">Menghubungkan ke API Cashify...</span>
                        <span class="text-[9px] text-text_sub block mt-1">Sistem mencari mutasi transaksi QRIS Anda</span>
                    </div>
                </div>

                <!-- SUCCESS TOPUP POPUP -->
                <div id="checkout_success_popup" class="hidden fixed inset-0 bg-black/90 flex items-center justify-center p-6 z-50">
                    <div class="bg-navy_card border border-border_line p-6 rounded-xl text-center flex flex-col items-center gap-4 max-w-xs shadow-2xl">
                        <div class="w-14 h-14 bg-custom_success/10 border border-custom_success/20 rounded-full flex items-center justify-center text-custom_success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-text_main tracking-wider uppercase">Isi Ulang Sukses!</h4>
                            <p id="success_popup_desc" class="text-[11px] text-text_sub mt-1 leading-relaxed">Dana saldo berhasil dikreditkan ke buku besar utama Anda secara otomatis.</p>
                        </div>
                        <button onclick="closeSuccessInvoiceRedirect()" class="w-full bg-blue_primary hover:bg-blue_active text-text_main py-2.5 rounded-lg text-xs font-bold shadow-lg">Gunakan Saldo Baru</button>
                    </div>
                </div>

                <script>
                    let rawInvoiceAmount = 0;
                    let invoiceTimer = null;

                    function setDepositAmountVal(val) {
                        document.getElementById('deposit_amount_input').value = val;
                        playSystemBeep(520, 0.08);
                    }

                    function handlePhpDepositSubmit() {
                        const amount = parseInt(document.getElementById('deposit_amount_input').value);
                        if (!amount || amount < 20000) {
                            alert('Minimal pengisian saldo adalah Rp 20.000!');
                            return;
                        }

                        // Generate unique digits
                        const unique = Math.floor(Math.random() * 900) + 100;
                        const total = amount + unique;
                        rawInvoiceAmount = total;

                        const txId = 'CSFY-PHP-' + Date.now();
                        
                        document.getElementById('inv_display_total').innerText = 'Rp ' + total.toLocaleString('id-ID');
                        document.getElementById('inv_display_txid').innerText = txId;
                        
                        // Set image source of QR Code
                        const qrPayload = '00020101021138510014ID.CASHIFY.WWW011893' + total + '5607com.orderkuota.app5802ID';
                        document.getElementById('qris_barcode_image').src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&color=0f172a&data=' + encodeURIComponent(qrPayload);

                        // Reveal invoice section, hide form section
                        document.getElementById('deposit_form_section').classList.add('hidden');
                        document.getElementById('invoice_display_section').classList.remove('hidden');

                        // Beep
                        playSystemBeep(650, 0.15);

                        // Start countdown timer (15 minutes)
                        let duration = 15 * 60;
                        const timerDisplay = document.getElementById('inv_countdown');
                        
                        if (invoiceTimer) clearInterval(invoiceTimer);
                        
                        invoiceTimer = setInterval(function() {
                            let minutes = parseInt(duration / 60, 10);
                            let seconds = parseInt(duration % 60, 10);

                            minutes = minutes < 10 ? "0" + minutes : minutes;
                            seconds = seconds < 10 ? "0" + seconds : seconds;

                            timerDisplay.innerText = minutes + ":" + seconds;

                            if (--duration < 0) {
                                clearInterval(invoiceTimer);
                                alert('Tagihan telah kedaluwarsa!');
                                cancelPhpInvoice();
                            }
                        }, 1000);
                    }

                    function cancelPhpInvoice() {
                        if (invoiceTimer) clearInterval(invoiceTimer);
                        document.getElementById('invoice_display_section').classList.add('hidden');
                        document.getElementById('deposit_form_section').classList.remove('hidden');
                        playSystemBeep(350, 0.12);
                    }

                    function verifyPhpPayment() {
                        const overlay = document.getElementById('checkout_loading_overlay');
                        const statusTxt = document.getElementById('loading_status_text');
                        
                        overlay.classList.remove('hidden');
                        playSystemBeep(440, 0.08);

                        // Polling simulation
                        setTimeout(function() {
                            statusTxt.innerText = 'Memeriksa mutasi e-wallet...';
                            playSystemBeep(480, 0.08);

                            setTimeout(function() {
                                statusTxt.innerText = 'Menyelaraskan buku kas...';
                                playSystemBeep(520, 0.08);

                                setTimeout(function() {
                                    overlay.classList.add('hidden');
                                    
                                    // Process real balance storage
                                    const rawAmt = Math.max(20000, rawInvoiceAmount);
                                    const netDeposit = rawAmt; 
                                    
                                    const currentMain = parseInt(localStorage.getItem('member_main_balance') || '0');
                                    const newMain = currentMain + netDeposit;
                                    localStorage.setItem('member_main_balance', String(newMain));

                                    // Let's also update the total topup
                                    const currentTopup = parseInt(localStorage.getItem('member_total_topup') || '0');
                                    localStorage.setItem('member_total_topup', String(currentTopup + netDeposit));

                                    // Push transaction to ledger history
                                    let ledgers = [];
                                    try {
                                        ledgers = JSON.parse(localStorage.getItem('member_ledger') || '[]');
                                    } catch(e) { ledgers = []; }
                                    
                                    const now = new Date();
                                    const dateStr = now.toLocaleDateString('id-ID') + ' ' + now.toLocaleTimeString('id-ID');
                                    
                                    ledgers.unshift({
                                        id: 'TX-' + Date.now(),
                                        desc: 'Isi Ulang Saldo QRIS Sukses',
                                        date: dateStr,
                                        amount: netDeposit,
                                        type: 'DEPOSIT'
                                    });
                                    localStorage.setItem('member_ledger', JSON.stringify(ledgers));

                                    // Trigger success popup
                                    document.getElementById('success_popup_desc').innerText = 'Faktur Rp ' + netDeposit.toLocaleString('id-ID') + ' berhasil divalidasi. Sesi Anda diperbarui.';
                                    document.getElementById('checkout_success_popup').classList.remove('hidden');
                                    
                                    // Sound success beep
                                    playSystemBeep(520, 0.1);
                                    setTimeout(function(){ playSystemBeep(650, 0.1); }, 120);
                                    setTimeout(function(){ playSystemBeep(780, 0.25); }, 240);

                                }, 1000);
                            }, 1000);
                        }, 1000);
                    }

                    function closeSuccessInvoiceRedirect() {
                        document.getElementById('checkout_success_popup').classList.add('hidden');
                        window.location.href = '?page=home';
                    }

                    // Sound oscillator system
                    function playSystemBeep(freq, dur) {
                        try {
                            const ctx = new (window.AudioContext || window.webkitAudioContext)();
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.type = 'sine';
                            osc.frequency.value = freq;
                            gain.gain.setValueAtTime(0.2, ctx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + dur);
                            osc.start();
                            osc.stop(ctx.currentTime + dur);
                        } catch (e) {
                            console.log('Audio Context muted by sandbox rules');
                        }
                    }
                </script>
                <?php
                break;

            case 'withdraw':
                ?>
                <div class="flex flex-col gap-4">
                    <h3 class="text-lg font-bold text-blue_primary text-center">Penarikan Dana (Withdraw)</h3>
                    <div class="bg-navy_card p-5 rounded-xl border border-border_line text-center flex flex-col gap-4">
                        <div class="w-12 h-12 bg-custom_warning/10 border border-custom_warning/20 text-custom_warning rounded-full flex items-center justify-center mx-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        </div>
                        <h4 class="font-bold text-sm">Akun Bank Belum Terkait</h4>
                        <p class="text-xs text-text_sub leading-relaxed">Anda wajib mengaitkan data rekening bank dan membuat PIN transaksi 6 digit terlebih dahulu sebelum mengajukan penarikan dana.</p>
                        <a href="?page=bank_account" class="w-full bg-blue_primary text-text_main py-2 rounded-lg text-xs font-semibold hover:bg-blue_active">Tautkan Bank Sekarang</a>
                    </div>
                </div>
                <?php
                break;
                
            case 'bank_account':
                ?>
                <div class="flex flex-col gap-4">
                    <h3 class="text-lg font-bold text-blue_primary text-center">Data Buku Rekening</h3>
                    <form onsubmit="alert('Data bank berhasil ditautkan!'); window.location.href='?page=home'; return false;" class="bg-navy_card p-5 rounded-xl border border-border_line flex flex-col gap-4">
                        <div>
                            <label class="block text-xs text-text_sub mb-1">Nama Bank / Dompet Digital</label>
                            <input type="text" required placeholder="Contoh: BCA, DANA" class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main">
                        </div>
                        <div>
                            <label class="block text-xs text-text_sub mb-1">Nomor Rekening / Nomor HP DANA</label>
                            <input type="text" required class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main">
                        </div>
                        <div>
                            <label class="block text-xs text-text_sub mb-1">Nama Pemilik Rekening (Sesuai Buku Bank)</label>
                            <input type="text" required class="w-full bg-navy_dark border border-border_line rounded-lg px-3 py-2 text-sm text-text_main">
                        </div>
                        <button type="submit" class="w-full bg-blue_primary text-text_main py-2.5 rounded-lg text-sm font-bold">Simpan Buku Rekening</button>
                    </form>
                </div>
                <?php
                break;
                
            case 'live_chat':
                ?>
                <div class="flex flex-col gap-3 flex-grow max-h-[400px]">
                    <h3 class="text-sm font-bold text-blue_active text-center uppercase tracking-wide">Live Chat Bantuan CS NOXARA</h3>
                    <div class="flex-grow bg-navy_card border border-border_line rounded-xl p-4 flex flex-col gap-3 overflow-y-auto min-h-[220px]">
                        <div class="bg-navy_light p-3 rounded-lg border border-border_line max-w-[85%] self-start">
                            <p class="text-[11px] text-text_main leading-relaxed">Selamat datang di layanan Chat Pusat NOXARA. Jika Anda memiliki pertanyaan seputar deposit, withdraw, ataupun error, silakan kirimkan disini.</p>
                            <span class="text-[9px] text-text_sub block mt-1 text-right">08:00</span>
                        </div>
                    </div>
                    <form onsubmit="alert('Pesan terikirim ke antrean admin!'); return false;" class="flex gap-2">
                        <input type="text" required placeholder="Ketik pesan keluhan Anda..." class="flex-grow bg-navy_card border border-border_line rounded-lg px-3 py-2 text-xs text-text_main focus:outline-none">
                        <button type="submit" class="bg-blue_primary text-text_main px-4 rounded-lg text-xs font-bold">Kirim</button>
                    </form>
                </div>
                <?php
                break;
        }
        ?>

    </main>

    <!-- FLOATING CHAT BUTTON (Fixed positioning bottom left, matches prompt rule: "Floating live chat button bawah pojok kiri, tidak menutupi bottom nav") -->
    <a href="?page=live_chat" class="fixed bottom-20 left-4 w-12 h-12 bg-blue_primary hover:bg-blue_active rounded-full flex items-center justify-center shadow-lg border border-blue_active/40 transition hover:scale-105 z-30">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-text_main"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
    </a>

    <!-- BOTTOM APP-LIKE NAVIGATION -->
    <nav class="absolute bottom-0 inset-x-0 bg-navy_card border-t border-border_line h-[64px] grid grid-cols-6 text-center text-text_sub items-center">
        <a href="?page=home" class="flex flex-col items-center gap-0.5 hover:text-blue_primary <?= ($page === 'home' || !$page) ? 'text-blue_primary' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span class="text-[9px]">Home</span>
        </a>

        <a href="?page=promo_events" class="flex flex-col items-center gap-0.5 hover:text-blue_primary <?= ($page === 'promo_events') ? 'text-blue_primary' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span class="text-[9px]">Tim</span>
        </a>

        <!-- Middle Plus Circle icon goes to Products -->
        <a href="?page=products" class="flex flex-col items-center translate-y-[-10px] z-10 transition">
            <div class="w-11 h-11 bg-blue_primary text-text_main rounded-full flex items-center justify-center border-4 border-navy_dark hover:bg-blue_active shadow-lg shadow-blue_primary/10">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="<?= ($page === 'products') ? 'text-text_main rotate-45 transition-transform' : 'text-text_main' ?>"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </div>
            <span class="text-[9px] text-blue_primary mt-0.5 font-bold">Produk</span>
        </a>

        <a href="?page=mining" class="flex flex-col items-center gap-0.5 hover:text-blue_primary <?= ($page === 'mining') ? 'text-blue_primary' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
            <span class="text-[9px]">Mining</span>
        </a>

        <a href="?page=voucher" class="flex flex-col items-center gap-0.5 hover:text-blue_primary <?= ($page === 'voucher') ? 'text-blue_primary' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            <span class="text-[9px]">Transaksi</span>
        </a>

        <a href="?page=bank_account" class="flex flex-col items-center gap-0.5 hover:text-blue_primary <?= ($page === 'bank_account') ? 'text-blue_primary' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span class="text-[9px]">Profil</span>
        </a>
    </nav>
    
</div>

<!-- GLOBAL LOCALSTORAGE STATE SYNCHRONIZATION ENGINE -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Init state
        if (!localStorage.getItem('member_main_balance')) {
            localStorage.setItem('member_main_balance', '0');
        }
        if (!localStorage.getItem('member_bonus_balance')) {
            localStorage.setItem('member_bonus_balance', '15000');
        }
        if (!localStorage.getItem('member_total_topup')) {
            localStorage.setItem('member_total_topup', '0');
        }

        const mainBal = parseInt(localStorage.getItem('member_main_balance') || '0');
        const bonusBal = parseInt(localStorage.getItem('member_bonus_balance') || '15000');
        const totalTopup = parseInt(localStorage.getItem('member_total_topup') || '0');

        // Dynamically override main home balances
        const mainBalEl = document.getElementById('user_main_balance');
        if (mainBalEl) {
            mainBalEl.innerText = "Rp " + mainBal.toLocaleString('id-ID');
        }

        const bonusBalEl = document.getElementById('user_bonus_balance');
        if (bonusBalEl) {
            bonusBalEl.innerText = "Rp " + bonusBal.toLocaleString('id-ID');
        }

        // Calculate and override VIP membership badge
        const badgeEl = document.getElementById('user_vip_badge');
        if (badgeEl) {
            let tier = 'VIP 0';
            if (totalTopup >= 1000000) {
                tier = 'VIP 3 GOLD';
                badgeEl.className = "text-[10px] bg-yellow-500/20 text-yellow-500 px-1.5 py-0.5 rounded ml-1 border border-yellow-500/45 font-extrabold uppercase";
            } else if (totalTopup >= 200000) {
                tier = 'VIP 2 SILVER';
                badgeEl.className = "text-[10px] bg-cyan-400/20 text-cyan-400 px-1.5 py-0.5 rounded ml-1 border border-cyan-400/45 font-extrabold uppercase";
            } else if (totalTopup >= 50000) {
                tier = 'VIP 1 BRONZE';
                badgeEl.className = "text-[10px] bg-orange-400/20 text-orange-400 px-1.5 py-0.5 rounded ml-1 border border-orange-400/45 font-extrabold uppercase";
            }
            badgeEl.innerText = tier;
        }

        // Render dynamic activity list on Dashboard
        const activityListEl = document.getElementById('recent_activities_list');
        if (activityListEl) {
            let ledgers = [];
            try {
                ledgers = JSON.parse(localStorage.getItem('member_ledger') || '[]');
            } catch(e) {}

            if (ledgers.length > 0) {
                let html = '<div class="flex flex-col gap-2">';
                // Show last 4 items
                ledgers.slice(0, 4).forEach(row => {
                    const isPlus = row.type === 'DEPOSIT';
                    const sign = isPlus ? '+Rp ' : '-Rp ';
                    const color = isPlus ? 'text-custom_success' : 'text-custom_danger';
                    
                    html += `
                        <div class="bg-navy_dark border border-border_line/60 p-2.5 rounded-lg flex justify-between items-center text-xs text-left antialiased font-sans">
                            <div>
                                <span class="font-bold text-text_main block">${row.desc}</span>
                                <span class="text-[9px] text-text_sub block mt-0.5">${row.date}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-black ${color} block">${sign}${row.amount.toLocaleString('id-ID')}</span>
                                <span class="text-[8px] uppercase tracking-wider text-text_sub block mt-0.5 font-mono">${row.type}</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                activityListEl.innerHTML = html;
                activityListEl.className = "py-1 text-xs text-text_sub";
            }
        }
    });
</script>

</body>
</html>
