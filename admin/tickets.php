<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$page_title = "Listado de Tickets PQRS";

// Filtros
$status_filter = trim($_GET['status'] ?? '');
$type_filter = trim($_GET['type'] ?? '');
$search_query = trim($_GET['search'] ?? '');
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

$query = "SELECT * FROM tickets WHERE 1=1";
$params = [];

if ($status_filter !== '') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}
if ($type_filter !== '') {
    $query .= " AND type = ?";
    $params[] = $type_filter;
}
if ($search_query !== '') {
    $query .= " AND (radicado LIKE ? OR client_name LIKE ? OR client_email LIKE ? OR client_document LIKE ? OR subject LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Función para renderizar filas de tickets (Estado en 2da columna, Radicado clickeable)
function renderTicketRows($tickets) {
    ob_start();
    if (!empty($tickets)):
        foreach($tickets as $ticket): 
            $status_class = 'bg-open';
            if ($ticket['status'] == 'En Proceso') $status_class = 'bg-process';
            elseif ($ticket['status'] == 'Resuelto') $status_class = 'bg-resolved';
            elseif ($ticket['status'] == 'Cerrado') $status_class = 'bg-danger';
        ?>
        <tr>
            <!-- 1. Radicado (Clickeable a la gestión) -->
            <td style="white-space: nowrap !important;">
                <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="radicado-link" title="Abrir gestión de <?php echo htmlspecialchars($ticket['radicado']); ?>">
                    <span class="radicado-code-pill">
                        <?php echo htmlspecialchars($ticket['radicado']); ?>
                    </span>
                </a>
            </td>

            <!-- 2. Estado (2do lugar permanente) -->
            <td style="white-space: nowrap !important;">
                <span class="status-badge <?php echo $status_class; ?>">
                    <?php if ($ticket['status'] == 'Abierto'): ?>
                        <span class="pulse-dot-red"></span>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($ticket['status']); ?>
                </span>
            </td>

            <!-- 3. Cliente -->
            <td>
                <div style="display: flex; align-items: center; gap: 8px; max-width: 190px;">
                    <div class="avatar-circle" style="width: 30px; height: 30px; font-size: 0.78rem;">
                        <?php echo strtoupper(substr($ticket['client_name'], 0, 1)); ?>
                    </div>
                    <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <strong style="color: var(--text-dark); font-size: 0.86rem; display: block; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($ticket['client_name']); ?>"><?php echo htmlspecialchars($ticket['client_name']); ?></strong>
                        <span style="font-size: 0.72rem; color: var(--text-muted); display: block; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($ticket['client_email']); ?>"><?php echo htmlspecialchars($ticket['client_email']); ?></span>
                    </div>
                </div>
            </td>

            <!-- 4. Tipo -->
            <td style="white-space: nowrap !important;">
                <span class="type-badge-pill" style="font-size: 0.78rem; padding: 3px 10px;">
                    <?php echo htmlspecialchars($ticket['type']); ?>
                </span>
            </td>

            <!-- 5. Ubicación -->
            <td style="white-space: nowrap !important; font-size: 0.82rem; color: var(--text-secondary);">
                <?php echo htmlspecialchars($ticket['ciudad'] ?: ($ticket['department'] ?: 'N/A')); ?>
            </td>

            <!-- 6. Asunto / Requerimiento (Acotado) -->
            <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.84rem;" title="<?php echo htmlspecialchars($ticket['subject']); ?>">
                <?php echo htmlspecialchars($ticket['subject']); ?>
            </td>

            <!-- 7. Fecha -->
            <td style="white-space: nowrap !important; font-size: 0.78rem; color: var(--text-muted);">
                <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>
            </td>

            <!-- 8. Acción (Icono de Gestión Directo) -->
            <td style="text-align: center; white-space: nowrap !important;">
                <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-action-icon" title="Gestionar ticket <?php echo htmlspecialchars($ticket['radicado']); ?>">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            </td>
        </tr>
        <?php endforeach;
    else: ?>
        <tr>
            <td colspan="8" style="text-align: center; padding: 3.5rem 1rem; color: var(--text-muted);">
                <i class="fa-solid fa-inbox" style="font-size: 2.2rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                <div style="font-weight: 700; font-size: 1rem; color: var(--text-dark); margin-bottom: 4px;">No se encontraron requerimientos</div>
                <div style="font-size: 0.84rem;">Intenta ajustando los filtros de búsqueda o el estado.</div>
            </td>
        </tr>
    <?php endif;
    return ob_get_clean();
}

// Si la petición es por AJAX, devolver JSON y salir
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => count($tickets),
        'html' => renderTicketRows($tickets)
    ]);
    exit;
}

$tipos_disponibles = [
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

include 'layouts/head.php';
include 'layouts/sidebar.php';
?>

<main class="main-content">
    <?php include 'layouts/topnav.php'; ?>

    <div class="card" style="padding: 1.2rem 1.6rem;">
        <form id="filtersForm" class="filters-bar" onsubmit="event.preventDefault(); applyFilters();" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 240px; position: relative;">
                <input type="text" id="searchInput" name="search" class="form-control" placeholder="🔍 Buscar por radicado, cliente, documento o asunto..." value="<?php echo htmlspecialchars($search_query); ?>" autocomplete="off">
                <span id="searchSpinner" style="display: none; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--compu-green-dark); font-size: 0.85rem;">
                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                </span>
            </div>
            
            <div style="width: auto;">
                <select id="statusSelect" name="status" class="form-control" style="width: auto; min-width: 175px;">
                    <option value="">Todos los Estados</option>
                    <option value="Abierto" <?php echo $status_filter == 'Abierto' ? 'selected' : ''; ?>>🔴 Abierto (Pendiente)</option>
                    <option value="En Proceso" <?php echo $status_filter == 'En Proceso' ? 'selected' : ''; ?>>🟡 En Proceso</option>
                    <option value="Resuelto" <?php echo $status_filter == 'Resuelto' ? 'selected' : ''; ?>>✅ Resuelto</option>
                    <option value="Cerrado" <?php echo $status_filter == 'Cerrado' ? 'selected' : ''; ?>>⚪ Cerrado</option>
                </select>
            </div>

            <div style="width: auto;">
                <select id="typeSelect" name="type" class="form-control" style="width: auto; min-width: 195px;">
                    <option value="">Todos los Tipos</option>
                    <?php foreach ($tipos_disponibles as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $type_filter == $t ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="button" id="clearBtn" class="btn btn-outline" onclick="resetFilters()" style="<?php echo ($status_filter || $type_filter || $search_query) ? '' : 'display: none;'; ?> padding: 9px 14px;">
                <i class="fa-solid fa-rotate-left"></i> Limpiar
            </button>

            <span id="resultsCountBadge" class="system-pill" style="font-size: 0.76rem; margin-left: auto;">
                Mostrando <strong id="resultsCount" style="color: var(--compu-green-dark); margin: 0 3px;"><?php echo count($tickets); ?></strong> tickets
            </span>
        </form>
    </div>

    <div class="card-table">
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 140px; white-space: nowrap !important;">Radicado</th>
                        <th style="width: 110px; white-space: nowrap !important;">Estado</th>
                        <th style="width: 190px;">Cliente</th>
                        <th style="width: 130px; white-space: nowrap !important;">Tipo</th>
                        <th style="width: 110px; white-space: nowrap !important;">Ubicación</th>
                        <th style="width: 200px;">Asunto / Requerimiento</th>
                        <th style="width: 110px; white-space: nowrap !important;">Fecha</th>
                        <th style="width: 50px; text-align: center; white-space: nowrap !important;">Acción</th>
                    </tr>
                </thead>
                <tbody id="ticketsTbody" style="transition: opacity 0.2s ease;">
                    <?php echo renderTicketRows($tickets); ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include 'layouts/footer.php'; ?>

<script>
    let debounceTimer = null;

    const searchInput = document.getElementById('searchInput');
    const statusSelect = document.getElementById('statusSelect');
    const typeSelect = document.getElementById('typeSelect');
    const clearBtn = document.getElementById('clearBtn');
    const ticketsTbody = document.getElementById('ticketsTbody');
    const resultsCount = document.getElementById('resultsCount');
    const searchSpinner = document.getElementById('searchSpinner');

    // Función principal para aplicar filtros por AJAX
    function applyFilters() {
        const search = searchInput.value.trim();
        const status = statusSelect.value;
        const type = typeSelect.value;

        // Mostrar / Ocultar botón de limpiar
        if (search !== '' || status !== '' || type !== '') {
            clearBtn.style.display = 'inline-flex';
        } else {
            clearBtn.style.display = 'none';
        }

        // Mostrar spinner y atenuar tabla
        searchSpinner.style.display = 'inline-block';
        ticketsTbody.style.opacity = '0.5';
        if (window.PqrLoading) {
            window.PqrLoading.show('Actualizando tickets...');
        }

        const params = new URLSearchParams({
            ajax: '1',
            search: search,
            status: status,
            type: type
        });

        // Actualizar URL sin recargar la página
        const newUrl = `${window.location.pathname}?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&type=${encodeURIComponent(type)}`;
        window.history.replaceState({}, '', newUrl);

        fetch(`tickets.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    ticketsTbody.innerHTML = data.html;
                    resultsCount.textContent = data.count;
                }
            })
            .catch(err => {
                console.error('Error al filtrar tickets:', err);
            })
            .finally(() => {
                searchSpinner.style.display = 'none';
                ticketsTbody.style.opacity = '1';
                if (window.PqrLoading) {
                    window.PqrLoading.hide();
                }
            });
    }

    // Event listener para buscador con Debounce (280ms)
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            applyFilters();
        }, 280);
    });

    // Event listeners para cambios inmediatos en los selectores
    statusSelect.addEventListener('change', applyFilters);
    typeSelect.addEventListener('change', applyFilters);

    // Función para restablecer todos los filtros
    function resetFilters() {
        searchInput.value = '';
        statusSelect.value = '';
        typeSelect.value = '';
        applyFilters();
    }
</script>
