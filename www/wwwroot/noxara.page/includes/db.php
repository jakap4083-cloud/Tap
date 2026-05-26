<?php
/**
 * NOXARA - Database Connection Initializer (PDO)
 */

$db_config = require_once __DIR__ . '/../config/database.php';

try {
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=%s",
        $db_config['host'],
        $db_config['port'],
        $db_config['database'],
        $db_config['charset']
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
} catch (PDOException $e) {
    // Write detailed error log securely to storage/logs
    $log_dir = __DIR__ . '/../storage/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    error_log("[" . date('Y-m-d H:i:s') . "] DB Connection Failed: " . $e->getMessage() . "\n", 3, $log_dir . '/error.log');
    
    // Output safe message for browser users without leaking credentials
    http_response_code(500);
    die("<h1>Database Connection Error</h1><p>We are experiencing technical difficulties. Please try again later.</p>");
}

return $pdo;
