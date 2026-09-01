<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$page_title = "Configuración del Sistema";
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }

    $success = "La configuración no se modifica desde esta pantalla todavía.";
}

include 'layouts/head.php';
include 'layouts/sidebar.php';
?>

<main class="main-content">
    <?php include 'layouts/topnav.php'; ?>

    <?php if ($success): ?>
        <div class="card" style="background: #f0fdf4; color: #166534; border: 1.5px solid #bbf7d0; padding: 12px 18px; margin-bottom: 1.5rem;">
            <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        
        <!-- Tarjeta Parámetros Generales -->
        <div class="card">
            <div class="card-header-flex">
                <h3 class="card-header-title">Parámetros de la Plataforma</h3>
                <span class="system-pill" style="font-size: 0.72rem;">General</span>
            </div>
            <form method="POST">
                <?php echo csrf_input(); ?>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Nombre Institucional</label>
                    <input type="text" class="form-control" value="Compulago S.A.S. - Sistema de PQRS" readonly>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>URL del Sitio Web</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Límite Horas SLA (Alerta de Vencimiento)</label>
                    <input type="number" class="form-control" value="48">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Ajustes
                </button>
            </form>
        </div>

        <!-- Tarjeta Integraciones -->
        <div class="card">
            <div class="card-header-flex">
                <h3 class="card-header-title">Canales Omnicanal (Email & WhatsApp)</h3>
                <span class="system-pill" style="font-size: 0.72rem;">Integraciones</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1rem; border-radius: var(--radius-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <strong style="font-size: 0.9rem;"><i class="fa-solid fa-envelope" style="color: var(--primary);"></i> Servidor SMTP</strong>
                        <span class="status-badge bg-resolved">Configurado</span>
                    </div>
                     <p style="font-size: 0.78rem; color: var(--text-muted);">Host: <?php echo htmlspecialchars(SMTP_HOST, ENT_QUOTES, 'UTF-8'); ?> (Puerto: <?php echo (int)SMTP_PORT; ?>)</p>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1rem; border-radius: var(--radius-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <strong style="font-size: 0.9rem;"><i class="fa-brands fa-whatsapp" style="color: #16a34a;"></i> Meta WhatsApp Cloud API</strong>
                        <span class="status-badge bg-process">Listo para Token</span>
                    </div>
                    <p style="font-size: 0.78rem; color: var(--text-muted);">Envío de confirmaciones automáticas de radicado y respuestas al cliente.</p>
                </div>
            </div>
        </div>

    </div>

<?php include 'layouts/footer.php'; ?>
