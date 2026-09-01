<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$page_title = "Dashboard Operativo";

// Período seleccionado para las métricas y los gráficos.
$periods = [
    'all' => ['label' => 'Histórico', 'short' => 'Todo', 'icon' => 'fa-layer-group'],
    'today' => ['label' => 'Hoy', 'short' => 'Hoy', 'icon' => 'fa-calendar-day'],
    'yesterday' => ['label' => 'Ayer', 'short' => 'Ayer', 'icon' => 'fa-calendar-minus'],
    'week' => ['label' => 'Última semana', 'short' => '1 semana', 'icon' => 'fa-calendar-week'],
    'month' => ['label' => 'Último mes', 'short' => '1 mes', 'icon' => 'fa-calendar'],
    'quarter' => ['label' => 'Últimos 3 meses', 'short' => '3 meses', 'icon' => 'fa-calendar-days'],
    'half_year' => ['label' => 'Últimos 6 meses', 'short' => '6 meses', 'icon' => 'fa-calendar-days'],
    'year' => ['label' => 'Último año', 'short' => '1 año', 'icon' => 'fa-calendar-check'],
];

$requested_period = $_GET['period'] ?? 'all';
$period_key = is_string($requested_period) && isset($periods[$requested_period]) ? $requested_period : 'all';
$period = $periods[$period_key];

$today = new DateTimeImmutable('today');
$months_before = static function (int $months) use ($today): DateTimeImmutable {
    $target_month = $today->modify('first day of this month')->modify("-$months months");
    $day = min((int)$today->format('d'), (int)$target_month->format('t'));

    return $target_month->setDate(
        (int)$target_month->format('Y'),
        (int)$target_month->format('n'),
        $day
    );
};

$period_start = null;
$period_end = null;

switch ($period_key) {
    case 'today':
        $period_start = $today;
        $period_end = $today->modify('+1 day');
        break;
    case 'yesterday':
        $period_start = $today->modify('-1 day');
        $period_end = $today;
        break;
    case 'week':
        $period_start = $today->modify('-6 days');
        $period_end = $today->modify('+1 day');
        break;
    case 'month':
        $period_start = $months_before(1);
        $period_end = $today->modify('+1 day');
        break;
    case 'quarter':
        $period_start = $months_before(3);
        $period_end = $today->modify('+1 day');
        break;
    case 'half_year':
        $period_start = $months_before(6);
        $period_end = $today->modify('+1 day');
        break;
    case 'year':
        $period_start = $months_before(12);
        $period_end = $today->modify('+1 day');
        break;
}

$ticket_scope = '1=1';
$scope_params = [];
if ($period_start !== null && $period_end !== null) {
    $ticket_scope = 'created_at >= ? AND created_at < ?';
    $scope_params = [
        $period_start->format('Y-m-d H:i:s'),
        $period_end->format('Y-m-d H:i:s')
    ];
}

$count_tickets = static function (string $extra_scope = '', array $extra_params = []) use ($pdo, $ticket_scope, $scope_params): int {
    $where = $ticket_scope;
    if ($extra_scope !== '') {
        $where .= ' AND ' . $extra_scope;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE $where");
    $stmt->execute(array_merge($scope_params, $extra_params));
    return (int)$stmt->fetchColumn();
};

$total_tickets = $count_tickets();
$open_tickets = $count_tickets('status = ?', ['Abierto']);
$process_tickets = $count_tickets('status = ?', ['En Proceso']);
$resolved_tickets = $count_tickets('status IN (?, ?)', ['Resuelto', 'Cerrado']);
$expired_tickets = $count_tickets(
    'status IN (?, ?) AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)',
    ['Abierto', 'En Proceso']
);

// Agrupar el volumen según el período para mantener el gráfico legible.
$volume_granularity = 'month';
$volume_group = "DATE_FORMAT(created_at, '%Y-%m-01')";
if (in_array($period_key, ['today', 'yesterday', 'week', 'month'], true)) {
    $volume_granularity = 'day';
    $volume_group = 'DATE(created_at)';
} elseif (in_array($period_key, ['quarter', 'half_year'], true)) {
    $volume_granularity = 'week';
    $volume_group = 'DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY)';
}

$volume_stmt = $pdo->prepare("SELECT $volume_group AS bucket, COUNT(*) AS total FROM tickets WHERE $ticket_scope GROUP BY bucket ORDER BY bucket");
$volume_stmt->execute($scope_params);
$volume_counts = [];
foreach ($volume_stmt->fetchAll() as $volume_row) {
    $volume_counts[(string)$volume_row['bucket']] = (int)$volume_row['total'];
}

$month_names = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
$volume_labels = [];
$volume_data = [];

if ($period_start === null) {
    foreach ($volume_counts as $bucket => $total) {
        $bucket_date = DateTimeImmutable::createFromFormat('!Y-m-d', $bucket);
        if (!$bucket_date) {
            continue;
        }

        $volume_labels[] = $volume_granularity === 'month'
            ? $month_names[(int)$bucket_date->format('n')] . ' ' . $bucket_date->format('Y')
            : $bucket_date->format('d') . ' ' . $month_names[(int)$bucket_date->format('n')];
        $volume_data[] = $total;
    }
} elseif ($volume_granularity === 'day') {
    for ($cursor = $period_start; $cursor < $period_end; $cursor = $cursor->modify('+1 day')) {
        $bucket = $cursor->format('Y-m-d');
        $volume_labels[] = $cursor->format('d') . ' ' . $month_names[(int)$cursor->format('n')];
        $volume_data[] = $volume_counts[$bucket] ?? 0;
    }
} elseif ($volume_granularity === 'week') {
    $cursor = $period_start->modify('monday this week');
    while ($cursor < $period_end) {
        $bucket = $cursor->format('Y-m-d');
        $volume_labels[] = 'Sem. ' . $cursor->format('d') . ' ' . $month_names[(int)$cursor->format('n')];
        $volume_data[] = $volume_counts[$bucket] ?? 0;
        $cursor = $cursor->modify('+7 days');
    }
} else {
    $cursor = $period_start->modify('first day of this month');
    while ($cursor < $period_end) {
        $bucket = $cursor->format('Y-m-01');
        $volume_labels[] = $month_names[(int)$cursor->format('n')] . ' ' . $cursor->format('Y');
        $volume_data[] = $volume_counts[$bucket] ?? 0;
        $cursor = $cursor->modify('+1 month');
    }
}

// Obtener tickets recientes dentro del período seleccionado.
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE $ticket_scope ORDER BY created_at DESC LIMIT 5");
$stmt->execute($scope_params);
$recent_tickets = $stmt->fetchAll();

include 'layouts/head.php';
include 'layouts/sidebar.php';
?>

<style>
    .dashboard-period-filter {
        position: relative;
        z-index: 20;
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 1rem 1.2rem;
        margin-bottom: 1.8rem;
        box-shadow: var(--shadow-subtle);
    }

    .dashboard-period-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .dashboard-period-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-dark);
        font-size: 0.9rem;
        font-weight: 800;
    }

    .dashboard-period-title i {
        color: var(--compu-green-dark);
    }

    .dashboard-period-select {
        position: relative;
        min-width: 245px;
    }

    .dashboard-period-trigger {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 48px;
        padding: 7px 12px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        color: var(--text-secondary);
        background: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dashboard-period-trigger:hover,
    .dashboard-period-select.open .dashboard-period-trigger {
        border-color: var(--compu-green);
        background: var(--compu-green-soft);
        box-shadow: 0 0 0 3px rgba(176, 200, 11, 0.12);
    }

    .dashboard-period-trigger:focus-visible {
        outline: 3px solid rgba(176, 200, 11, 0.3);
        outline-offset: 2px;
    }

    .dashboard-period-trigger-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 9px;
        color: var(--compu-green-dark);
        background: #ffffff;
        flex-shrink: 0;
    }

    .dashboard-period-trigger-copy {
        display: flex;
        flex: 1;
        min-width: 0;
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }

    .dashboard-period-trigger-copy small {
        color: var(--text-muted);
        font-size: 0.66rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .dashboard-period-trigger-copy strong {
        overflow: hidden;
        color: var(--text-dark);
        font-size: 0.84rem;
        line-height: 1.35;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dashboard-period-trigger-chevron {
        color: var(--text-muted);
        transition: transform 0.2s ease;
    }

    .dashboard-period-select.open .dashboard-period-trigger-chevron {
        transform: rotate(180deg);
    }

    .dashboard-period-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: min(310px, calc(100vw - 40px));
        padding: 6px;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16), 0 4px 12px rgba(15, 23, 42, 0.08);
        opacity: 0;
        pointer-events: none;
        transform: translateY(-5px) scale(0.98);
        transform-origin: top right;
        transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s ease;
        visibility: hidden;
    }

    .dashboard-period-select.open .dashboard-period-menu {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0) scale(1);
        visibility: visible;
    }

    .dashboard-period-menu-option {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 9px 10px;
        border-radius: 9px;
        color: var(--text-secondary);
        text-decoration: none;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .dashboard-period-menu-option:hover,
    .dashboard-period-menu-option:focus-visible {
        outline: none;
        color: var(--compu-green-dark);
        background: var(--compu-green-soft);
    }

    .dashboard-period-menu-option > i:first-child {
        width: 20px;
        color: var(--compu-green-dark);
        text-align: center;
    }

    .dashboard-period-menu-copy {
        display: flex;
        flex: 1;
        min-width: 0;
        flex-direction: column;
    }

    .dashboard-period-menu-copy strong {
        font-size: 0.82rem;
        line-height: 1.3;
    }

    .dashboard-period-menu-copy small {
        color: var(--text-muted);
        font-size: 0.7rem;
        line-height: 1.3;
    }

    .dashboard-period-menu-option.active {
        color: var(--compu-green-dark);
        background: var(--compu-green-soft);
    }

    .dashboard-period-menu-option .period-check {
        opacity: 0;
        color: var(--compu-green-dark);
    }

    .dashboard-period-menu-option.active .period-check {
        opacity: 1;
    }

    @media (max-width: 700px) {
        .dashboard-period-heading {
            align-items: flex-start;
            flex-direction: column;
            gap: 4px;
        }

        .dashboard-period-select {
            width: 100%;
        }
    }
</style>

<main class="main-content">
    <?php include 'layouts/topnav.php'; ?>

    <section class="dashboard-period-filter" aria-label="Filtro de período del dashboard">
        <div class="dashboard-period-heading">
            <div class="dashboard-period-title">
                <i class="fa-solid fa-sliders"></i>
                <span>Período de análisis</span>
            </div>
            <div class="dashboard-period-select" data-period-select>
                <button type="button" class="dashboard-period-trigger" aria-haspopup="menu" aria-expanded="false" aria-controls="dashboardPeriodMenu">
                    <span class="dashboard-period-trigger-icon">
                        <i class="fa-solid <?php echo htmlspecialchars($period['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                    </span>
                    <span class="dashboard-period-trigger-copy">
                        <small>Período seleccionado</small>
                        <strong><?php echo htmlspecialchars($period['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </span>
                    <i class="fa-solid fa-chevron-down dashboard-period-trigger-chevron" aria-hidden="true"></i>
                </button>
                <div id="dashboardPeriodMenu" class="dashboard-period-menu" role="menu" aria-hidden="true">
                    <?php foreach ($periods as $key => $period_option): ?>
                        <a href="dashboard.php?period=<?php echo urlencode($key); ?>" class="dashboard-period-menu-option <?php echo $period_key === $key ? 'active' : ''; ?>" role="menuitem"<?php echo $period_key === $key ? ' aria-current="page"' : ''; ?>>
                            <i class="fa-solid <?php echo htmlspecialchars($period_option['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            <span class="dashboard-period-menu-copy">
                                <strong><?php echo htmlspecialchars($period_option['short'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars($period_option['label'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </span>
                            <i class="fa-solid fa-check period-check" aria-hidden="true"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 21st.dev Bento Grid de Métricas -->
    <div class="stats-grid">
        <div class="bento-stat-card">
            <div class="stat-left">
                <h3><?php echo $total_tickets; ?></h3>
                <p>Total Requerimientos</p>
            </div>
            <div class="bento-stat-icon icon-green-theme">
                <i class="fa-solid fa-folder-open"></i>
            </div>
        </div>

        <div class="bento-stat-card">
            <div class="stat-left">
                <h3><?php echo $open_tickets; ?></h3>
                <p>Abiertos (Pendientes)</p>
            </div>
            <div class="bento-stat-icon icon-yellow-theme">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>

        <div class="bento-stat-card">
            <div class="stat-left">
                <h3><?php echo $resolved_tickets; ?></h3>
                <p>Casos Resueltos</p>
            </div>
            <div class="bento-stat-icon icon-emerald-theme">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="bento-stat-card">
            <div class="stat-left">
                <h3><?php echo $expired_tickets; ?></h3>
                <p>Críticos / Vencidos (>48h)</p>
            </div>
            <div class="bento-stat-icon icon-red-theme">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    <!-- Gráficos Bento UI -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header-flex">
                <h3 class="card-header-title">Distribución por Estado</h3>
                <span class="system-pill" style="font-size: 0.72rem;"><?php echo htmlspecialchars($period['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div style="height: 260px; position: relative;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0;">
            <div class="card-header-flex">
                <h3 class="card-header-title">Volumen de PQRs (<?php echo htmlspecialchars($period['label'], ENT_QUOTES, 'UTF-8'); ?>)</h3>
                <span class="system-pill" style="font-size: 0.72rem;">Tendencia</span>
            </div>
            <div style="height: 260px; position: relative;">
                <canvas id="historyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabla de Solicitudes Recientes -->
    <div class="card">
        <div class="card-header-flex">
            <div>
                <h3 class="card-header-title">Solicitudes Recientes</h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">Últimos requerimientos ingresados a la plataforma.</p>
            </div>
            <a href="tickets.php" class="btn btn-outline" style="font-size: 0.8rem; padding: 6px 14px;">
                <span>Ver Todos</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="min-width: 165px; white-space: nowrap !important;">Radicado</th>
                        <th style="min-width: 120px; white-space: nowrap !important;">Estado</th>
                        <th style="min-width: 180px;">Cliente</th>
                        <th style="min-width: 130px; white-space: nowrap !important;">Tipo</th>
                        <th style="min-width: 110px; white-space: nowrap !important;">Fecha</th>
                        <th style="width: 50px; text-align: center; white-space: nowrap !important;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_tickets as $ticket): ?>
                    <tr>
                        <td class="nowrap-cell">
                            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="radicado-link" title="Gestionar ticket">
                                <span class="radicado-code-pill">
                                    <?php echo htmlspecialchars($ticket['radicado']); ?>
                                </span>
                            </a>
                        </td>
                        <td class="nowrap-cell">
                            <span class="status-badge <?php 
                                echo $ticket['status'] == 'Abierto' ? 'bg-open' : ($ticket['status'] == 'En Proceso' ? 'bg-process' : ($ticket['status'] == 'Resuelto' ? 'bg-resolved' : 'bg-danger')); 
                            ?>">
                                <?php if ($ticket['status'] == 'Abierto'): ?>
                                    <span class="pulse-dot-red"></span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($ticket['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px; max-width: 200px;">
                                <div class="avatar-circle" style="width: 30px; height: 30px; font-size: 0.78rem;">
                                     <?php echo htmlspecialchars(strtoupper(substr($ticket['client_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <strong style="color: var(--text-dark); font-size: 0.86rem; display: block; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($ticket['client_name']); ?>"><?php echo htmlspecialchars($ticket['client_name']); ?></strong>
                                    <span style="font-size: 0.72rem; color: var(--text-muted); display: block; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($ticket['client_email']); ?>"><?php echo htmlspecialchars($ticket['client_email']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="nowrap-cell">
                            <span class="type-badge-pill" style="font-size: 0.78rem; padding: 3px 10px;"><?php echo htmlspecialchars($ticket['type']); ?></span>
                        </td>
                        <td class="nowrap-cell" style="font-size: 0.78rem; color: var(--text-muted);">
                            <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>
                        </td>
                        <td class="nowrap-cell" style="text-align: center;">
                            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-action-icon" title="Gestionar ticket <?php echo htmlspecialchars($ticket['radicado']); ?>">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($recent_tickets)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">No hay tickets registrados aún.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include 'layouts/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Gráfico Doughnut de Estados
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Abiertos', 'En Proceso', 'Resueltos'],
            datasets: [{
                data: [<?php echo $open_tickets; ?>, <?php echo $process_tickets; ?>, <?php echo $resolved_tickets; ?>],
                backgroundColor: ['#b0c80b', '#f59e0b', '#16a34a'],
                borderWidth: 0,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', weight: 600 } } }
            },
            cutout: '70%'
        }
    });

    // 2. Gráfico Bar/Line de Histórico
    const ctxHistory = document.getElementById('historyChart').getContext('2d');
    new Chart(ctxHistory, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($volume_labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                label: 'Tickets',
                data: <?php echo json_encode($volume_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                backgroundColor: '#b0c80b',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>

<script>
    (() => {
        const periodSelect = document.querySelector('[data-period-select]');
        if (!periodSelect) return;

        const trigger = periodSelect.querySelector('.dashboard-period-trigger');
        const menu = periodSelect.querySelector('.dashboard-period-menu');
        const options = Array.from(menu.querySelectorAll('.dashboard-period-menu-option'));

        function setPeriodMenu(open) {
            periodSelect.classList.toggle('open', open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            menu.setAttribute('aria-hidden', open ? 'false' : 'true');
            options.forEach(option => {
                option.tabIndex = open ? 0 : -1;
            });
        }

        function focusOption(offset) {
            const currentIndex = options.indexOf(document.activeElement);
            const nextIndex = currentIndex < 0
                ? (offset > 0 ? 0 : options.length - 1)
                : (currentIndex + offset + options.length) % options.length;
            options[nextIndex].focus();
        }

        setPeriodMenu(false);

        trigger.addEventListener('click', () => {
            const isOpen = periodSelect.classList.contains('open');
            setPeriodMenu(!isOpen);
            if (!isOpen) {
                focusOption(1);
            }
        });

        menu.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                focusOption(event.key === 'ArrowDown' ? 1 : -1);
            } else if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                options[event.key === 'Home' ? 0 : options.length - 1].focus();
            } else if (event.key === 'Escape') {
                setPeriodMenu(false);
                trigger.focus();
            }
        });

        document.addEventListener('click', event => {
            if (!periodSelect.contains(event.target)) {
                setPeriodMenu(false);
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && periodSelect.classList.contains('open')) {
                setPeriodMenu(false);
                trigger.focus();
            }
        });
    })();
</script>
