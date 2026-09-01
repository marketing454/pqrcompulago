<?php

function send_security_headers(bool $no_store = false): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    if ($no_store) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    if (IS_PRODUCTION) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $is_https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $is_forwarded_https = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_name('PQRSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => IS_PRODUCTION || $is_https || $is_forwarded_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

function csrf_token(): string
{
    secure_session_start();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token(?string $token): bool
{
    secure_session_start();

    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function client_ip(): string
{
    return filter_var($_SERVER['REMOTE_ADDR'] ?? 'unknown', FILTER_VALIDATE_IP) ?: 'unknown';
}

function rate_limit(string $bucket, string $key, int $limit, int $window_seconds): bool
{
    if (!is_dir(RATE_LIMIT_DIR) && !mkdir(RATE_LIMIT_DIR, 0700, true) && !is_dir(RATE_LIMIT_DIR)) {
        error_log('Unable to create rate limit directory.');
        return false;
    }

    $file = RATE_LIMIT_DIR . DIRECTORY_SEPARATOR . hash('sha256', $bucket . "\0" . $key) . '.json';
    $handle = @fopen($file, 'c+');

    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        error_log('Unable to access rate limit storage.');
        return false;
    }

    $contents = stream_get_contents($handle);
    $entries = json_decode($contents ?: '[]', true);
    $now = time();

    if (!is_array($entries)) {
        $entries = [];
    }

    $entries = array_values(array_filter($entries, static function ($timestamp) use ($now, $window_seconds): bool {
        return is_numeric($timestamp) && (int)$timestamp > ($now - $window_seconds);
    }));

    if (count($entries) >= $limit) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    $entries[] = $now;
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($entries, JSON_THROW_ON_ERROR));
    fflush($handle);
    chmod($file, 0600);
    flock($handle, LOCK_UN);
    fclose($handle);

    return true;
}

function reset_rate_limit(string $bucket, string $key): void
{
    $file = RATE_LIMIT_DIR . DIRECTORY_SEPARATOR . hash('sha256', $bucket . "\0" . $key) . '.json';

    if (is_file($file)) {
        @unlink($file);
    }
}

function require_admin(?string $required_role = null): void
{
    secure_session_start();

    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }

    global $pdo;

    try {
        $stmt = $pdo->prepare('SELECT id, name, role FROM users_admin WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('Admin session validation failed: ' . $e->getMessage());
        http_response_code(503);
        exit('Servicio temporalmente no disponible.');
    }

    if (!$admin) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit;
    }

    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_role'] = $admin['role'];

    if ($required_role !== null && $admin['role'] !== $required_role) {
        http_response_code(403);
        exit('Acceso denegado.');
    }
}
