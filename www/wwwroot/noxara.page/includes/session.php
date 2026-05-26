<?php
/**
 * NOXARA - Secure Session Manager
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie params
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');

    // Secure cookie if running on HTTPS
    $is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                 (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    if ($is_secure) {
        ini_set('session.cookie_secure', 1);
    }

    session_start();
}

// Session hijacking security checks
if (!isset($_SESSION['user_agent_fingerprint'])) {
    $_SESSION['user_agent_fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
} else {
    $current_fingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    if ($_SESSION['user_agent_fingerprint'] !== $current_fingerprint) {
        // Destroy potential session hijack attempt
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['user_agent_fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    }
}

// Generate new session ID after critical transitions to prevent fixation
function regenerate_session_safely() {
    session_regenerate_id(true);
}
