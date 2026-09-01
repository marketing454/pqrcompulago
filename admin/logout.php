<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

$_SESSION = [];
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => '/',
    'secure' => IS_PRODUCTION || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_destroy();
header('Location: login.php');
exit;
