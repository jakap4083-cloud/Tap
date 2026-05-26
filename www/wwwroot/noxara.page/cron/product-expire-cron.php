<?php
/**
 * NOXARA - Product Expiration Automation Cron
 * Command: php /www/wwwroot/noxara.page/cron/product-expire-cron.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Forbidden: CLI execution only.");
}

$start_time = microtime(true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

log_error("--- Product Expire Cron Started ---", 'cron-expired');

try {
    // 1. Fetch all active leases where expired time has passed
    $stmt = $pdo->prepare("
        SELECT up.id, up.user_id, p.name 
        FROM user_products up 
        JOIN products p ON up.product_id = p.id
        WHERE up.status = 'active' AND up.active_until <= CURRENT_TIMESTAMP
    ");
    $stmt->execute();
    $expired_leases = $stmt->fetchAll();

    $expired_count = 0;
    foreach ($expired_leases as $lease) {
        $pdo->beginTransaction();
        try {
            // Update user product state to expired
            $update = $pdo->prepare("UPDATE user_products SET status = 'expired' WHERE id = ?");
            $update->execute([$lease['id']]);

            // Feed user with notification
            $notify = $pdo->prepare("
                INSERT INTO notifications (user_id, title, content) 
                VALUES (?, 'Masa Sewa Miner Berakhir', ?)
            ");
            $notify->execute([
                $lease['user_id'],
                "Masa sewa atau sewa aktif mesin miner '" . $lease['name'] . "' (ID #" . $lease['id'] . ") milik Anda telah resmi berakhir karena masa kontrak selesai."
            ]);

            $pdo->commit();
            log_error("Sewa miner ID #" . $lease['id'] . " expired untuk user ID " . $lease['user_id'], 'cron-expired');
            $expired_count++;
        } catch (Exception $ex) {
            $pdo->rollBack();
            log_error("Failed expiring lease ID " . $lease['id'] . ": " . $ex->getMessage(), 'cron-expired-error');
        }
    }

    $end_time = microtime(true);
    $duration = $end_time - $start_time;

    $log_cron = $pdo->prepare("INSERT INTO cron_logs (cron_name, status, output_summary, duration_seconds) VALUES (?, 'success', ?, ?)");
    $log_cron->execute(['product-expire-cron', "Berhasil meremove dan memperbarui {$expired_count} sewa miner yang habis masa kontraknya.", $duration]);

    log_error("Product Expire Cron Finished. Blocked: " . $expired_count . " expired miners. Duration: " . round($duration, 3) . "s\n", 'cron-expired');
} catch (Exception $e) {
    log_error("FATAL Product Expire Cron Error: " . $e->getMessage(), 'cron-expired-error');
    try {
        $log_cron = $pdo->prepare("INSERT INTO cron_logs (cron_name, status, output_summary, duration_seconds) VALUES (?, 'failed', ?, NULL)");
        $log_cron->execute(['product-expire-cron', "Error: " . $e->getMessage()]);
    } catch (Exception $ignore) {}
}
