<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$ticket_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$ticket_id) {
    header('Location: tickets.php');
    exit;
}

// Obtener datos del ticket
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    http_response_code(404);
    exit('Ticket no encontrado.');
}

$reply_success = false;
$reply_failed = false;
$status_updated = false;

// Procesar Respuestas o Cambio de Estado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }

    if (isset($_POST['update_status'])) {
        $new_status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
        $allowed_statuses = ['Abierto', 'En Proceso', 'Resuelto', 'Cerrado'];
        if (!in_array($new_status, $allowed_statuses, true)) {
            http_response_code(422);
            exit('Estado no válido.');
        }
        $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $ticket_id]);
        $status_updated = true;
    }
    
    $message_value = $_POST['message'] ?? '';
    if (isset($_POST['send_reply']) && is_string($message_value) && trim($message_value) !== '') {
        $message = trim($message_value);
        if (mb_strlen($message) > 10000) {
            http_response_code(422);
            exit('La respuesta supera el tamaño permitido.');
        }
        $is_internal = isset($_POST['is_internal']) ? 1 : 0;
        
        $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_internal) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ticket_id, $_SESSION['admin_id'], $message, $is_internal]);
        
        if (!$is_internal) {
            require_once __DIR__ . '/../includes/Mailer.php';
            require_once __DIR__ . '/../includes/WhatsAppService.php';
            
            $reply_success = Mailer::sendTicketReply($ticket['client_email'], $ticket['client_name'], $ticket['radicado'], $message);
            WhatsAppService::sendTicketReplyNotification($ticket['client_phone'], $ticket['radicado']);
            $reply_failed = !$reply_success;
        }
    }

    header("Location: view_ticket.php?id=" . $ticket_id . ($reply_success ? "&sent=1" : "") . ($reply_failed ? "&send_failed=1" : "") . ($status_updated ? "&updated=1" : ""));
    exit;
}

// Obtener archivos adjuntos
$stmt = $pdo->prepare("SELECT * FROM ticket_files WHERE ticket_id = ?");
$stmt->execute([$ticket_id]);
$files = $stmt->fetchAll();

// Obtener respuestas (historial)
$stmt = $pdo->prepare("SELECT tr.*, u.name as user_name FROM ticket_replies tr LEFT JOIN users_admin u ON tr.user_id = u.id WHERE tr.ticket_id = ? ORDER BY tr.created_at ASC");
$stmt->execute([$ticket_id]);
$replies = $stmt->fetchAll();

$page_title = "Ticket #" . $ticket['radicado'];
include 'layouts/head.php';
include 'layouts/sidebar.php';

// Limpieza de teléfono para WhatsApp
$wa_phone = preg_replace('/[^0-9]/', '', $ticket['client_phone']);

// Fechas formateadas en español
$timestamp = strtotime($ticket['created_at']);
$meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$fecha_formateada = date('d', $timestamp) . ' ' . $meses[(int)date('m', $timestamp)-1] . ' ' . date('Y', $timestamp);
$hora_formateada = date('H:i', $timestamp) . ' hrs';
?>

<style>
    .ticket-workspace {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 1.8rem;
        align-items: start;
    }

    @media (max-width: 1100px) {
        .ticket-workspace { grid-template-columns: 1fr; }
    }

    /* Bento Hero Banner */
    .ticket-hero-banner {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 1.8rem 2rem;
        margin-bottom: 1.8rem;
        box-shadow: var(--shadow-subtle);
        border-top: 4px solid var(--compu-green);
    }

    .hero-top-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        padding-bottom: 1.2rem;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 1.4rem;
    }

    .radicado-badge-big {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #fcfef7;
        border: 1.5px solid rgba(176, 200, 11, 0.6);
        padding: 8px 16px;
        border-radius: var(--radius-sm);
    }

    .radicado-badge-big span {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--compu-green-dark);
        letter-spacing: 0.5px;
    }

    .datetime-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 8px 14px;
        border-radius: var(--radius-sm);
        font-size: 0.86rem;
        font-weight: 700;
        color: var(--text-dark);
    }

    /* Client Details Grid */
    .client-details-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.4rem;
    }

    .client-detail-box {
        display: flex;
        flex-direction: column;
    }

    .detail-box-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        font-weight: 800;
        color: var(--text-muted);
        letter-spacing: 0.6px;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .detail-box-value {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--text-dark);
        word-break: break-word;
    }

    .email-highlight {
        color: #0284c7;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .email-highlight:hover {
        text-decoration: underline;
    }

    /* Conversation Bubble Timeline */
    .timeline-container {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
        margin-top: 1.8rem;
    }

    .bubble {
        padding: 1.2rem 1.5rem;
        border-radius: var(--radius-md);
        position: relative;
        max-width: 88%;
        box-shadow: var(--shadow-subtle);
        animation: fadeIn 0.25s ease;
    }

    .bubble.client {
        align-self: flex-start;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 2px;
    }

    .bubble.agent {
        align-self: flex-end;
        background: #f8faf2;
        border: 1.5px solid rgba(176, 200, 11, 0.45);
        border-bottom-right-radius: 2px;
    }

    .bubble.internal {
        align-self: center;
        width: 100%;
        max-width: 100%;
        background: #fffdf5;
        border: 1.5px dashed #f59e0b;
    }

    .bubble-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.76rem;
        color: var(--text-muted);
        margin-bottom: 8px;
        font-weight: 600;
    }

    .reply-box-card {
        background: #ffffff;
        border: 1.5px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 1.6rem;
        margin-top: 1.8rem;
        box-shadow: var(--shadow-subtle);
    }

    .file-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: var(--radius-sm);
        color: var(--text-dark);
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .file-chip:hover {
        border-color: var(--compu-green);
        background: var(--compu-green-soft);
        color: var(--compu-green-dark);
    }

    .email-dispatch-notice {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: var(--radius-sm);
        padding: 10px 14px;
        font-size: 0.83rem;
        color: #0369a1;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1rem;
    }
</style>

<main class="main-content">
    <?php include 'layouts/topnav.php'; ?>

    <?php if (isset($_GET['sent'])): ?>
        <div class="card" style="background: #f0fdf4; color: #166534; border: 1.5px solid #bbf7d0; padding: 12px 18px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
            <span><strong>¡Respuesta registrada exitosamente!</strong> Se ha enviado la notificación oficial por correo a <strong><?php echo htmlspecialchars($ticket['client_email']); ?></strong>.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="card" style="background: #f0fdf4; color: #166534; border: 1.5px solid #bbf7d0; padding: 12px 18px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
            <span>Estado de la solicitud actualizado correctamente a <strong><?php echo htmlspecialchars($ticket['status']); ?></strong>.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['send_failed'])): ?>
        <div class="card" style="background: #fff7ed; color: #9a3412; border: 1.5px solid #fed7aa; padding: 12px 18px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.1rem;"></i>
            <span><strong>Respuesta guardada, pero no se pudo enviar el correo.</strong> Revisa la configuración SMTP de Resend y reintenta la notificación.</span>
        </div>
    <?php endif; ?>

    <!-- Bento Hero Banner Principal -->
    <div class="ticket-hero-banner">
        
        <!-- Fila 1: Radicado, Fecha/Hora exacta y Estados -->
        <div class="hero-top-row">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div class="radicado-badge-big">
                    <i class="fa-solid fa-hashtag" style="color: var(--compu-green-dark);"></i>
                    <span><?php echo htmlspecialchars($ticket['radicado']); ?></span>
                </div>
                <span class="status-badge <?php 
                    echo $ticket['status'] == 'Abierto' ? 'bg-open' : ($ticket['status'] == 'En Proceso' ? 'bg-process' : ($ticket['status'] == 'Resuelto' ? 'bg-resolved' : 'bg-danger')); 
                ?>" style="font-size: 0.85rem; padding: 6px 14px;">
                    <?php if ($ticket['status'] == 'Abierto'): ?>
                        <span class="pulse-dot-red"></span>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($ticket['status']); ?>
                </span>
                <span class="type-badge-pill" style="font-size: 0.85rem; padding: 6px 14px;">
                    <?php echo htmlspecialchars($ticket['type']); ?>
                </span>
            </div>

            <div class="datetime-tag">
                <i class="fa-solid fa-calendar-day" style="color: var(--compu-green-dark);"></i>
                <span>Radicado el <strong><?php echo $fecha_formateada; ?></strong> a las <strong><?php echo $hora_formateada; ?></strong></span>
            </div>
        </div>

        <!-- Fila 2: Información Integral del Cliente (Nombre, Correo, Documento, Teléfono, Ciudad) -->
        <div class="client-details-strip">
            
            <div class="client-detail-box">
                <span class="detail-box-label"><i class="fa-solid fa-user"></i> Cliente</span>
                <span class="detail-box-value"><?php echo htmlspecialchars($ticket['client_name']); ?></span>
            </div>

            <div class="client-detail-box">
                <span class="detail-box-label"><i class="fa-solid fa-envelope"></i> Correo Electrónico</span>
                <span class="detail-box-value">
                    <a href="mailto:<?php echo htmlspecialchars($ticket['client_email']); ?>" class="email-highlight" title="Enviar correo">
                        <?php echo htmlspecialchars($ticket['client_email']); ?>
                    </a>
                </span>
            </div>

            <div class="client-detail-box">
                <span class="detail-box-label"><i class="fa-solid fa-id-card"></i> Documento</span>
                <span class="detail-box-value"><?php echo htmlspecialchars($ticket['client_document'] ?: 'No registrado'); ?></span>
            </div>

            <div class="client-detail-box">
                <span class="detail-box-label"><i class="fa-brands fa-whatsapp" style="color: #16a34a;"></i> WhatsApp / Teléfono</span>
                <span class="detail-box-value">
                    <a href="https://wa.me/<?php echo $wa_phone; ?>" target="_blank" rel="noopener noreferrer" style="color: var(--compu-green-dark); text-decoration: none;" title="Abrir chat en WhatsApp">
                        <?php echo htmlspecialchars($ticket['client_phone']); ?>
                    </a>
                </span>
            </div>

            <div class="client-detail-box">
                <span class="detail-box-label"><i class="fa-solid fa-location-dot"></i> Ubicación</span>
                <span class="detail-box-value"><?php echo htmlspecialchars($ticket['ciudad'] ?: ($ticket['department'] ?: 'N/A')); ?></span>
            </div>

        </div>

    </div>

    <!-- Workspace Grid -->
    <div class="ticket-workspace">
        
        <!-- Left: Main Discussion -->
        <div class="workspace-main">
            
            <!-- Solicitud Original -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-dark);">
                        <?php echo htmlspecialchars($ticket['subject']); ?>
                    </h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">
                        <i class="fa-regular fa-clock"></i> Ingreso: <?php echo date('d/m/Y H:i', $timestamp); ?>
                    </span>
                </div>

                <div style="line-height: 1.7; color: var(--text-secondary); font-size: 0.95rem; white-space: pre-line;">
                    <?php echo htmlspecialchars($ticket['description']); ?>
                </div>

                <!-- Archivos Adjuntos -->
                <?php if (!empty($files)): ?>
                <div style="margin-top: 1.6rem; padding-top: 1.2rem; border-top: 1px solid #f1f5f9;">
                    <div class="detail-box-label" style="margin-bottom: 10px;">
                        <i class="fa-solid fa-paperclip"></i> Documentos y Evidencias Adjuntas (<?php echo count($files); ?>)
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <?php foreach($files as $f): ?>
                         <a href="download_file.php?id=<?php echo (int)$f['id']; ?>" target="_blank" rel="noopener noreferrer" class="file-chip">
                            <i class="fa-solid fa-file-arrow-down" style="color: var(--compu-green-dark);"></i>
                            <span><?php echo htmlspecialchars($f['file_name']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Historial de Conversación / Timeline -->
            <div class="timeline-container">
                <div style="text-align: center; position: relative; margin: 0.5rem 0;">
                    <hr style="border: 0; border-top: 1px solid #e2e8f0;">
                    <span style="background: var(--main-bg); position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 0 15px; font-size: 0.72rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                        Historial de Respuestas & Gestión
                    </span>
                </div>

                <?php foreach($replies as $reply): ?>
                <div class="bubble <?php echo $reply['user_id'] ? 'agent' : 'client'; ?> <?php echo $reply['is_internal'] ? 'internal' : ''; ?>">
                    <div class="bubble-meta">
                        <strong>
                            <?php if ($reply['is_internal']): ?>
                                <span style="color: #b45309;"><i class="fa-solid fa-lock"></i> Nota Interna Privada</span>
                            <?php elseif ($reply['user_id']): ?>
                                <span style="color: var(--compu-green-dark);"><i class="fa-solid fa-headset"></i> <?php echo htmlspecialchars($reply['user_name'] ?? 'Agente'); ?> (Respuesta Oficial)</span>
                            <?php else: ?>
                                <span style="color: var(--text-dark);"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($ticket['client_name']); ?></span>
                            <?php endif; ?>
                        </strong>
                        <span><?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></span>
                    </div>
                    <div style="line-height: 1.6; font-size: 0.92rem; color: var(--text-secondary);">
                        <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Caja de Respuesta -->
            <div class="reply-box-card">
                <div class="card-header-flex" style="margin-bottom: 1rem;">
                    <h4 style="font-size: 1.05rem; font-weight: 800; color: var(--text-dark);">
                        <i class="fa-solid fa-reply" style="color: var(--compu-green-dark);"></i> Redactar Respuesta al Cliente
                    </h4>
                </div>

                <!-- Aviso de Envío Automático de Correo -->
                <div id="emailNotice" class="email-dispatch-notice">
                    <i class="fa-solid fa-paper-plane" style="font-size: 1.1rem;"></i>
                    <span>Al enviar esta respuesta, se despachará automáticamente un correo electrónico oficial a <strong style="text-decoration: underline;"><?php echo htmlspecialchars($ticket['client_email']); ?></strong>.</span>
                </div>

                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <div style="margin-bottom: 1rem;">
                        <textarea name="message" class="form-control" style="min-height: 120px; border-color: #cbd5e1; resize: vertical; line-height: 1.6;" placeholder="Escribe aquí tu respuesta para resolver o dar seguimiento a este requerimiento..." required></textarea>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px; background: #fffbe6; padding: 8px 14px; border-radius: var(--radius-sm); border: 1px solid #fef08a;">
                            <input type="checkbox" name="is_internal" id="is_internal" onchange="toggleNotice(this)" style="width: 16px; height: 16px; cursor: pointer; accent-color: #d97706;">
                            <label for="is_internal" style="font-size: 0.82rem; cursor: pointer; font-weight: 700; color: #b45309; margin: 0;">Marcar como Nota Interna Privada (No se envía correo)</label>
                        </div>
                        <button type="submit" name="send_reply" class="btn btn-primary" style="padding: 10px 22px;">
                            <i class="fa-solid fa-paper-plane"></i> Enviar Respuesta
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Right: Actions & Sidebar Details -->
        <div class="workspace-sidebar">
            <div class="card" style="padding: 1.4rem;">
                <h4 class="detail-box-label" style="margin-bottom: 1rem;"><i class="fa-solid fa-sliders"></i> Control de Estado</h4>
                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <select name="status" class="form-control" style="margin-bottom: 1rem; font-weight: 700;">
                        <option value="Abierto" <?php echo $ticket['status'] == 'Abierto' ? 'selected' : ''; ?>>🟢 Abierto (Pendiente)</option>
                        <option value="En Proceso" <?php echo $ticket['status'] == 'En Proceso' ? 'selected' : ''; ?>>🟡 En Proceso</option>
                        <option value="Resuelto" <?php echo $ticket['status'] == 'Resuelto' ? 'selected' : ''; ?>>✅ Resuelto</option>
                        <option value="Cerrado" <?php echo $ticket['status'] == 'Cerrado' ? 'selected' : ''; ?>>⚪ Cerrado</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-outline" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-arrows-rotate"></i> Actualizar Estado
                    </button>
                </form>
            </div>

            <div class="card" style="padding: 1.4rem;">
                <h4 class="detail-box-label" style="margin-bottom: 1rem;"><i class="fa-solid fa-bolt"></i> Acciones Rápidas</h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="mailto:<?php echo htmlspecialchars($ticket['client_email']); ?>" class="btn btn-outline" style="font-size: 0.82rem; justify-content: flex-start;">
                        <i class="fa-solid fa-envelope" style="color: #0284c7;"></i> Enviar Correo Directo
                    </a>
                    <a href="https://wa.me/<?php echo $wa_phone; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="font-size: 0.82rem; justify-content: flex-start;">
                        <i class="fa-brands fa-whatsapp" style="color: #16a34a;"></i> Contactar por WhatsApp
                    </a>
                    <a href="tickets.php?search=<?php echo urlencode($ticket['client_email']); ?>" class="btn btn-outline" style="font-size: 0.82rem; justify-content: flex-start;">
                        <i class="fa-solid fa-clock-rotate-left"></i> Historial del Cliente
                    </a>
                </div>
            </div>
        </div>

    </div>

<?php include 'layouts/footer.php'; ?>

<script>
function toggleNotice(checkbox) {
    const notice = document.getElementById('emailNotice');
    notice.classList.toggle('is-internal', checkbox.checked);
    if (checkbox.checked) {
        notice.style.background = '#fffbe6';
        notice.style.borderColor = '#fef08a';
        notice.style.color = '#b45309';
        notice.innerHTML = '<i class="fa-solid fa-lock"></i> <span><strong>Nota privada seleccionada:</strong> Este mensaje se guardará internamente para el equipo de soporte y <strong>NO se enviará por correo</strong>.</span>';
    } else {
        notice.style.background = '#f0f9ff';
        notice.style.borderColor = '#bae6fd';
        notice.style.color = '#0369a1';
        notice.innerHTML = '<i class="fa-solid fa-paper-plane" style="font-size: 1.1rem;"></i> <span>Al enviar esta respuesta, se despachará automáticamente un correo electrónico oficial a <strong style="text-decoration: underline;"><?php echo htmlspecialchars($ticket['client_email']); ?></strong>.</span>';
    }
}
</script>
