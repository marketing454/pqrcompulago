<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Solicitud no válida. Inténtalo de nuevo.';
    } else {
        $email_value = $_POST['email'] ?? '';
        $password_value = $_POST['password'] ?? '';
        $email = is_string($email_value) ? strtolower(trim($email_value)) : '';
        $password = is_string($password_value) ? $password_value : '';

        $ip_allowed = rate_limit('admin-login-ip', client_ip(), 5, 900);
        $account_allowed = rate_limit('admin-login-account', $email ?: 'unknown', 5, 900);

        if (!$ip_allowed || !$account_allowed) {
            $error = 'Demasiados intentos. Inténtalo de nuevo más tarde.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $error = 'Credenciales inválidas. Inténtalo de nuevo.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users_admin WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    reset_rate_limit('admin-login-ip', client_ip());
                    reset_rate_limit('admin-login-account', $email);
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int)$user['id'];
                    $_SESSION['admin_name'] = $user['name'];
                    $_SESSION['admin_role'] = $user['role'];
                    header('Location: dashboard.php');
                    exit;
                }

                $error = 'Credenciales inválidas. Inténtalo de nuevo.';
            } catch (Throwable $e) {
                error_log('Admin login failed: ' . $e->getMessage());
                $error = 'No fue posible iniciar sesión. Inténtalo de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/loading.css">
    <script src="../public/js/loading.js" defer></script>
    <style>
        :root {
            --primary: #b0c80b;
            --primary-dark: #769600;
            --accent: #b0c80b;
            --bg-dark: #0f172a;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --error: #ef4444;
            --glass-bg: rgba(255, 255, 255, 0.95);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(6, 106, 171, 0.15) 0px, transparent 50%), 
                radial-gradient(at 100% 100%, rgba(149, 193, 30, 0.1) 0px, transparent 50%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 3rem 2.5rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .logo-box {
            margin-bottom: 2rem;
        }

        .logo-box img {
            max-width: 200px;
            height: auto;
        }

        .login-card h2 {
            font-family: 'Poppins', sans-serif;
            color: var(--text-main);
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .login-card p.subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 2.5rem;
        }

        .error-alert {
            background: #fee2e2;
            color: var(--error);
            padding: 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #fecaca;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-group {
            text-align: left;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.6rem;
            margin-left: 4px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border-radius: 14px;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s;
            color: var(--text-main);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(6, 106, 171, 0.1);
        }

        .form-control:focus + i {
            color: var(--primary);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
            box-shadow: 0 10px 15px -3px rgba(6, 106, 171, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 20px -5px rgba(6, 106, 171, 0.4);
            filter: brightness(1.1);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer-text {
            margin-top: 2.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-box">
                <img src="https://compulago.b-cdn.net/Logo-Compulago/SVG/SVG%20LOGO%20BLACK.svg" alt="Compulago Logo" style="max-width: 220px; height: auto;">
            </div>
            <h2>Gestión PQRS</h2>
            <p class="subtitle">Ingresa tus credenciales para administrar.</p>

            <?php if ($error): ?>
                <div class="error-alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <?php echo csrf_input(); ?>
                <div class="form-group">
                    <label for="email">Correo Institucional</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" id="email" class="form-control" required placeholder="admin@compulago.com" autocomplete="username" autofocus>
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••" autocomplete="current-password">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Acceder al Panel <i class="fa-solid fa-arrow-right-to-bracket" style="margin-left: 8px;"></i>
                </button>
            </form>

            <a href="../index.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Volver al Portal de Clientes
            </a>

            <div class="footer-text">
                &copy; <?php echo date('Y'); ?> Compulago S.A.S. • Todos los derechos reservados.
            </div>
        </div>
    </div>
</body>
</html>
