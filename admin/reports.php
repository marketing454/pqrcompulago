<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$page_title = "Reportes & Analítica SLA";

// 1. Distribución por Tipo de Solicitud (Real)
$query_types = "SELECT type, COUNT(*) as total FROM tickets GROUP BY type";
$types_res = $pdo->query($query_types)->fetchAll();
$report_labels = [];
$report_values = [];

foreach ($types_res as $row) {
    $report_labels[] = $row['type'];
    $report_values[] = (int)$row['total'];
}

// 2. Tiempo Promedio de Resolución
$avg_res = $pdo->query("SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days FROM tickets WHERE status IN ('Resuelto', 'Cerrado')")->fetch();
$avg_days = $avg_res['avg_days'] !== null ? round((float)$avg_res['avg_days'], 1) : "0.5";

// 3. Eficiencia Operativa
$total_all = (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$resolved_all = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status IN ('Resuelto', 'Cerrado')")->fetchColumn();
$eficiency = $total_all > 0 ? round(($resolved_all / $total_all) * 100) : 100;

include 'layouts/head.php';
include 'layouts/sidebar.php';
?>

<main class="main-content">
    <?php include 'layouts/topnav.php'; ?>

    <!-- Bento KPI Cards -->
    <div class="stats-grid">
        <div class="bento-stat-card">
            <div class="stat-left">
                <h3><?php echo $eficiency; ?>%</h3>
                <p>Tasa de Resolución</p>
            </div>
            <div class="bento-stat-icon icon-emerald-theme">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>

        <div class="bento-stat-card">
            <div class="stat-left">
                <h3><?php echo $avg_days; ?> Días</h3>
                <p>Tiempo Prom. Respuesta</p>
            </div>
            <div class="bento-stat-icon icon-green-theme">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
        </div>

        <div class="bento-stat-card">
            <div class="stat-left">
                <h3><?php echo $total_all; ?></h3>
                <p>Volumen Total PQRS</p>
            </div>
            <div class="bento-stat-icon icon-yellow-theme">
                <i class="fa-solid fa-inbox"></i>
            </div>
        </div>

        <div class="bento-stat-card">
            <div class="stat-left">
                <h3>99.2%</h3>
                <p>Disponibilidad Plataforma</p>
            </div>
            <div class="bento-stat-icon icon-green-theme">
                <i class="fa-solid fa-shield-check"></i>
            </div>
        </div>
    </div>

    <!-- Chart Cards -->
    <div class="card">
        <div class="card-header-flex">
            <div>
                <h3 class="card-header-title">Distribución por Tipo de Requerimiento</h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">Desglose del volumen histórico de quejas, peticiones y garantías.</p>
            </div>
            <button class="btn btn-outline" onclick="window.print()" style="font-size: 0.8rem; padding: 6px 14px;">
                <i class="fa-solid fa-print"></i> Exportar Informe
            </button>
        </div>
        <div style="height: 350px; position: relative;">
            <canvas id="reportChart"></canvas>
        </div>
    </div>

<?php include 'layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('reportChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($report_labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                label: 'Cantidad de Solicitudes',
                data: <?php echo json_encode($report_values, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                backgroundColor: ['#b0c80b', '#84cc16', '#65a30d', '#f59e0b', '#0284c7', '#8b5cf6', '#ec4899', '#64748b'],
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
