<?php
$radicado = $_GET['radicado'] ?? 'ERROR';
$notification_failed = ($_GET['notification'] ?? '') === 'failed';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Solicitud Radicada con Éxito! | Compulago</title>
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="public/css/loading.css">
    <style>
        .success-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }

        .success-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-lg);
            padding: 3rem 2.5rem;
            max-width: 620px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-card);
            position: relative;
        }

        .success-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 40px;
            right: 40px;
            height: 4px;
            background: var(--compu-green-gradient);
            border-radius: 0 0 6px 6px;
        }

        .success-icon-ring {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: var(--compu-green-soft);
            color: var(--compu-green-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 0 0 10px rgba(176, 200, 11, 0.1);
            animation: bounceIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        /* 21st.dev Radicado Copyable Widget */
        .radicado-widget {
            background: #fcfef7;
            border: 1.5px dashed rgba(176, 200, 11, 0.6);
            border-radius: var(--radius-md);
            padding: 1.4rem;
            margin: 1.8rem 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            text-align: left;
        }

        .radicado-info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 2px;
        }

        .radicado-code {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--compu-green-dark);
            letter-spacing: 1px;
            font-family: monospace;
        }

        .copy-btn {
            background: #ffffff;
            border: 1.5px solid #d1d5db;
            padding: 9px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-secondary);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .copy-btn:hover {
            border-color: var(--compu-green-bright);
            color: var(--compu-green-dark);
            background: var(--compu-green-soft);
        }

        .copy-btn.copied {
            background: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }

        /* 21st.dev Step Timeline */
        .timeline-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 2rem 0;
            padding: 0 10px;
        }

        .timeline-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 30px;
            right: 30px;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
        }

        .timeline-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            max-width: 120px;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid var(--compu-green-bright);
            color: var(--compu-green-dark);
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .step-circle.done {
            background: var(--compu-green-gradient);
            border-color: var(--compu-green-dark);
            color: #ffffff;
        }

        .step-text {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-secondary);
            line-height: 1.2;
        }
    </style>
</head>
<body>

    <div class="success-wrapper">
        <div class="success-card">
            
            <div class="success-icon-ring">
                <i class="fa-solid fa-check"></i>
            </div>

            <h1 style="font-size: 1.7rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; margin-bottom: 0.4rem;">
                ¡Solicitud Radicada con Éxito!
            </h1>
            
            <p style="color: var(--text-secondary); font-size: 0.92rem; line-height: 1.5;">
                Hemos registrado tu caso en nuestro sistema de atención. Nuestro equipo de soporte dará inicio a la gestión de tu requerimiento.
            </p>

            <?php if ($notification_failed): ?>
                <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: var(--radius-sm); color: #9a3412; padding: 12px 14px; margin-top: 1rem; font-size: 0.84rem;">
                    La solicitud fue registrada, pero no pudimos enviar el correo de confirmación. Conserva tu número de radicado y contacta a soporte si no recibes una notificación.
                </div>
            <?php endif; ?>

            <!-- Radicado Copyable Widget -->
            <div class="radicado-widget">
                <div>
                    <div class="radicado-info-label">Número de Radicado</div>
                    <div class="radicado-code" id="radicadoCode"><?php echo htmlspecialchars($radicado); ?></div>
                </div>
                <button type="button" class="copy-btn" id="copyBtn" onclick="copyRadicado()">
                    <i class="fa-regular fa-copy"></i>
                    <span id="copyText">Copiar</span>
                </button>
            </div>

            <!-- Timeline Steps -->
            <div class="timeline-steps">
                <div class="timeline-step">
                    <div class="step-circle done"><i class="fa-solid fa-check"></i></div>
                    <div class="step-text">Radicado Creado</div>
                </div>
                <div class="timeline-step">
                    <div class="step-circle">2</div>
                    <div class="step-text">Asignación a Especialista</div>
                </div>
                <div class="timeline-step">
                    <div class="step-circle">3</div>
                    <div class="step-text">Respuesta & Solución</div>
                </div>
            </div>

            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1.8rem;">
                Te enviamos un correo electrónico de confirmación con los detalles y el enlace de seguimiento.
            </p>

            <a href="index.php" class="shimmer-button" style="text-decoration: none; display: inline-flex;">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Volver al Formulario</span>
            </a>

        </div>
    </div>

    <script src="public/js/loading.js"></script>
    <script>
        function copyRadicado() {
            const radicadoText = document.getElementById('radicadoCode').textContent;
            navigator.clipboard.writeText(radicadoText).then(() => {
                const btn = document.getElementById('copyBtn');
                btn.classList.add('copied');
                btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>¡Copiado!</span>';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = '<i class="fa-regular fa-copy"></i> <span>Copiar</span>';
                }, 2500);
            });
        }
    </script>
</body>
</html>
