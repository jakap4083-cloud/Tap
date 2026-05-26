<?php
/**
 * NOXARA - Mining Profit Automation Cron
 * Command: php /www/wwwroot/noxara.page/cron/mining-cron.php
 */

// Only allow execution via CLI for security
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Forbidden: CLI execution only.");
}

$start_time = microtime(true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

log_error("--- Mining Cron Started ---", 'cron-mining');

try {
    // 1. Process running mining sessions that have finished counting down (ends_at <= CURRENT_TIMESTAMP)
    $stmt = $pdo->prepare("
        SELECT ms.*, up.product_id, u.username 
        FROM mining_sessions ms 
        JOIN user_products up ON ms.user_product_id = up.id
        JOIN users u ON ms.user_id = u.id
        WHERE ms.status = 'running' AND ms.ends_at <= CURRENT_TIMESTAMP
    ");
    $stmt->execute();
    $sessions = $stmt->fetchAll();

    $processed_count = 0;
    foreach ($sessions as $session) {
        $pdo->beginTransaction();
        
        try {
            // Lock user's balance for updates to ensure consistency and prevent race conditions
            $stmt_lock = $pdo->prepare("SELECT id FROM balance_accounts WHERE user_id = ? FOR UPDATE");
            $stmt_lock->execute([$session['user_id']]);
            
            // Re-verify session is indeed still running
            $stmt_chk = $pdo->prepare("SELECT status FROM mining_sessions WHERE id = ? FOR UPDATE");
            $stmt_chk->execute([$session['id']]);
            $chk = $stmt_chk->fetch();
            
            if ($chk && $chk['status'] === 'running') {
                // Update session state
                $update_sess = $pdo->prepare("UPDATE mining_sessions SET status = 'completed' WHERE id = ?");
                $update_sess->execute([$session['id']]);
                
                // Distribute profit direct to user profit balance (or main balance as requested)
                // Profit goes to profit_balance & total_profit tracker
                $update_bal = $pdo->prepare("
                    UPDATE balance_accounts 
                    SET profit_balance = profit_balance + ?, 
                        total_profit = total_profit + ? 
                    WHERE user_id = ?
                ");
                $update_bal->execute([$session['profit_amount'], $session['profit_amount'], $session['user_id']]);
                
                // Log and insert into ledger transactions using idempotent key
                $ld_stmt = $pdo->prepare("
                    INSERT INTO ledger_transactions 
                    (user_id, idempotency_key, type, profit_delta, description) 
                    VALUES (?, ?, 'dividend', ?, ?)
                ");
                $ld_stmt->execute([
                    $session['user_id'],
                    $session['idempotency_key'],
                    $session['profit_amount'],
                    "Profit Mining Hasil Mesin ID #" . $session['user_product_id']
                ]);
                
                // Log to separate report log
                $log_prof = $pdo->prepare("INSERT INTO mining_profit_logs (user_id, session_id, profit_amount) VALUES (?, ?, ?)");
                $log_prof->execute([$session['user_id'], $session['id'], $session['profit_amount']]);
                
                log_error("Processed profit Rp " . number_format($session['profit_amount'], 2) . " for user " . $session['username'], 'cron-mining');
                $processed_count++;
            }
            
            $pdo->commit();
        } catch (Exception $ex) {
            $pdo->rollBack();
            log_error("Failed processing session ID " . $session['id'] . ": " . $ex->getMessage(), 'cron-mining-error');
        }
    }

    $end_time = microtime(true);
    $duration = $end_time - $start_time;

    // Record cron log in database for admin dashboards
    $log_cron = $pdo->prepare("INSERT INTO cron_logs (cron_name, status, output_summary, duration_seconds) VALUES (?, 'success', ?, ?)");
    $log_cron->execute(['mining-cron', "Berhasil memproses {$processed_count} data mining yang selesai.", $duration]);

    log_error("Mining Cron Finished. Processed: " . $processed_count . " sessions. Duration: " . round($duration, 3) . "s\n", 'cron-mining');
} catch (Exception $e) {
    log_error("FATAL Mining Cron Error: " . $e->getMessage(), 'cron-mining-error');
    // Save failed state to database
    try {
        $log_cron = $pdo->prepare("INSERT INTO cron_logs (cron_name, status, output_summary, duration_seconds) VALUES (?, 'failed', ?, NULL)");
        $log_cron->execute(['mining-cron', "Error: " . $e->getMessage()]);
    } catch (Exception $ignore) {}
}
