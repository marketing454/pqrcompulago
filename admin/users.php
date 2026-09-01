<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin('superadmin');

$page_title = "Gestión de Agentes & Usuarios";
$error = '';
$success = '';

// Procesar Acciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    
    // Crear/Editar Usuario
    if ($action === 'save') {
        $id_value = $_POST['id'] ?? null;
        $id = $id_value === null || $id_value === '' ? null : filter_var($id_value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $name_value = $_POST['name'] ?? '';
        $email_value = $_POST['email'] ?? '';
        $password_value = $_POST['password'] ?? '';
        $name = is_string($name_value) ? trim($name_value) : '';
        $email = is_string($email_value) ? strtolower(trim($email_value)) : '';
        $role = is_string($_POST['role'] ?? null) ? $_POST['role'] : '';
        $password = is_string($password_value) ? $password_value : '';

        if (
            $id === false
            ||
            $name === ''
            || mb_strlen($name) > 100
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !in_array($role, ['agent', 'admin', 'superadmin'], true)
            || ($password !== '' && mb_strlen($password) < 12)
        ) {
            $error = 'Verifica nombre, correo, rol y una contraseña de al menos 12 caracteres.';
            $id = null;
        }

        if ($error === '') {
            try {
                if ($id !== null) {
                    if ($password !== '') {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('UPDATE users_admin SET name = ?, email = ?, role = ?, password = ? WHERE id = ?');
                        $stmt->execute([$name, $email, $role, $hashed_password, $id]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE users_admin SET name = ?, email = ?, role = ? WHERE id = ?');
                        $stmt->execute([$name, $email, $role, $id]);
                    }
                    $success = "Usuario actualizado correctamente.";
                } elseif ($password !== '') {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO users_admin (name, email, password, role) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$name, $email, $hashed_password, $role]);
                    $success = "Nuevo usuario creado exitosamente.";
                } else {
                    $error = "Por favor completa todos los campos para crear un agente.";
                }
            } catch (PDOException $e) {
                error_log('Admin user operation failed: ' . $e->getMessage());
                $error = 'No fue posible guardar el usuario. Verifica que el correo no esté registrado.';
            }
        }
    }

    // Eliminar Usuario
    if ($action === 'delete') {
        $id_value = $_POST['id'] ?? null;
        $id = filter_var($id_value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id && $id != $_SESSION['admin_id']) {
            $stmt = $pdo->prepare("DELETE FROM users_admin WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Usuario eliminado del sistema.";
        } else {
            $error = "No puedes eliminar tu propia cuenta en sesión.";
        }
    }
}

// Obtener todos los usuarios
$users = $pdo->query("SELECT * FROM users_admin ORDER BY role DESC, name ASC")->fetchAll();

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
    
    <?php if ($error): ?>
        <div class="card" style="background: #fef2f2; color: #991b1b; border: 1.5px solid #fecaca; padding: 12px 18px; margin-bottom: 1.5rem;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 1.4rem 1.8rem;">
        <div class="card-header-flex" style="margin-bottom: 0;">
            <div>
                <h3 class="card-header-title">Agentes & Administradores</h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">Gestión de accesos y roles autorizados para el sistema de PQRS.</p>
            </div>
            <button class="btn btn-primary" onclick="openUserModal()">
                <i class="fa-solid fa-user-plus"></i> Registrar Nuevo Usuario
            </button>
        </div>
    </div>

    <div class="card-table">
        <table>
            <thead>
                <tr>
                    <th>Agente</th>
                    <th>Correo Electrónico</th>
                    <th>Rol de Acceso</th>
                    <th>Fecha de Alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="avatar-circle">
                                <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                            </div>
                            <strong style="color: var(--text-dark);"><?php echo htmlspecialchars($u['name']); ?></strong>
                        </div>
                    </td>
                    <td style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <?php
                        $role_labels = [
                            'agent' => ['🎧 Agente de Soporte', 'bg-process'],
                            'admin' => ['👔 Administrador', 'bg-resolved'],
                            'superadmin' => ['🛡️ Super Administrador', 'bg-open']
                        ];
                        $role_display = $role_labels[$u['role']] ?? ['Rol no reconocido', 'bg-danger'];
                        ?>
                        <span class="status-badge <?php echo $role_display[1]; ?>" style="border-radius: 4px;">
                            <?php echo $role_display[0]; ?>
                        </span>
                    </td>
                    <td style="font-size: 0.82rem; color: var(--text-muted);"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                            <button type="submit" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.75rem; color: var(--danger); border-color: #fecaca;">
                                <i class="fa-solid fa-trash-can"></i> Eliminar
                            </button>
                        </form>
                        <?php else: ?>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">(Sesión Actual)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal 21st.dev Style -->
    <div id="userModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:100; align-items:center; justify-content:center;">
        <div class="card" style="max-width:480px; width:100%; margin:0 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <div class="card-header-flex">
                <h3 class="card-header-title">Registrar Nuevo Usuario</h3>
                <button type="button" onclick="closeUserModal()" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--text-muted);">&times;</button>
            </div>
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="save">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Nombre Completo</label>
                    <input type="text" name="name" class="form-control" required placeholder="Ej: Juan Pérez">
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" class="form-control" required placeholder="juan.perez@compulago.com">
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="form-control" required placeholder="Contraseña de acceso">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Rol</label>
                    <select name="role" class="form-control">
                        <option value="agent">🎧 Agente de Soporte</option>
                        <option value="admin">👔 Administrador</option>
                        <option value="superadmin">🛡️ Super Administrador</option>
                    </select>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="closeUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>

<?php include 'layouts/footer.php'; ?>

<script>
function openUserModal() {
    document.getElementById('userModal').style.display = 'flex';
}
function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}
</script>
