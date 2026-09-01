<?php

function config_env(string $name, ?string $default = null): ?string
{
    $value = getenv($name);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

define('APP_ENV', strtolower(config_env('APP_ENV', 'development')));
define('IS_PRODUCTION', APP_ENV === 'production');

define('DB_HOST', config_env('DB_HOST', 'db'));
define('DB_NAME', config_env('DB_NAME', 'pqr_db'));
define('DB_USER', config_env('DB_USER', ''));
define('DB_PASS', config_env('DB_PASSWORD', config_env('DB_PASS', '')));
define('DB_PORT', config_env('DB_PORT', '3306'));

define('UPLOAD_DIR', config_env('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads'));
define('RATE_LIMIT_DIR', config_env('RATE_LIMIT_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pqr-rate-limit'));

// Resend SMTP relay. SMTP_PASS must contain the Resend API key.
define('SMTP_HOST', config_env('SMTP_HOST', 'smtp.resend.com'));
define('SMTP_PORT', (int)config_env('SMTP_PORT', '587'));
define('SMTP_USER', config_env('SMTP_USER', 'resend'));
define('SMTP_PASS', config_env('SMTP_PASS', ''));
define('SMTP_SECURE', strtolower(config_env('SMTP_SECURE', 'tls')));
define('MAIL_FROM_EMAIL', config_env('MAIL_FROM_EMAIL', ''));
define('MAIL_FROM_NAME', config_env('MAIL_FROM_NAME', 'Compulago Atención'));
define('MAIL_REPLY_TO', config_env('MAIL_REPLY_TO', 'soporte@compulago.com'));

// Configuración WhatsApp (Meta Cloud API)
define('WHATSAPP_TOKEN', config_env('WHATSAPP_TOKEN', ''));
define('WHATSAPP_PHONE_ID', config_env('WHATSAPP_PHONE_ID', ''));
define('WHATSAPP_API_VERSION', config_env('WHATSAPP_API_VERSION', 'v17.0'));

define('SITE_URL', rtrim(config_env('SITE_URL', 'http://localhost:8085'), '/'));

if (IS_PRODUCTION && (DB_USER === '' || DB_PASS === '' || MAIL_FROM_EMAIL === '')) {
    error_log('Required production configuration is missing.');
    http_response_code(500);
    exit('Servicio temporalmente no disponible.');
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4;port=" . DB_PORT;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT             => 5,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Servicio temporalmente no disponible.');
}
