<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Obtener conteo de tickets abiertos para la insignia
$open_count = 0;
if (isset($pdo)) {
    try {
        $open_count = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'Abierto'")->fetchColumn();
    } catch (Exception $e) {}
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="https://compulago.b-cdn.net/Logo-Compulago/SVG/SVG%20LOGO%20BLACK.svg" alt="Compulago" style="height: 22px; max-width: 140px; filter: brightness(0) invert(1);">
        </div>
        <span class="sidebar-badge" style="font-size: 0.7rem; padding: 2px 7px;">PQR v2.5</span>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-category-label">Operaciones</div>
        
        <a href="dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <div class="menu-item-left">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </div>
        </a>

        <a href="tickets.php" class="menu-item <?php echo $current_page == 'tickets.php' || $current_page == 'view_ticket.php' ? 'active' : ''; ?>">
            <div class="menu-item-left">
                <i class="fa-solid fa-ticket-simple"></i>
                <span>Tickets PQRS</span>
            </div>
            <?php if ($open_count > 0): ?>
                <span style="background: var(--danger); color: white; font-size: 0.72rem; padding: 2px 7px; border-radius: 9999px; font-weight: 800;">
                    <?php echo $open_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="clients.php" class="menu-item <?php echo $current_page == 'clients.php' ? 'active' : ''; ?>">
            <div class="menu-item-left">
                <i class="fa-solid fa-users"></i>
                <span>Directorio Clientes</span>
            </div>
        </a>

        <a href="reports.php" class="menu-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <div class="menu-item-left">
                <i class="fa-solid fa-chart-simple"></i>
                <span>Reportes & SLA</span>
            </div>
        </a>

        <div class="menu-category-label">Administración</div>
        
        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin'): ?>
        <a href="users.php" class="menu-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <div class="menu-item-left">
                <i class="fa-solid fa-user-gear"></i>
                <span>Agentes & Roles</span>
            </div>
        </a>
        <?php endif; ?>

        <a href="settings.php" class="menu-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <div class="menu-item-left">
                <i class="fa-solid fa-sliders"></i>
                <span>Configuración</span>
            </div>
        </a>
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="logout.php" style="margin: 0;">
            <?php echo csrf_input(); ?>
            <button type="submit" class="menu-item" style="width: 100%; color: #f87171; background: transparent; border: 0; font-family: inherit; text-align: left;">
            <div class="menu-item-left">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Cerrar Sesión</span>
            </div>
            </button>
        </form>
    </div>
</aside>
