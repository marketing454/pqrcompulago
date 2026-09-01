<?php

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$file_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$file_id) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$stmt = $pdo->prepare('SELECT file_name, file_path FROM ticket_files WHERE id = ? LIMIT 1');
$stmt->execute([$file_id]);
$file = $stmt->fetch();

$base_dir = realpath(UPLOAD_DIR);
$requested_path = $file ? realpath($file['file_path']) : false;

if (
    !$file
    || $base_dir === false
    || $requested_path === false
    || !is_file($requested_path)
    || strpos($requested_path, $base_dir . DIRECTORY_SEPARATOR) !== 0
) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($requested_path) ?: 'application/octet-stream';
$download_name = basename((string)$file['file_name']);
$download_name = preg_replace('/[\r\n"]+/', '_', $download_name) ?: 'archivo';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . addcslashes($download_name, '\\') . '"; filename*=UTF-8\'\'' . rawurlencode($download_name));
header('Content-Length: ' . (string)filesize($requested_path));
header('Cache-Control: private, no-store, max-age=0');
readfile($requested_path);
exit;
