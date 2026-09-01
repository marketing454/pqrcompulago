<?php

require_once __DIR__ . '/includes/bootstrap.php';

function pqr_post_text(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function reject_pqr_request(string $message, int $status = 422): never
{
    http_response_code($status);
    exit($message);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    reject_pqr_request('Solicitud no válida.', 403);
}

if (!rate_limit('pqr-submit', client_ip(), 5, 3600)) {
    reject_pqr_request('Se alcanzó el límite temporal de solicitudes. Intenta más tarde.', 429);
}

if (pqr_post_text('website') !== '') {
    reject_pqr_request('Solicitud no válida.', 422);
}

$nombre       = pqr_post_text('nombre');
$apellido     = pqr_post_text('apellido');
$tipo_doc     = pqr_post_text('tipo_doc');
$documento    = pqr_post_text('documento');
$direccion    = pqr_post_text('direccion');
$departamento = pqr_post_text('departamento');
$ciudad       = pqr_post_text('ciudad');
$phone_prefix = pqr_post_text('phone_prefix');
$telefono_num = pqr_post_text('telefono');
$email        = pqr_post_text('email');
$tipo_pqr     = pqr_post_text('tipo_pqr');
$comentario   = pqr_post_text('comentario');

$allowed_doc_types = [
    'Cédula de Ciudadanía',
    'Cédula de Extranjería',
    'NIT',
    'Pasaporte'
];
$allowed_types = [
    'Petición',
    'Consultas',
    'Quejas',
    'Reclamos',
    'Garantía',
    'Derecho de Retracto',
    'Reversión del Pago',
    'Otros',
    'Cotización'
];
$allowed_prefixes = ['+57', '+1', '+34', '+52', '+58', '+507'];

$name_pattern = '/^[\p{L}\p{M}][\p{L}\p{M} .\'-]{0,99}$/u';
$document_pattern = '/^[A-Za-z0-9][A-Za-z0-9 .#-]{0,99}$/';
$phone_digits = preg_replace('/\D+/', '', $telefono_num);

if (
    !preg_match($name_pattern, $nombre)
    || !preg_match($name_pattern, $apellido)
    || !in_array($tipo_doc, $allowed_doc_types, true)
    || !preg_match($document_pattern, $documento)
    || mb_strlen($direccion) > 500
    || mb_strlen($departamento) > 100
    || mb_strlen($ciudad) > 100
    || !in_array($phone_prefix, $allowed_prefixes, true)
    || !preg_match('/^[0-9 ()-]+$/', $telefono_num)
    || !is_string($phone_digits)
    || strlen($phone_digits) < 7
    || strlen($phone_digits) > 15
    || preg_match('/[\r\n]/', $email)
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !in_array($tipo_pqr, $allowed_types, true)
    || $comentario === ''
    || mb_strlen($comentario) > 10000
    || !isset($_POST['check_datos'])
) {
    reject_pqr_request('Por favor revisa los datos obligatorios e inténtalo de nuevo.');
}

$full_name     = trim($nombre . ' ' . $apellido);
$full_phone    = trim($phone_prefix . ' ' . $telefono_num);
$full_document = $tipo_doc . ' ' . $documento;
$subject       = $tipo_pqr . ' - ' . $full_name;

$upload_items = [];
$max_file_size = 2 * 1024 * 1024;
$allowed_mimes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'pdf' => 'application/pdf'
];

if (isset($_FILES['soporte'])) {
    $files = $_FILES['soporte'];
    $required_keys = ['name', 'type', 'tmp_name', 'error', 'size'];

    foreach ($required_keys as $required_key) {
        if (!isset($files[$required_key]) || !is_array($files[$required_key])) {
            reject_pqr_request('Los adjuntos no tienen un formato válido.');
        }
    }

    $file_count = count($files['name']);
    if ($file_count > 4) {
        reject_pqr_request('Solo puedes adjuntar hasta 4 archivos.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    for ($index = 0; $index < $file_count; $index++) {
        $error = (int)$files['error'][$index];
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($files['tmp_name'][$index])) {
            reject_pqr_request('No se pudo validar uno de los adjuntos.');
        }

        $original_name = basename((string)$files['name'][$index]);
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $size = (int)$files['size'][$index];

        if ($original_name === '' || mb_strlen($original_name) > 255 || !isset($allowed_mimes[$extension]) || $size <= 0 || $size > $max_file_size) {
            reject_pqr_request('Cada adjunto debe ser JPG, PNG o PDF y pesar máximo 2 MB.');
        }

        $detected_mime = $finfo->file($files['tmp_name'][$index]);
        if ($detected_mime !== $allowed_mimes[$extension]) {
            reject_pqr_request('El contenido de uno de los adjuntos no coincide con su extensión.');
        }

        if ($extension !== 'pdf') {
            $image_info = @getimagesize($files['tmp_name'][$index]);
            if ($image_info === false || !in_array($image_info['mime'] ?? '', ['image/jpeg', 'image/png'], true)) {
                reject_pqr_request('Una de las imágenes no es válida.');
            }
        }

        $upload_items[] = [
            'original_name' => $original_name,
            'extension' => $extension,
            'tmp_name' => $files['tmp_name'][$index]
        ];
    }
}

$stored_files = [];

try {
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0700, true) && !is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('Upload directory could not be created.');
    }

    if (!is_writable(UPLOAD_DIR)) {
        throw new RuntimeException('Upload directory is not writable.');
    }

    do {
        $radicado = 'PQR-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $check_stmt = $pdo->prepare('SELECT 1 FROM tickets WHERE radicado = ? LIMIT 1');
        $check_stmt->execute([$radicado]);
    } while ($check_stmt->fetchColumn());

    $pdo->beginTransaction();

    $sql = "INSERT INTO tickets (radicado, client_name, client_email, client_phone, client_document, address, type, department, ciudad, subject, description, status)
            VALUES (:radicado, :name, :email, :phone, :document, :address, :type, :dept, :ciudad, :subject, :desc, 'Abierto')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':radicado' => $radicado,
        ':name' => $full_name,
        ':email' => $email,
        ':phone' => $full_phone,
        ':document' => $full_document,
        ':address' => $direccion,
        ':type' => $tipo_pqr,
        ':dept' => $departamento,
        ':ciudad' => $ciudad,
        ':subject' => $subject,
        ':desc' => $comentario
    ]);

    $ticket_id = (int)$pdo->lastInsertId();
    $file_stmt = $pdo->prepare('INSERT INTO ticket_files (ticket_id, file_name, file_path) VALUES (?, ?, ?)');

    foreach ($upload_items as $item) {
        $stored_name = bin2hex(random_bytes(16)) . '.' . $item['extension'];
        $target_path = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $stored_name;

        if (!move_uploaded_file($item['tmp_name'], $target_path)) {
            throw new RuntimeException('An attachment could not be stored.');
        }

        $stored_files[] = $target_path;
        $file_stmt->execute([$ticket_id, $item['original_name'], $target_path]);
    }

    $pdo->commit();

    require_once __DIR__ . '/includes/Mailer.php';
    require_once __DIR__ . '/includes/WhatsAppService.php';

    $mail_sent = Mailer::sendTicketConfirmation($email, $full_name, $radicado);
    if (!$mail_sent) {
        error_log('Ticket confirmation email could not be sent for ' . $radicado . '.');
    }
    WhatsAppService::sendTicketConfirmation($full_phone, $full_name, $radicado);

    $notification_status = $mail_sent ? '' : '&notification=failed';
    header('Location: gracias.php?radicado=' . urlencode($radicado) . $notification_status);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    foreach ($stored_files as $stored_file) {
        if (is_file($stored_file)) {
            @unlink($stored_file);
        }
    }

    error_log('PQR processing failed: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible procesar la solicitud. Intenta nuevamente.');
}
