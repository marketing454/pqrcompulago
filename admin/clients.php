<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$page_title = "Directorio de Clientes";

$search = trim($_GET['search'] ?? '');
$query = "SELECT client_email, client_name, client_phone, client_document, MAX(created_at) as last_pqr, COUNT(*) as total_pqrs 
          FROM tickets WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (client_name LIKE ? OR client_email LIKE ? OR client_phone LIKE ? OR client_document LIKE ?)";
    $st = "%$search%";
    $params = [$st, $st, $st, $st];
}

$query .= " GROUP BY client_email, client_name, client_phone, client_document ORDER BY last_pqr DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll();

include 'layouts/head.php';
include 'layouts/sidebar.php';
?>

<main class="main-content">
    <?php include 'layouts/topnav.php'; ?>

    <div class="card" style="padding: 1.2rem 1.6rem;">
        <form method="GET" style="display: flex; gap: 12px; align-items: center;">
            <div style="flex: 1;">
                <input type="text" name="search" class="form-control" placeholder="🔍 Buscar cliente por nombre, correo, teléfono o documento..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">
                <i class="fa-solid fa-magnifying-glass"></i> Buscar
            </button>
            <?php if ($search): ?>
                <a href="clients.php" class="btn btn-outline">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card-table">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Documento</th>
                    <th>Contacto</th>
                    <th>Total PQRs</th>
                    <th>Última Solicitud</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($clients as $client): 
                    $wa_phone = preg_replace('/[^0-9]/', '', $client['client_phone']);
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="avatar-circle">
                                 <?php echo htmlspecialchars(strtoupper(substr($client['client_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div>
                                <strong style="color: var(--text-dark);"><?php echo htmlspecialchars($client['client_name']); ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($client['client_email']); ?></span>
                            </div>
                        </div>
                    </td>
                    <td style="font-size: 0.84rem; color: var(--text-secondary);">
                        <?php echo htmlspecialchars($client['client_document'] ?: '-'); ?>
                    </td>
                    <td>
                        <a href="https://wa.me/<?php echo $wa_phone; ?>" target="_blank" rel="noopener noreferrer" style="color: var(--compu-green-dark); text-decoration: none; font-weight: 600; font-size: 0.85rem;">
                            <i class="fa-brands fa-whatsapp"></i> <?php echo htmlspecialchars($client['client_phone']); ?>
                        </a>
                    </td>
                    <td>
                        <span class="status-badge bg-open" style="border-radius: 6px;">
                            <?php echo $client['total_pqrs']; ?> <?php echo $client['total_pqrs'] == 1 ? 'ticket' : 'tickets'; ?>
                        </span>
                    </td>
                    <td style="font-size: 0.82rem; color: var(--text-muted);">
                        <?php echo date('d/m/Y', strtotime($client['last_pqr'])); ?>
                    </td>
                    <td>
                        <a href="tickets.php?search=<?php echo urlencode($client['client_email']); ?>" class="btn btn-view" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fa-solid fa-clock-rotate-left"></i> Historial
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($clients)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            No hay clientes que coincidan con la búsqueda.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php include 'layouts/footer.php'; ?>
