<?php
/**
 * NOXARA - Project Utilities & Helper Functions
 */

if (!function_exists('esc')) {
    function esc(?string $string): string {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

function format_rupiah($number): string {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

function redirect(string $path) {
    header("Location: " . $path);
    exit();
}

function api_response(bool $success, string $message, array $data = [], int $http_code = 200) {
    header('Content-Type: application/json');
    http_response_code($http_code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function log_error(string $message, string $category = 'system') {
    $log_dir = __DIR__ . '/../storage/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $file = $log_dir . '/' . $category . '.log';
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . "\n", 3, $file);
}

function generate_uuid(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
