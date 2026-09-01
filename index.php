<?php require_once 'includes/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atención Al Cliente - PQRS | Compulago</title>
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 21st.dev Modern Design Styles -->
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="public/css/loading.css">
</head>
<body>

    <!-- Header Section -->
    <header class="header">
        <div class="logo-container">
            <img src="https://compulago.b-cdn.net/Logo-Compulago/SVG/SVG%20LOGO%20BLACK.svg" alt="Compulago Logo">
        </div>

        <div class="status-badge-floating">
            <span class="pulse-dot"></span>
            <span>Canal Oficial de Atención al Cliente & PQRS</span>
        </div>

        <div class="title-container">
            <h1>Radicación de Solicitudes</h1>
            <p>Queremos escucharte. Si necesitas reportar una novedad, presentar una inquietud o recibir apoyo de nuestro equipo, completa el siguiente formulario.</p>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="container">

        <!-- 2 Icon Boxes: Selector de Acción Principal -->
        <div class="mode-selector-grid">
            
            <div class="mode-card active" id="tabCreate" onclick="switchMode('create')">
                <div class="mode-card-icon">
                    <i class="fa-solid fa-file-circle-plus"></i>
                </div>
                <div class="mode-card-content">
                    <h3>Radicar Solicitud</h3>
                    <p>Crea una nueva petición, queja, reclamo o requerimiento de garantía.</p>
                </div>
                <div class="mode-indicator">
                    <i class="fa-solid fa-circle-dot"></i>
                </div>
            </div>

            <div class="mode-card" id="tabSearch" onclick="switchMode('search')">
                <div class="mode-card-icon">
                    <i class="fa-solid fa-magnifying-glass-chart"></i>
                </div>
                <div class="mode-card-content">
                    <h3>Consultar Estado de PQR</h3>
                    <p>Ingresa tu número de radicado o cédula para consultar el avance en tiempo real.</p>
                </div>
                <div class="mode-indicator">
                    <i class="fa-regular fa-circle"></i>
                </div>
            </div>

        </div>

        <!-- Vista 1: Formulario de Radicación de Solicitudes -->
        <div id="viewCreate" class="mode-view">
            <div class="pqr-card">
                <form id="pqrForm" action="process_pqr.php" method="POST" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <div style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>
                
                <!-- 1. Datos Personales -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon-badge">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <h2 class="section-title">Datos Personales</h2>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre">Nombre <span class="required">*</span></label>
                            <input type="text" id="nombre" name="nombre" required placeholder="Tu nombre">
                        </div>
                        <div class="form-group">
                            <label for="apellido">Apellido <span class="required">*</span></label>
                            <input type="text" id="apellido" name="apellido" required placeholder="Tu apellido">
                        </div>
                        <div class="form-group">
                            <label for="tipo_doc">Tipo de Documento <span class="required">*</span></label>
                            <select id="tipo_doc" name="tipo_doc" required>
                                <option value="" disabled selected>Selecciona una opción</option>
                                <option value="Cédula de Ciudadanía">Cédula de Ciudadanía</option>
                                <option value="Cédula de Extranjería">Cédula de Extranjería</option>
                                <option value="NIT">NIT</option>
                                <option value="Pasaporte">Pasaporte</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="documento">Número de Documento <span class="required">*</span></label>
                            <input type="text" id="documento" name="documento" required placeholder="Ej: 123456789">
                        </div>
                        <div class="form-group full-width">
                            <label for="direccion">Dirección</label>
                            <input type="text" id="direccion" name="direccion" placeholder="Dirección de correspondencia (opcional)">
                        </div>
                        <div class="form-group">
                            <label for="departamento">Departamento</label>
                            <select id="departamento" name="departamento">
                                <option value="" disabled selected>Cargando departamentos...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ciudad">Ciudad</label>
                            <select id="ciudad" name="ciudad" disabled>
                                <option value="" disabled selected>Selecciona un departamento primero</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Teléfono / Celular <span class="required">*</span></label>
                            <div class="phone-input-group">
                                <select id="phone_prefix" name="phone_prefix" class="phone-prefix">
                                    <option value="+57" selected>🇨🇴 +57</option>
                                    <option value="+1">🇺🇸 +1</option>
                                    <option value="+34">🇪🇸 +34</option>
                                    <option value="+52">🇲🇽 +52</option>
                                    <option value="+58">🇻🇪 +58</option>
                                    <option value="+507">🇵🇦 +507</option>
                                </select>
                                <input type="tel" id="telefono" name="telefono" required placeholder="Ej: 321 1234567">
                            </div>
                        </div>
                        <div class="form-group" style="position: relative;">
                            <label for="email">Correo Electrónico <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required placeholder="ejemplo@correo.com" autocomplete="off">
                            <div id="emailSuggestions" class="suggestions-container"></div>
                        </div>
                    </div>
                </section>

                <!-- 2. Datos de la Solicitud -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon-badge" style="background: rgba(163, 230, 53, 0.15); color: #4d7c0f;">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <h2 class="section-title">Datos de la Solicitud</h2>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 1rem;">
                        <label>Tipo de Solicitud <span class="required">*</span></label>
                        <!-- Input oculto que envía el valor real al backend -->
                        <input type="hidden" name="tipo_pqr" id="tipoPqrInput" value="Petición">
                        
                        <!-- Cuadrícula interactiva estilo 21st.dev -->
                        <div class="type-grid" id="typeGrid">
                            <div class="type-card active" data-value="Petición">
                                <i class="fa-solid fa-circle-check check-indicator"></i>
                                <i class="fa-solid fa-paper-plane type-card-icon"></i>
                                <span class="type-card-label">Petición</span>
                            </div>
                            <div class="type-card" data-value="Cotización">
                                <i class="fa-solid fa-circle-check check-indicator"></i>
                                <i class="fa-solid fa-file-invoice-dollar type-card-icon"></i>
                                <span class="type-card-label">Cotización</span>
                            </div>
                            <div class="type-card" data-value="Consultas">
                                <i class="fa-solid fa-circle-check check-indicator"></i>
                                <i class="fa-solid fa-circle-question type-card-icon"></i>
                                <span class="type-card-label">Consultas</span>
                            </div>
                            <div class="type-card" data-value="Quejas">
                                <i class="fa-solid fa-circle-check check-indicator"></i>
                                <i class="fa-solid fa-triangle-exclamation type-card-icon"></i>
                                <span class="type-card-label">Quejas</span>
                            </div>
                            <div class="type-card" data-value="Reclamos">
                                <i class="fa-solid fa-circle-check check-indicator"></i>
                                <i class="fa-solid fa-file-circle-exclamation type-card-icon"></i>
                                <span class="type-card-label">Reclamos</span>
                            </div>
                            <div class="type-card" data-value="Garantía">
                                <i class="fa-solid fa-circle-check check-indicator"></i>
                                <i class="fa-solid fa-screwdriver-wrench type-card-icon"></i>
                                <span class="type-card-label">Garantía</span>
                            </div>
                            <div class="type-card" data-value="Derecho de Retracto">
                                <i class="fa-solid fa-circle-check check-indicator"></i>
                                <i class="fa-solid fa-arrow-rotate-left type-card-icon"></i>
                                <span class="type-card-label">Retracto</span>
                            </div>
                            <div class="type-card" data-value="Reversión del Pago">
                                <i class="fa-solid fa-circle-check check-indicator"></i>
                                <i class="fa-solid fa-credit-card type-card-icon"></i>
                                <span class="type-card-label">Reversión</span>
                            </div>
                            <div class="type-card" data-value="Otros">
                                <i class="fa-solid fa-circle-check check-indicator"></i>
                                <i class="fa-solid fa-ellipsis type-card-icon"></i>
                                <span class="type-card-label">Otros</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="comentario">Comentario o Detalle <span class="required">*</span></label>
                        <textarea id="comentario" name="comentario" required placeholder="(Indicar de manera detallada de qué se trata el requerimiento)"></textarea>
                    </div>

                    <!-- Dropzone de Archivos -->
                    <div class="form-group full-width" style="margin-top: 1rem;">
                        <label>Soporte o Evidencias <span style="font-weight: 400; color: var(--text-muted);">(Opcional)</span></label>
                        <div class="dropzone-container" id="dropzoneSoporte">
                            <div class="dropzone-icon-wrapper">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div class="dropzone-text">
                                <h3>Haz clic o arrastra tus archivos aquí</h3>
                                <p>Hasta 4 archivos en total, máx. 2MB por archivo</p>
                            </div>
                            <div class="dropzone-badges">
                                <span class="format-badge">JPG</span>
                                <span class="format-badge">PNG</span>
                                <span class="format-badge">PDF (hasta 2)</span>
                            </div>
                            <input type="file" name="soporte[]" id="soporteInput" class="file-input" multiple accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <div id="soporteFileList" class="file-list"></div>
                    </div>
                </section>

                <!-- 3. Consentimiento Legal y Políticas (Diseño Oficial) -->
                <div class="legal-consent-section">
                    
                    <!-- Checkbox de Autorización -->
                    <div class="legal-checkbox-wrapper">
                        <input type="checkbox" id="check_datos" name="check_datos" required>
                        <label for="check_datos" class="legal-text-label">
                            Autorizo a COMPULAGO S.A.S. para tratar mis datos personales con la finalidad de registrar, gestionar, responder y hacer seguimiento a esta solicitud, así como para enviarme al correo electrónico indicado las comunicaciones relacionadas con este trámite, conforme a la Política de Tratamiento de Datos Personales.
                        </label>
                    </div>

                    <!-- Acordeón 1: Aviso de Privacidad -->
                    <div class="legal-accordion-card">
                        <div class="legal-accordion-header" onclick="this.parentElement.classList.toggle('open')">
                            <span>Aviso de Privacidad</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                        <div class="legal-accordion-body">
                            COMPULAGO S.A.S., identificada con NIT 804.004.992-1, en calidad de Responsable del Tratamiento de Datos Personales, le informa que la información suministrada a través de este formulario será tratada de manera segura y confidencial con el único propósito de gestionar, dar trámite y responder a su requerimiento de atención al cliente. Puede ejercer sus derechos de conocer, actualizar, rectificar y suprimir sus datos escribiendo a <a href="mailto:soporte@compulago.com" style="color: var(--compu-green-dark); font-weight: 700;">soporte@compulago.com</a>.
                        </div>
                    </div>

                    <!-- Acordeón 2: Tratamiento de Datos Personales -->
                    <div class="legal-accordion-card">
                        <div class="legal-accordion-header" onclick="this.parentElement.classList.toggle('open')">
                            <span>Tratamiento de Datos Personales</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                        <div class="legal-accordion-body">
                             Los datos personales recolectados a través de esta plataforma de PQRS serán almacenados en nuestros servidores conforme a lo establecido en la Ley Estatutaria 1581 de 2012 y el Decreto 1377 de 2013 de Colombia. Para conocer en detalle nuestra política institucional, finalidades de tratamiento y canales de atención, consulte nuestra <a href="https://compulago.com/politica-de-tratamiento-de-datos-personales/" target="_blank" rel="noopener noreferrer" style="color: var(--compu-green-dark); font-weight: 700; text-decoration: underline;">Política Integral de Tratamiento de Datos Personales</a>.
                        </div>
                    </div>

                </div>

                <!-- Shimmer Submit Button -->
                <button type="submit" class="shimmer-button" id="btnSubmit">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Enviar Solicitud</span>
                </button>

            </form>
        </div>
    </div>

    <!-- Vista 2: Módulo de Consulta de Estado de PQR -->
    <div id="viewSearch" class="mode-view" style="display: none;">
        <div class="search-card-container">
            <div class="search-hero-header">
                <h2>Consulta y Seguimiento de Requerimientos</h2>
                <p>Verifica el estado actual de tu solicitud ingresando tu <strong>Número de Radicado</strong> y el <strong>correo o documento</strong> registrado.</p>
            </div>

            <form id="formSearchPqr" onsubmit="handlePqrSearch(event)">
                <div class="search-input-box">
                    <div class="search-field-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="pqrQueryInput" placeholder="Número de radicado" aria-label="Número de radicado" required autocomplete="off" maxlength="30">
                    </div>
                    <div class="search-field-wrapper">
                        <i class="fa-solid fa-shield-halved"></i>
                        <input type="text" id="pqrVerificationInput" placeholder="Correo o documento registrado" aria-label="Correo o documento registrado" required autocomplete="off" maxlength="254">
                    </div>
                    <button type="submit" class="btn-lookup" id="btnLookup">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Consultar Estado</span>
                    </button>
                </div>
            </form>

            <!-- Estado de Carga -->
            <div id="searchLoading" style="display: none; text-align: center; padding: 2.5rem 1rem;">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 2.2rem; color: var(--compu-green-dark); margin-bottom: 12px;"></i>
                <p style="color: var(--text-secondary); font-weight: 600;">Consultando en el sistema oficial de Compulago...</p>
            </div>

            <!-- Mensaje de Error / No Encontrado -->
            <div id="searchNotFound" style="display: none; background: #fff5f5; border: 1.5px solid #fecaca; border-radius: var(--radius-sm); padding: 1.4rem; text-align: center; color: #b91c1c; margin-top: 1.5rem;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.6rem; margin-bottom: 8px; display: block;"></i>
                <div id="searchNotFoundMsg" style="font-weight: 700; font-size: 0.95rem;">No se encontraron resultados para los datos ingresados.</div>
                <div style="font-size: 0.84rem; color: #7f1d1d; margin-top: 4px;">Por favor verifica que el radicado o número de documento esté bien escrito.</div>
            </div>

            <!-- Contenedor de Resultados -->
            <div id="searchResultsContainer"></div>
        </div>
    </div>

</main>

<footer class="footer-clean">
    <p>Compulago S.A.S. &copy; <?php echo date('Y'); ?> • Todos los derechos reservados</p>
</footer>

<script src="public/js/loading.js"></script>
<script src="public/js/app.js"></script>
</body>
</html>
