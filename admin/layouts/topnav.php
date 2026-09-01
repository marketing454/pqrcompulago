<header class="top-nav">
    <div class="top-nav-left">
        <div class="top-title">
            <?php echo htmlspecialchars($page_title ?? 'Panel Administrativo'); ?>
        </div>
    </div>
    
    <div class="top-nav-right">
        <div class="system-pill">
            <span class="presence-dot"></span>
            <span>Sistema Operativo</span>
        </div>

        <div class="user-chip">
            <div class="avatar-circle" style="width: 26px; height: 26px; font-size: 0.75rem;">
                <?php echo htmlspecialchars(strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Agente'); ?></span>
            <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; background: #f1f5f9; padding: 2px 7px; border-radius: 4px; border: 1px solid #e2e8f0;">
                <?php
                $role_names = [
                    'agent' => 'Agente de Soporte',
                    'admin' => 'Administrador',
                    'superadmin' => 'Super Administrador'
                ];
                echo htmlspecialchars($role_names[$_SESSION['admin_role'] ?? ''] ?? 'Administrador');
                ?>
            </span>
        </div>
    </div>
</header>
<div class="content-body">
