<?php
/**
 * NOXARA - Cashify QRIS Payment Reconciliation Cron
 * Command: php /www/wwwroot/noxara.page/cron/cashify-payment-cron.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Forbidden: CLI execution only.");
}

$start_time = microtime(true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

log_error("--- Cashify Cron Started ---", 'cron-cashify');

// Function to handle referral commission hierarchy (Upline 3 Levels)
function processTopupReferralCommission($pdo, $topup_id, $user_id, $amount) {
    // 1. Fetch upline commission settings
    $stmt = $pdo->prepare("SELECT * FROM referral_commission_rates WHERE commission_type = 'topup'");
    $stmt->execute();
    $rates = $stmt->fetch();
    if (!$rates) return;

    $levels = [
        1 => $rates['level_1_percent'],
        2 => $rates['level_2_percent'],
        3 => $rates['level_3_percent']
    ];

    // 2. Transgress recursive ancestor nodes (1st level is physical referrer, then parent, then grandparent)
    $stmt_uplink = $pdo->prepare("
        SELECT r.referred_by as upline_id, u.username
        FROM user_referrals r
        JOIN users u ON r.referred_by = u.id
        WHERE r.user_id = ?
    ");

    $current_child = $user_id;

    for ($depth = 1; $depth <= 3; $depth++) {
        $stmt_uplink->execute([$current_child]);
        $upline = $stmt_uplink->fetch();
        
        if (!$upline) {
            break; // No more uplines
        }

        $upline_id = $upline['upline_id'];
        $commission_percent = $levels[$depth];
        $commission_earned = ($amount * $commission_percent) / 100;

        if ($commission_earned > 0) {
            // Idempotency key for referral commissions
            $idempotency_key = "referral_topup_{$topup_id}_{$upline_id}_{$depth}";

            try {
                // Add commission balance to upline user
                $add_comm = $pdo->prepare("
                    UPDATE balance_accounts 
                    SET commission_balance = commission_balance + ? 
                    WHERE user_id = ?
                ");
                $add_comm->execute([$commission_earned, $upline_id]);

                // Record in referral commissions log table
                $log_comm = $pdo->prepare("
                    INSERT INTO referral_commissions 
                    (recipient_user_id, trigger_user_id, type, reference_id, level_depth, source_amount, commission_rate, commission_amount, idempotency_key)
                    VALUES (?, ?, 'topup', ?, ?, ?, ?, ?, ?)
                ");
                $log_comm->execute([
                    $upline_id,
                    $user_id,
                    $topup_id,
                    $depth,
                    $amount,
                    $commission_percent,
                    $commission_earned,
                    $idempotency_key
                ]);

                // Create ledger transaction entry for upline
                $ledger_upline = $pdo->prepare("
                    INSERT INTO ledger_transactions 
                    (user_id, idempotency_key, type, commission_delta, description) 
                    VALUES (?, ?, 'referral', ?, ?)
                ");
                $ledger_upline->execute([
                    $upline_id,
                    $idempotency_key,
                    $commission_earned,
                    "Rabat Referral Level {$depth} atas Pengisian Saldo Bawahan (ID #{$user_id})"
                ]);

                log_error("Referral Upline Comm Level {$depth} paid to user ID {$upline_id} amount Rp{$commission_earned}", 'cron-cashify');
            } catch (PDOException $ex) {
                // Handle duplicate key easily - means already processed
                log_error("Referral Comm already processed for Upline ID {$upline_id} depth {$depth}: " . $ex->getMessage(), 'cron-cashify');
            }
        }

        // Parent becomes child for next bubble up iteration
        $current_child = $upline_id;
    }
}

// Function to check and update VIP Level automatically
function checkAndUpgradeVipLevel($pdo, $user_id) {
    // 1. Calculate approved total top-up
    $stmt = $pdo->prepare("
        SELECT SUM(nominal) as total 
        FROM topups 
        WHERE user_id = ? AND status = 'approved'
    ");
    $stmt->execute([$user_id]);
    $total_topup = $stmt->fetch()['total'] ?? 0;

    // 2. Query VIP profiles to find the highest matches
    $stmt_vip = $pdo->prepare("
        SELECT id, name, level_number, min_total_topup 
        FROM vip_levels 
        WHERE is_active = 1 AND min_total_topup <= ?
        ORDER BY level_number DESC LIMIT 1
    ");
    $stmt_vip->execute([$total_topup]);
    $vip_match = $stmt_vip->fetch();

    if ($vip_match) {
        $target_vip_level = $vip_match['level_number'];

        // Get current level
        $stmt_curr = $pdo->prepare("SELECT level_number FROM user_vip_status WHERE user_id = ? FOR UPDATE");
        $stmt_curr->execute([$user_id]);
        $curr = $stmt_curr->fetch();

        if (!$curr) {
            $pdo->prepare("INSERT INTO user_vip_status (user_id, level_number, accumulated_topup) VALUES (?, ?, ?)")
                ->execute([$user_id, $target_vip_level, $total_topup]);
        } else if ($target_vip_level > $curr['level_number']) {
            // Level UP!
            $pdo->prepare("UPDATE user_vip_status SET level_number = ?, accumulated_topup = ? WHERE user_id = ?")
                ->execute([$target_vip_level, $total_topup, $user_id]);

            // Notify user
            $pdo->prepare("INSERT INTO notifications (user_id, title, content) VALUES (?, 'Level VIP Anda Naik!', ?)")
                ->execute([
                    $user_id,
                    "Selamat! Tingkat Keanggotaan Anda telah ditingkatkan menjadi '" . $vip_match['name'] . "' secara otomatis karena total deposit Anda telah mencapai " . format_rupiah($total_topup)
                ]);

            log_error("User ID {$user_id} upgraded from VIP {$curr['level_number']} to VIP {$target_vip_level}", 'cron-cashify');
        } else {
            // Update only accumulated sum
            $pdo->prepare("UPDATE user_vip_status SET accumulated_topup = ? WHERE user_id = ?")
                ->execute([$total_topup, $user_id]);
        }
    }
}

try {
    // Collect Cashify static setting details
    $stmt_set = $pdo->prepare("SELECT * FROM cashify_settings LIMIT 1");
    $stmt_set->execute();
    $cashify = $stmt_set->fetch();
    if (!$cashify) {
        throw new Exception("Cashify configurations missing from settings database.");
    }

    // Select pending topup entries that haven't expired
    $stmt_top = $pdo->prepare("
        SELECT t.*, u.username 
        FROM topups t
        JOIN users u ON t.user_id = u.id
        WHERE t.status = 'pending' AND t.expires_at > CURRENT_TIMESTAMP
    ");
    $stmt_top->execute();
    $pending_orders = $stmt_top->fetchAll();

    $processed_count = 0;

    foreach ($pending_orders as $order) {
        // Query Cashify Status API
        $check_url = $cashify['cashify_base_url'] . '/api/generate/check-status';
        
        $payload = [
            'transactionId' => $order['cashify_transaction_id']
        ];

        $ch = curl_init($check_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-license-key: ' . $cashify['cashify_license_key'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            
            // Check status flag
            $status = $data['status'] ?? ($data['data']['status'] ?? 'pending');
            
            if ($status === 'success' || $status === 'paid' || $status === 'approved') {
                $pdo->beginTransaction();
                try {
                    // Lock user balance
                    $stmt_lock = $pdo->prepare("SELECT id FROM balance_accounts WHERE user_id = ? FOR UPDATE");
                    $stmt_lock->execute([$order['user_id']]);

                    // Verify double process check
                    $stmt_chk = $pdo->prepare("SELECT status FROM topups WHERE id = ? FOR UPDATE");
                    $stmt_chk->execute([$order['id']]);
                    $chk_status = $stmt_chk->fetch();

                    if ($chk_status && $chk_status['status'] === 'pending') {
                        // Mark topup order approved
                        $up_top = $pdo->prepare("UPDATE topups SET status = 'approved', approved_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $up_top->execute([$order['id']]);

                        // Net funds added: pure nominal + voucher bonus if applicable
                        $original_nominal = $order['nominal'];
                        $bonus_reward = $order['voucher_bonus'];
                        $total_added = $original_nominal + $bonus_reward;

                        // Increment main balance
                        $up_bal = $pdo->prepare("UPDATE balance_accounts SET main_balance = main_balance + ? WHERE user_id = ?");
                        $up_bal->execute([$total_added, $order['user_id']]);

                        // Generate ledger line with Cashify idempotency
                        $idempotency_key = "cashify_topup_paid_{$order['id']}_{$order['cashify_transaction_id']}";
                        
                        $led_line = $pdo->prepare("
                            INSERT INTO ledger_transactions 
                            (user_id, idempotency_key, type, main_delta, description)
                            VALUES (?, ?, 'topup', ?, ?)
                        ");
                        $led_line->execute([
                            $order['user_id'],
                            $idempotency_key,
                            $total_added,
                            "Top Up Saldo Utama via Cashify QRIS (Voucher Bonus: Rp {$bonus_reward})"
                        ]);

                        // Process automatic VIP checks
                        checkAndUpgradeVipLevel($pdo, $order['user_id']);

                        // Process 3 Level Referral upline payouts
                        processTopupReferralCommission($pdo, $order['id'], $order['user_id'], $original_nominal);

                        log_error("Payment validated for user " . $order['username'] . " amount: Rp " . $order['nominal'], 'cron-cashify');
                        $processed_count++;
                    }

                    $pdo->commit();
                } catch (Exception $ex) {
                    $pdo->rollBack();
                    log_error("Error completing credit for order ID " . $order['id'] . ": " . $ex->getMessage(), 'cron-cashify-error');
                }
            }
        }
    }

    // Check for expired pending topup orders (expires_at <= CURRENT_TIMESTAMP)
    $stmt_exp = $pdo->prepare("UPDATE topups SET status = 'expired' WHERE status = 'pending' AND expires_at <= CURRENT_TIMESTAMP");
    $stmt_exp->execute();
    $exp_rows = $stmt_exp->rowCount();
    if ($exp_rows > 0) {
        log_error("Expired {$exp_rows} pending deposits that timed out.", 'cron-cashify');
    }

    $end_time = microtime(true);
    $duration = $end_time - $start_time;

    $log_cron = $pdo->prepare("INSERT INTO cron_logs (cron_name, status, output_summary, duration_seconds) VALUES (?, 'success', ?, ?)");
    $log_cron->execute(['cashify-payment-cron', "Selesai menyelaraskan status pembayaran. Terproses baru: {$processed_count} approved, {$exp_rows} expired.", $duration]);

    log_error("Cashify Cron Finished. Complete: {$processed_count}. Duration: " . round($duration, 3) . "s\n", 'cron-cashify');
} catch (Exception $e) {
    log_error("FATAL Cashify Cron Error: " . $e->getMessage(), 'cron-cashify-error');
    try {
        $log_cron = $pdo->prepare("INSERT INTO cron_logs (cron_name, status, output_summary, duration_seconds) VALUES (?, 'failed', ?, NULL)");
        $log_cron->execute(['cashify-payment-cron', "Error: " . $e->getMessage()]);
    } catch (Exception $ignore) {}
}
