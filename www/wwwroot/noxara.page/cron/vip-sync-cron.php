<?php
/**
 * NOXARA - VIP Level Consistency Verification Cron
 * Command: php /www/wwwroot/noxara.page/cron/vip-sync-cron.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Forbidden: CLI execution only.");
}

$start_time = microtime(true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

log_error("--- VIP Sync Cron Started ---", 'cron-vipsync');

try {
    // 1. Core audit query: Find the sum of all approved topups grouped by user_id
    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.username, COALESCE(SUM(t.nominal), 0) as total_approved
        FROM users u
        LEFT JOIN topups t ON u.id = t.user_id AND t.status = 'approved'
        GROUP BY u.id
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();

    // 2. Fetch all VIP level tiers
    $v_stmt = $pdo->prepare("SELECT * FROM vip_levels WHERE is_active = 1 ORDER BY level_number DESC");
    $v_stmt->execute();
    $vip_tiers = $v_stmt->fetchAll();

    $synced_count = 0;
    foreach ($users as $usr) {
        $user_id = $usr['user_id'];
        $total_topup = (float)$usr['total_approved'];
        
        // Find matching level
        $matched_level_number = 0; // Default VIP 0
        foreach ($vip_tiers as $tier) {
            if ($total_topup >= (float)$tier['min_total_topup']) {
                $matched_level_number = (int)$tier['level_number'];
                break;
            }
        }

        // Fetch current status
        $status_stmt = $pdo->prepare("SELECT level_number, accumulated_topup FROM user_vip_status WHERE user_id = ?");
        $status_stmt->execute([$user_id]);
        $curr = $status_stmt->fetch();

        if (!$curr) {
            $ins = $pdo->prepare("INSERT INTO user_vip_status (user_id, level_number, accumulated_topup) VALUES (?, ?, ?)");
            $ins->execute([$user_id, $matched_level_number, $total_topup]);
            $synced_count++;
        } else if ((int)$curr['level_number'] !== $matched_level_number || (float)$curr['accumulated_topup'] !== $total_topup) {
            $upd = $pdo->prepare("UPDATE user_vip_status SET level_number = ?, accumulated_topup = ? WHERE user_id = ?");
            $upd->execute([$matched_level_number, $total_topup, $user_id]);
            log_error("Synchronized VIP drift for user {$usr['username']}: VIP {$curr['level_number']} -> VIP {$matched_level_number}", 'cron-vipsync');
            $synced_count++;
        }
    }

    $end_time = microtime(true);
    $duration = $end_time - $start_time;

    $log_cron = $pdo->prepare("INSERT INTO cron_logs (cron_name, status, output_summary, duration_seconds) VALUES (?, 'success', ?, ?)");
    $log_cron->execute(['vip-sync-cron', "Berhasil menyelaraskan {$synced_count} baris data keanggotaan VIP.", $duration]);

    log_error("VIP Sync Cron Finished. Checked: " . count($users) . " users. Refreshed: " . $synced_count . " records. Duration: " . round($duration, 3) . "s\n", 'cron-vipsync');
} catch (Exception $e) {
    log_error("FATAL VIP Sync Cron Error: " . $e->getMessage(), 'cron-vipsync-error');
    try {
        $log_cron = $pdo->prepare("INSERT INTO cron_logs (cron_name, status, output_summary, duration_seconds) VALUES (?, 'failed', ?, NULL)");
        $log_cron->execute(['vip-sync-cron', "Error: " . $e->getMessage()]);
    } catch (Exception $ignore) {}
}
