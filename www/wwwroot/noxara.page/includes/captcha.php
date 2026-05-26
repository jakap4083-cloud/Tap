<?php
/**
 * NOXARA - Lightweight Math Captcha Provider
 */

require_once __DIR__ . '/session.php';

function generate_math_captcha(): array {
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $operators = ['+', '-'];
    $operator = $operators[array_rand($operators)];
    
    $answer = ($operator === '+') ? ($num1 + $num2) : ($num1 - $num2);
    
    $_SESSION['captcha_answer'] = $answer;
    $_SESSION['captcha_time'] = time(); // track rate-limiting or stale captcha
    
    return [
        'question' => "Berapakah {$num1} {$operator} {$num2}?",
        'answer' => $answer
    ];
}

function verify_captcha(int $user_answer): bool {
    if (!isset($_SESSION['captcha_answer'])) {
        return false;
    }
    
    // Check if captcha is older than 5 minutes
    if (time() - ($_SESSION['captcha_time'] ?? 0) > 300) {
        unset($_SESSION['captcha_answer'], $_SESSION['captcha_time']);
        return false;
    }
    
    $correct = ((int)$_SESSION['captcha_answer'] === $user_answer);
    
    // Invalidate immediately after attempt to prevent replay attacks
    unset($_SESSION['captcha_answer'], $_SESSION['captcha_time']);
    return $correct;
}
