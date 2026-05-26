<?php
/**
 * NOXARA - Database Backup Automation Cron
 * Command: php /www/wwwroot/noxara.page/cron/backup-cron.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Forbidden: CLI execution only.");
}

$start_time = microtime(true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

log_error("--- Backup Cron Started ---", 'cron-backup');

try {
    $db_config = require_once __DIR__ . '/../config/database.php';
    
    $backup_dir = __DIR__ . '/../storage/backups';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }

    $backup_file_name = 'backup_' . $db_config['database'] . '_' . date('Ymd_His') . '.sql';
    $backup_path = $backup_dir . '/' . $backup_file_name;

    // Use mysqldump cli binary if available
    // Otherwise fallback to safe native SELECT loop writes
    $command = sprintf(
        "mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1",
        escapeshellarg($db_config['host']),
        escapeshellarg($db_config['port']),
        escapeshellarg($db_config['username']),
        escapeshellarg($db_config['password']),
        escapeshellarg($db_config['database']),
        escapeshellarg($backup_path)
    );

    $output = [];
    $return_var = -1;
    
    // Execute backup
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        log_error("mysqldump utility command failed with exit code {$return_var}. Attuning fallback database exporter script...", 'cron-backup');
        
        // Native PDO schema exporter fallback
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql_dump = "-- NOXARA NATIVE EXPORT FALLBACK\n-- Generated " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $sql_dump .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
            $row2 = $pdo->query("SHOW CREATE TABLE `" . $table . "`")->fetch(PDO::FETCH_NUM);
            $sql_dump .= $row2[1] . ";\n\n";

            $result_data = $pdo->query("SELECT * FROM `" . $table . "`");
            while ($row = $result_data->fetch(PDO::FETCH_ASSOC)) {
                $keys = array_keys($row);
                $escaped_vals = array_map(function($v) use ($pdo) {
                    if ($v === null) return "NULL";
                    return $pdo->quote($v);
                }, array_values($row));
                
                $sql_dump .= "INSERT INTO `" . $table . "` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escaped_vals) . ");\n";
            }
            $sql_dump .= "\n\n";
        }

        file_put_contents($backup_path, $sql_dump);
        log_error("Successfully completed backup fallback dump to {$backup_file_name}", 'cron-backup');
    } else {
        log_error("Successfully completed mysqldump command export to {$backup_file_name}", 'cron-backup');
    }

    $end_time = microtime(true);
    $duration = $end_time - $start_time;

    $log_cron = $pdo->prepare("INSERT INTO cron_logs (cron_name, status, output_summary, duration_seconds) VALUES (?, 'success', ?, ?)");
    $log_cron->execute(['backup-cron', "Berhasil mencadangkan basis data ke file: {$backup_file_name}", $duration]);

} catch (Exception $e) {
    log_error("Database backup failure: " . $e->getMessage(), 'cron-backup-error');
    try {
        $log_cron = $pdo->prepare("INSERT INTO cron_logs (cron_name, status, output_summary, duration_seconds) VALUES (?, 'failed', ?, NULL)");
        $log_cron->execute(['backup-cron', "Error: " . $e->getMessage()]);
    } catch (Exception $ignore) {}
}
