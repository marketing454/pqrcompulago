<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';

send_security_headers(true);
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

function respond_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function normalize_lookup_document(string $value): string
{
    return strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', $value));
}

function stored_document_value(string $value): string
{
    foreach (['Cédula de Ciudadanía', 'Cédula de Extranjería', 'NIT', 'Pasaporte', 'C.C.', 'C.E.', 'C.C', 'C.E', 'CC', 'CE', 'TI', 'T.I.'] as $document_type) {
        $prefix = $document_type . ' ';
        if (str_starts_with($value, $prefix)) {
            return substr($value, strlen($prefix));
        }
    }

    return $value;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond_json([
        'success' => false,
        'message' => 'Método no permitido.'
    ], 405);
}

if (!rate_limit('pqr-lookup', client_ip(), 20, 60)) {
    respond_json([
        'success' => false,
        'message' => 'Se alcanzó el límite temporal de consultas. Intenta más tarde.'
    ], 429);
}

$radicado_value = $_POST['radicado'] ?? '';
$verification_value = $_POST['verification'] ?? '';
$radicado = is_string($radicado_value) ? trim($radicado_value) : '';
$verification = is_string($verification_value) ? trim($verification_value) : '';

if (
    !preg_match('/^[A-Za-z0-9][A-Za-z0-9-]{7,29}$/', $radicado)
    || $verification === ''
    || mb_strlen($verification) > 254
    || preg_match('/[\r\n\x00-\x1F\x7F]/', $verification)
) {
    respond_json([
        'success' => false,
        'message' => 'Ingresa un radicado y el correo o documento registrado.'
    ], 422);
}

$verification_email = filter_var($verification, FILTER_VALIDATE_EMAIL) ? strtolower($verification) : '';
$document_key = $verification_email === '' ? normalize_lookup_document($verification) : '';

if ($verification_email === '' && strlen($document_key) < 6) {
    respond_json([
        'success' => false,
        'message' => 'El segundo dato debe ser un correo válido o un documento de al menos 6 caracteres.'
    ], 422);
}

try {
    $sql = "SELECT id, radicado, client_name, client_email, client_document, ciudad, department, type, subject, description, status, created_at
            FROM tickets
            WHERE LOWER(radicado) = LOWER(:radicado)
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':radicado' => $radicado]);

    $ticket = $stmt->fetch();
    $verification_matches = false;

    if ($ticket) {
        if ($verification_email !== '') {
            $verification_matches = hash_equals(
                strtolower((string)$ticket['client_email']),
                $verification_email
            );
        } else {
            $stored_document = stored_document_value((string)$ticket['client_document']);
            $verification_matches = hash_equals(
                normalize_lookup_document($stored_document),
                $document_key
            );
        }
    }

    if (!$ticket || !$verification_matches) {
        respond_json([
            'success' => false,
            'message' => 'No encontramos una solicitud con los datos ingresados.'
        ]);
    }

    $tickets = [$ticket];

    $results = [];
    $stmt_replies = $pdo->prepare("SELECT id, message, created_at FROM ticket_replies WHERE ticket_id = ? AND is_internal = 0 ORDER BY created_at ASC");

    foreach ($tickets as $t) {
        $stmt_replies->execute([$t['id']]);
        $replies = $stmt_replies->fetchAll();

        // Formatear fechas en español
        $timestamp = strtotime($t['created_at']);
        $meses = [1=>'Ene', 2=>'Feb', 3=>'Mar', 4=>'Abr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Ago', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dic'];
        $fecha_formateada = date('d', $timestamp) . ' ' . $meses[(int)date('n', $timestamp)] . ' ' . date('Y, H:i', $timestamp) . ' hrs';

        $formatted_replies = [];
        foreach ($replies as $r) {
            $r_ts = strtotime($r['created_at']);
            $r_fecha = date('d', $r_ts) . ' ' . $meses[(int)date('n', $r_ts)] . ' ' . date('Y, H:i', $r_ts) . ' hrs';
            $formatted_replies[] = [
                'message' => $r['message'],
                'date' => $r_fecha
            ];
        }

        $results[] = [
            'id' => $t['id'],
            'radicado' => $t['radicado'],
            'status' => $t['status'],
            'type' => $t['type'],
            'client_name' => $t['client_name'],
            'ciudad' => $t['ciudad'] ?: ($t['department'] ?: 'Colombia'),
            'subject' => $t['subject'],
            'description' => $t['description'],
            'created_at_human' => $fecha_formateada,
            'replies' => $formatted_replies
        ];
    }

    respond_json([
        'success' => true,
        'count' => count($results),
        'tickets' => $results
    ]);

} catch (Throwable $e) {
    error_log('PQR lookup failed: ' . $e->getMessage());
    respond_json([
        'success' => false,
        'message' => 'No fue posible completar la consulta. Intenta nuevamente.'
    ], 500);
}
