document.addEventListener('DOMContentLoaded', () => {
    // 1. Selector de Tipo de Solicitud (21st.dev Card Grid)
    initTypeSelector();

    // 2. Manejo Avanzado de Dropzone con Lista Interactiva y Borrado
    initFileManager('dropzoneSoporte', 'soporteInput', 'soporteFileList', 4, 2);

    // 3. Departamentos y Municipios de Colombia
    initColombiaData();

    // 4. Autocompletado de Correos Electrónicos
    initEmailSuggestions();

    // 5. Validación y Feedback Visual de Envío
    const pqrForm = document.getElementById('pqrForm');
    const btnSubmit = document.getElementById('btnSubmit');

    if (pqrForm && btnSubmit) {
        pqrForm.addEventListener('submit', (e) => {
            const checkDatos = document.getElementById('check_datos');
            if (!checkDatos.checked) {
                e.preventDefault();
                alert('Debes autorizar el tratamiento de datos personales para continuar.');
                checkDatos.focus();
                return;
            }

            btnSubmit.disabled = true;
            pqrForm.dataset.loadingHandled = 'true';

            const soporteInput = document.getElementById('soporteInput');
            const hasFiles = soporteInput && soporteInput.files.length > 0;
            if (window.PqrLoading) {
                window.PqrLoading.setButtonLoading(btnSubmit, hasFiles ? 'Subiendo...' : 'Enviando...');
                window.PqrLoading.show(hasFiles ? 'Subiendo tus archivos...' : 'Enviando tu solicitud...');
            } else {
                btnSubmit.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><span>Procesando Solicitud...</span>';
                btnSubmit.style.opacity = '0.85';
                btnSubmit.style.cursor = 'wait';
            }
        });
    }
});

/**
 * 1. Selector de Tipo de Solicitud (21st.dev Grid Selector)
 */
function initTypeSelector() {
    const cards = document.querySelectorAll('.type-card');
    const hiddenInput = document.getElementById('tipoPqrInput');

    cards.forEach(card => {
        card.addEventListener('click', () => {
            cards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            const val = card.getAttribute('data-value');
            if (hiddenInput) {
                hiddenInput.value = val;
            }
        });
    });
}

/**
 * 2. Gestor de Archivos Interactivo estilo 21st.dev
 */
function initFileManager(containerId, inputId, listId, maxFiles = 4, maxMbPerFile = 2) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);

    if (!container || !input || !list) return;

    let currentFiles = [];

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        container.classList.add('dragover');
    });

    container.addEventListener('dragleave', () => {
        container.classList.remove('dragover');
    });

    container.addEventListener('drop', (e) => {
        e.preventDefault();
        container.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            addFiles(Array.from(e.dataTransfer.files));
        }
    });

    input.addEventListener('change', () => {
        if (input.files.length > 0) {
            addFiles(Array.from(input.files));
        }
    });

    function addFiles(newFiles) {
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        const maxSizeBytes = maxMbPerFile * 1024 * 1024;

        newFiles.forEach(file => {
            if (currentFiles.length >= maxFiles) {
                alert(`Solo puedes adjuntar hasta un máximo de ${maxFiles} archivos.`);
                return;
            }

            const ext = file.name.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(ext)) {
                alert(`El archivo "${file.name}" no tiene un formato válido (JPG, PNG, PDF).`);
                return;
            }

            if (file.size > maxSizeBytes) {
                alert(`El archivo "${file.name}" supera el tamaño máximo permitido de ${maxMbPerFile}MB.`);
                return;
            }

            // Evitar duplicados por nombre y tamaño
            const isDuplicate = currentFiles.some(f => f.name === file.name && f.size === file.size);
            if (!isDuplicate) {
                currentFiles.push(file);
            }
        });

        syncInputFiles();
        renderFileList();
    }

    function removeFile(index) {
        currentFiles.splice(index, 1);
        syncInputFiles();
        renderFileList();
    }

    function syncInputFiles() {
        const dt = new DataTransfer();
        currentFiles.forEach(file => dt.items.add(file));
        input.files = dt.files;
    }

    function renderFileList() {
        list.innerHTML = '';
        currentFiles.forEach((file, index) => {
            const ext = file.name.split('.').pop().toLowerCase();
            const sizeMb = (file.size / 1024 / 1024).toFixed(2);
            let iconClass = 'fa-file-lines';
            if (['jpg', 'jpeg', 'png'].includes(ext)) iconClass = 'fa-file-image';
            if (ext === 'pdf') iconClass = 'fa-file-pdf';

            const card = document.createElement('div');
            card.className = 'file-item-card';
            card.innerHTML = `
                <div class="file-item-left">
                    <div class="file-item-icon">
                        <i class="fa-solid ${iconClass}"></i>
                    </div>
                    <div>
                        <div class="file-item-name">${escapeHtml(file.name)}</div>
                        <div class="file-item-size">${sizeMb} MB</div>
                    </div>
                </div>
                <button type="button" class="file-item-remove" title="Eliminar archivo">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            `;

            card.querySelector('.file-item-remove').addEventListener('click', () => {
                removeFile(index);
            });

            list.appendChild(card);
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

/**
 * 3. Departamentos y Municipios de Colombia
 */
const colombiaData = {
    "Antioquia": ["Medellín", "Bello", "Itagüí", "Envigado", "Apartadó", "Rionegro", "Turbo", "Caucasia"],
    "Atlántico": ["Barranquilla", "Soledad", "Malambo", "Sabanalarga", "Puerto Colombia", "Baranoa"],
    "Bogotá D.C.": ["Bogotá D.C."],
    "Bolívar": ["Cartagena", "Magangué", "Turbaco", "El Carmen de Bolívar", "Arjona"],
    "Boyacá": ["Tunja", "Duitama", "Sogamoso", "Chiquinquirá", "Puerto Boyacá"],
    "Caldas": ["Manizales", "La Dorada", "Riosucio", "Villamaría", "Chinchiná"],
    "Caquetá": ["Florencia", "San Vicente del Caguán"],
    "Casanare": ["Yopal", "Aguazul", "Villanueva"],
    "Cauca": ["Popayán", "Santander de Quilichao", "Puerto Tejada"],
    "Cesar": ["Valledupar", "Aguachica", "Agustín Codazzi", "Bosconia"],
    "Chocó": ["Quibdó", "Istmina"],
    "Córdoba": ["Montería", "Cereté", "Sahagún", "Lorica", "Montelíbano"],
    "Cundinamarca": ["Soacha", "Girardot", "Zipaquirá", "Facatativá", "Chía", "Mosquera", "Madrid", "Funza"],
    "Huila": ["Neiva", "Pitalito", "Garzón", "La Plata"],
    "La Guajira": ["Riohacha", "Maicao", "Uribia", "San Juan del Cesar"],
    "Magdalena": ["Santa Marta", "Ciénaga", "Fundación", "Plato", "El Banco"],
    "Meta": ["Villavicencio", "Acacías", "Granada"],
    "Nariño": ["Pasto", "Ipiales", "Tumaco", "Tuquerres"],
    "Norte de Santander": ["Cúcuta", "Ocaña", "Villa del Rosario", "Los Patios", "Pamplona"],
    "Quindío": ["Armenia", "Calarcá", "Montenegro", "Quimbaya"],
    "Risaralda": ["Pereira", "Dosquebradas", "Santa Rosa de Cabal"],
    "Santander": ["Bucaramanga", "Floridablanca", "Barrancabermeja", "Girón", "Piedecuesta", "San Gil"],
    "Sucre": ["Sincelejo", "Corozal", "San Marcos"],
    "Tolima": ["Ibagué", "Espinal", "Melgar", "Chaparral"],
    "Valle del Cauca": ["Cali", "Buenaventura", "Palmira", "Tuluá", "Yumbo", "Cartago", "Buga", "Jamundí"],
    "Arauca": ["Arauca", "Tame", "Saravena"],
    "Guaviare": ["San José del Guaviare"],
    "Putumayo": ["Mocoa", "Puerto Asís", "Orito"],
    "San Andrés y Providencia": ["San Andrés", "Providencia"],
    "Amazonas": ["Leticia"],
    "Guainía": ["Inírida"],
    "Vaupés": ["Mitú"],
    "Vichada": ["Puerto Carreño"]
};

function initColombiaData() {
    const depSelect = document.getElementById('departamento');
    const citySelect = document.getElementById('ciudad');

    if (!depSelect || !citySelect) return;

    depSelect.innerHTML = '<option value="" disabled selected>Selecciona un departamento</option>';
    Object.keys(colombiaData).sort().forEach(dep => {
        const option = document.createElement('option');
        option.value = dep;
        option.textContent = dep;
        depSelect.appendChild(option);
    });

    depSelect.addEventListener('change', () => {
        const selectedDep = depSelect.value;
        const cities = colombiaData[selectedDep] || [];

        citySelect.innerHTML = '<option value="" disabled selected>Selecciona una ciudad</option>';
        cities.sort().forEach(city => {
            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            citySelect.appendChild(option);
        });

        citySelect.disabled = false;
    });
}

/**
 * 4. Autocompletado de Correos Electrónicos
 */
function initEmailSuggestions() {
    const emailInput = document.getElementById('email');
    const suggestionsContainer = document.getElementById('emailSuggestions');
    if (!emailInput || !suggestionsContainer) return;

    const domains = [
        'gmail.com',
        'hotmail.com',
        'outlook.com',
        'yahoo.com',
        'icloud.com',
        'live.com',
        'compulago.net',
        'compulago.com'
    ];

    emailInput.addEventListener('input', (e) => {
        const value = e.target.value;
        if (value.includes('@')) {
            const atIndex = value.indexOf('@');
            const userPart = value.substring(0, atIndex);
            const domainQuery = value.substring(atIndex + 1).toLowerCase();

            if (userPart.length > 0) {
                const filteredDomains = domains.filter(d => d.startsWith(domainQuery));
                if (filteredDomains.length > 0 && !(filteredDomains.length === 1 && filteredDomains[0] === domainQuery)) {
                    showSuggestions(userPart, filteredDomains);
                    return;
                }
            }
        }
        suggestionsContainer.style.display = 'none';
    });

    function showSuggestions(userPart, domainList) {
        suggestionsContainer.innerHTML = '';
        domainList.forEach(domain => {
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.innerHTML = `${escapeText(userPart)}<span>@${escapeText(domain)}</span>`;
            div.onclick = () => {
                emailInput.value = `${userPart}@${domain}`;
                suggestionsContainer.style.display = 'none';
                emailInput.focus();
            };
            suggestionsContainer.appendChild(div);
        });
        suggestionsContainer.style.display = 'block';
    }

    function escapeText(str) {
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }

    document.addEventListener('click', (e) => {
        if (e.target !== emailInput) {
            suggestionsContainer.style.display = 'none';
        }
    });
}

/**
 * 5. Conmutador de Modos (Radicar vs Consultar)
 */
function switchMode(mode) {
    const tabCreate = document.getElementById('tabCreate');
    const tabSearch = document.getElementById('tabSearch');
    const viewCreate = document.getElementById('viewCreate');
    const viewSearch = document.getElementById('viewSearch');

    if (!tabCreate || !tabSearch || !viewCreate || !viewSearch) return;

    if (mode === 'create') {
        tabCreate.classList.add('active');
        tabCreate.querySelector('.mode-indicator').innerHTML = '<i class="fa-solid fa-circle-dot"></i>';
        tabSearch.classList.remove('active');
        tabSearch.querySelector('.mode-indicator').innerHTML = '<i class="fa-regular fa-circle"></i>';
        viewCreate.style.display = 'block';
        viewSearch.style.display = 'none';
    } else {
        tabSearch.classList.add('active');
        tabSearch.querySelector('.mode-indicator').innerHTML = '<i class="fa-solid fa-circle-dot"></i>';
        tabCreate.classList.remove('active');
        tabCreate.querySelector('.mode-indicator').innerHTML = '<i class="fa-regular fa-circle"></i>';
        viewCreate.style.display = 'none';
        viewSearch.style.display = 'block';
        
        // Auto-focus input
        const input = document.getElementById('pqrQueryInput');
        if (input) input.focus();
    }
}

/**
 * 6. Consulta de Estado de PQR por Radicado o Cédula (AJAX)
 */
async function handlePqrSearch(event) {
    event.preventDefault();
    const input = document.getElementById('pqrQueryInput');
    const verificationInput = document.getElementById('pqrVerificationInput');
    const radicado = input.value.trim();
    const verification = verificationInput ? verificationInput.value.trim() : '';
    if (!radicado || !verification) return;

    const btnLookup = document.getElementById('btnLookup');
    const loadingEl = document.getElementById('searchLoading');
    const notFoundEl = document.getElementById('searchNotFound');
    const notFoundMsg = document.getElementById('searchNotFoundMsg');
    const resultsContainer = document.getElementById('searchResultsContainer');

    // UI Reset
    notFoundEl.style.display = 'none';
    resultsContainer.innerHTML = '';
    loadingEl.style.display = 'block';

    if (window.PqrLoading) {
        window.PqrLoading.setButtonLoading(btnLookup, 'Consultando...');
        window.PqrLoading.show('Consultando el estado de tu PQR...');
    }
    else {
        btnLookup.disabled = true;
    }

    try {
        const formData = new FormData();
        formData.append('radicado', radicado);
        formData.append('verification', verification);

        const res = await fetch('consultar_pqr_ajax.php', {
            method: 'POST',
            body: formData
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const data = await res.json();

        if (!data.success || !data.tickets || data.tickets.length === 0) {
            notFoundMsg.textContent = data.message || 'No se encontró ningún requerimiento con los datos ingresados.';
            notFoundEl.style.display = 'block';
            return;
        }

        renderPqrSearchResults(data.tickets, resultsContainer);

    } catch (err) {
        notFoundMsg.textContent = 'Ocurrió un error al comunicarse con el servidor. Intenta de nuevo.';
        notFoundEl.style.display = 'block';
    } finally {
        loadingEl.style.display = 'none';
        if (window.PqrLoading) {
            window.PqrLoading.restoreButton(btnLookup);
            window.PqrLoading.hide();
        } else {
            btnLookup.disabled = false;
        }
    }
}

function renderPqrSearchResults(tickets, container) {
    container.innerHTML = '';

    tickets.forEach(ticket => {
        let statusBadgeClass = 'bg-open';
        let statusIcon = '<span class="pulse-dot-red"></span>';
        let heroClass = 'hero-open';
        let heroIcon = '<i class="fa-solid fa-hourglass-half" style="color: #dc2626;"></i>';
        let heroTitle = 'Solicitud Recibida (Pendiente de Asignación)';
        let heroDesc = 'Tu requerimiento ha sido registrado en nuestro sistema oficial y se encuentra en cola para ser revisado por un asesor.';
        
        let step1Class = 'completed';
        let step2Class = '';
        let step3Class = '';

        if (ticket.status === 'En Proceso') {
            statusBadgeClass = 'bg-process';
            statusIcon = '<i class="fa-solid fa-clock"></i>';
            heroClass = 'hero-process';
            heroIcon = '<i class="fa-solid fa-gears" style="color: #d97706;"></i>';
            heroTitle = 'Solicitud en Gestión Activa';
            heroDesc = 'Tu requerimiento se encuentra en trámite y análisis con las áreas correspondientes para brindarte una solución oportuna.';
            step2Class = 'active';
        } else if (ticket.status === 'Resuelto') {
            statusBadgeClass = 'bg-resolved';
            statusIcon = '<i class="fa-solid fa-circle-check"></i>';
            heroClass = 'hero-resolved';
            heroIcon = '<i class="fa-solid fa-circle-check" style="color: #16a34a;"></i>';
            heroTitle = 'Solicitud Atendida y Resuelta';
            heroDesc = 'Tu solicitud ha sido atendida y solucionada formalmente por el equipo de atención al cliente de Compulago.';
            step2Class = 'completed';
            step3Class = 'completed';
        } else if (ticket.status === 'Cerrado') {
            statusBadgeClass = 'bg-danger';
            statusIcon = '<i class="fa-solid fa-circle-xmark"></i>';
            heroClass = 'hero-closed';
            heroIcon = '<i class="fa-solid fa-archive" style="color: #64748b;"></i>';
            heroTitle = 'Trámite Concluido';
            heroDesc = 'El ciclo de atención y seguimiento de esta solicitud ha finalizado.';
            step2Class = 'completed';
            step3Class = 'completed';
        }

        const card = document.createElement('div');
        card.className = 'pqr-status-card';

        // Respuesta Oficial (Si existe)
        let repliesHtml = '';
        if (ticket.replies && ticket.replies.length > 0) {
            const latestReply = ticket.replies[ticket.replies.length - 1];
            repliesHtml = `
                <div class="pqr-official-reply-card">
                    <div class="reply-card-header">
                        <div class="reply-card-title">
                            <i class="fa-solid fa-comments-dollar" style="color: #16a34a;"></i>
                            <span>Respuesta Oficial de Atención al Cliente</span>
                        </div>
                        <span class="reply-card-date">
                            <i class="fa-regular fa-clock"></i> ${escapeHtml(latestReply.date)}
                        </span>
                    </div>
                    <div class="reply-card-body">${escapeHtml(latestReply.message)}</div>
                </div>
            `;
        }

        card.innerHTML = `
            <!-- Cabecera de Identificación -->
            <div class="pqr-status-header">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div class="radicado-badge-big" style="background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 5px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-hashtag" style="color: var(--compu-green-dark);"></i>
                        <span style="color: var(--compu-green-dark); font-weight: 800; font-family: monospace; font-size: 1.05rem;">${escapeHtml(ticket.radicado)}</span>
                    </div>
                    <span class="status-badge ${statusBadgeClass}" style="font-size: 0.85rem; padding: 6px 14px;">
                        ${statusIcon} ${escapeHtml(ticket.status)}
                    </span>
                    <span class="type-badge-pill" style="font-size: 0.85rem; padding: 6px 14px;">
                        ${escapeHtml(ticket.type)}
                    </span>
                </div>
                <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
                    <i class="fa-regular fa-calendar"></i> Radicado el ${escapeHtml(ticket.created_at_human)}
                </div>
            </div>

            <!-- 1. Banner Principal con Explicación Detallada del Estado -->
            <div class="status-hero-banner ${heroClass}">
                <div class="status-hero-icon">${heroIcon}</div>
                <div class="status-hero-text">
                    <h4>${escapeHtml(heroTitle)}</h4>
                    <p>${escapeHtml(heroDesc)}</p>
                </div>
            </div>

            <!-- 2. Stepper Visual del Estado -->
            <div class="pqr-stepper-track">
                <div class="stepper-step ${step1Class}">
                    <div class="stepper-dot"><i class="fa-solid fa-check"></i></div>
                    <span class="stepper-label">1. Radicado</span>
                </div>
                <div class="stepper-step ${step2Class}">
                    <div class="stepper-dot">
                        ${step2Class === 'completed' ? '<i class="fa-solid fa-check"></i>' : (step2Class === 'active' ? '<i class="fa-solid fa-ellipsis"></i>' : '2')}
                    </div>
                    <span class="stepper-label">2. En Gestión</span>
                </div>
                <div class="stepper-step ${step3Class}">
                    <div class="stepper-dot">
                        ${step3Class === 'completed' ? '<i class="fa-solid fa-check"></i>' : '3'}
                    </div>
                    <span class="stepper-label">3. Resuelto</span>
                </div>
            </div>

            <!-- 3. Respuesta Oficial (Destacada) -->
            ${repliesHtml}

            <!-- 4. Datos del Requerimiento Original (Desplegable Discreto) -->
            <details class="initial-details-accordion">
                <summary class="initial-details-summary">
                    <span><i class="fa-solid fa-file-lines" style="color: #94a3b8; margin-right: 6px;"></i> Ver datos del requerimiento original y radicación</span>
                    <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
                </summary>
                <div class="initial-details-content">
                    <div class="initial-meta-row">
                        <div>
                            <span style="font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Titular:</span>
                            <div style="font-weight: 700; color: var(--text-dark);">${escapeHtml(ticket.client_name)}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Ciudad:</span>
                            <div style="font-weight: 700; color: var(--text-dark);">${escapeHtml(ticket.ciudad)}</div>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Detalle Reportado:</span>
                        <div style="margin-top: 3px; color: var(--text-secondary); line-height: 1.5;">${escapeHtml(ticket.description || ticket.subject)}</div>
                    </div>
                </div>
            </details>
        `;

        container.appendChild(card);
    });
}

function escapeHtml(str) {
    if (!str) return '';
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}
