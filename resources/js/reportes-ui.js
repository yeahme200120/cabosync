// ============================================
// CABOSYNC - MÓDULO REPORTES (UI)
// ============================================

// ============================================
// HELPERS
// ============================================
function getFiltros() {
    const empresaId = document.getElementById("empresa_id")?.value || "";
    const obraId    = document.getElementById("obra_id")?.value || "";
    const week      = document.getElementById("week")?.value || "";

    if (!empresaId) {
        CaboSyncAlert.error("Debes seleccionar una empresa.");
        return null;
    }

    if (!week) {
        CaboSyncAlert.error("Debes seleccionar una semana.");
        return null;
    }

    return { empresa_id: empresaId, obra_id: obraId, week };
}

function getFiltrosHistorial() {
    return {
        empresa_id: document.getElementById("filtroEmpresa")?.value || "",
        estado:     document.getElementById("filtroEstado")?.value || "",
    };
}

// ============================================
// DESCARGAR PDF / EXCEL
// ============================================
function descargarReporte(tipo) {
    const filtros = getFiltros();
    if (!filtros) return;

    const params = new URLSearchParams(filtros).toString();
    window.location.href = `${window.REPORTES_CONFIG.rutas.descargar}/${tipo}?${params}`;
}

// ============================================
// GENERAR LINK TEMPORAL
// ============================================
async function generarLink() {
    const filtros = getFiltros();
    if (!filtros) return;

    try {
        const { data } = await axios.post(
            window.REPORTES_CONFIG.rutas.generarLink,
            filtros
        );

        if (data.success) {
            const alert = document.getElementById("linkGenerado");
            const linkUrl = document.getElementById("linkPublicoUrl");
            const expira = document.getElementById("linkExpira");

            linkUrl.href = data.link;
            linkUrl.textContent = data.link;
            expira.textContent = data.expira_en;
            alert.classList.remove("d-none");
            alert.dataset.link = data.link;

            CaboSyncAlert.success("Link generado correctamente");
            cargarHistorial();
        } else {
            CaboSyncAlert.error(data.error || "Error al generar link");
        }
    } catch (error) {
        console.error(error);
        CaboSyncAlert.error(error.response?.data?.error || "Error al generar link");
    }
}

function copiarLink() {
    const alert = document.getElementById("linkGenerado");
    const link = alert?.dataset?.link;
    if (!link) return;

    navigator.clipboard.writeText(link).then(() => {
        CaboSyncAlert.success("Link copiado al portapapeles");
    });
}

// ============================================
// COMPARTIR WHATSAPP
// ============================================
async function compartirWhatsApp() {
    const filtros = getFiltros();
    if (!filtros) return;

    try {
        const { data } = await axios.post(
            window.REPORTES_CONFIG.rutas.generarLink,
            filtros
        );

        if (!data.success) {
            CaboSyncAlert.error(data.error || "Error al generar link");
            return;
        }

        const link = data.link;
        const mensaje = `Hola, te comparto el reporte de asistencia de la semana ${filtros.week}:\n${link}`;

        if (navigator.share) {
            try {
                await navigator.share({
                    title: `Reporte ${filtros.week}`,
                    text: mensaje,
                });
                return;
            } catch (err) {
                // El usuario canceló o no soporta → seguimos con wa.me
            }
        }

        const url = `https://wa.me/?text=${encodeURIComponent(mensaje)}`;
        window.open(url, "_blank");

        cargarHistorial();
    } catch (error) {
        console.error(error);
        CaboSyncAlert.error(error.response?.data?.error || "Error al compartir");
    }
}

// ============================================
// ABRIR MODAL DE CORREO
// ============================================
function abrirModalCorreo() {
    const filtros = getFiltros();
    if (!filtros) return;

    document.getElementById("correoDestinatarios").value = "";
    document.getElementById("correoCC").value = "";
    document.getElementById("correoMensaje").value = "";

    new bootstrap.Modal(document.getElementById("modalEnviarCorreo")).show();
}

// ============================================
// ENVIAR CORREO
// ============================================
document.addEventListener("DOMContentLoaded", function () {
    const btnEnviar = document.getElementById("btnEnviarCorreo");
    if (!btnEnviar) return;

    btnEnviar.addEventListener("click", async function () {
        const filtros = getFiltros();
        if (!filtros) return;

        const destinatarios = document.getElementById("correoDestinatarios").value.trim();
        const cc = document.getElementById("correoCC").value.trim();
        const mensaje = document.getElementById("correoMensaje").value.trim();

        if (!destinatarios) {
            CaboSyncAlert.error("Debes indicar al menos un destinatario.");
            return;
        }

        btnEnviar.disabled = true;
        btnEnviar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

        try {
            const { data } = await axios.post(
                window.REPORTES_CONFIG.rutas.enviarCorreo,
                {
                    ...filtros,
                    destinatarios,
                    cc,
                    mensaje,
                }
            );

            if (data.success) {
                CaboSyncAlert.success(data.mensaje || "Correo enviado");
                bootstrap.Modal.getInstance(document.getElementById("modalEnviarCorreo")).hide();
                cargarHistorial();
            } else {
                CaboSyncAlert.error(data.error || "Error al enviar");
            }
        } catch (error) {
            console.error(error);
            CaboSyncAlert.error(error.response?.data?.error || "Error al enviar correo");
        } finally {
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = '<i class="bi bi-send-fill"></i> Enviar Correo';
        }
    });
});

// ============================================
// CARGAR HISTORIAL
// ============================================
async function cargarHistorial() {
    const loading = document.getElementById("historialLoading");
    const vacio = document.getElementById("historialVacio");
    const contenido = document.getElementById("historialContenido");
    const tbody = document.getElementById("historialBody");

    loading.classList.remove("d-none");
    vacio.classList.add("d-none");
    contenido.classList.add("d-none");

    try {
        const filtros = getFiltrosHistorial();
        const params = new URLSearchParams(filtros).toString();
        const { data } = await axios.get(
            `${window.REPORTES_CONFIG.rutas.historial}?${params}`
        );

        loading.classList.add("d-none");

        if (!data.success || !data.registros || data.registros.length === 0) {
            vacio.classList.remove("d-none");
            return;
        }

        tbody.innerHTML = "";
        data.registros.forEach((r) => {
            const badgeVigencia = r.esta_vigente
                ? `<span class="badge bg-success"><i class="bi bi-check-circle"></i> Vigente</span>`
                : `<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Expirado</span>`;

            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>
                    <div class="small">${r.creado}</div>
                    <div class="text-muted" style="font-size: 0.72rem;">${r.creado_humano}</div>
                </td>
                <td class="small">${r.empresa}</td>
                <td class="small">${r.obra}</td>
                <td><code>${r.week}</code></td>
                <td>${badgeVigencia}</td>
                <td><span class="badge bg-light text-dark">${r.descargas}</span></td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="${r.link_pdf}" class="btn btn-outline-danger" title="PDF" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                        <a href="${r.link_excel}" class="btn btn-outline-success" title="Excel" target="_blank">
                            <i class="bi bi-file-earmark-excel"></i>
                        </a>
                        <button class="btn btn-outline-secondary" title="Copiar link"
                                onclick="navigator.clipboard.writeText('${r.link_publico}').then(() => CaboSyncAlert.success('Link copiado'))">
                            <i class="bi bi-link-45deg"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        contenido.classList.remove("d-none");
    } catch (error) {
        console.error(error);
        loading.classList.add("d-none");
        vacio.classList.remove("d-none");
    }
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener("DOMContentLoaded", function () {
    // Filtrar obras por empresa
    const empresaSelect = document.getElementById("empresa_id");
    const obraSelect = document.getElementById("obra_id");

    if (empresaSelect && obraSelect) {
        empresaSelect.addEventListener("change", function () {
            const empresaId = this.value;
            Array.from(obraSelect.options).forEach((opt) => {
                if (opt.value === "") return;
                opt.hidden = empresaId && opt.dataset.empresa !== empresaId;
            });
            obraSelect.value = "";
        });
    }

    // Filtros de historial
    const filtroEmpresa = document.getElementById("filtroEmpresa");
    const filtroEstado = document.getElementById("filtroEstado");

    if (filtroEmpresa) filtroEmpresa.addEventListener("change", cargarHistorial);
    if (filtroEstado) filtroEstado.addEventListener("change", cargarHistorial);

    // Cargar historial al entrar
    if (document.getElementById("historialBody")) {
        cargarHistorial();
    }
});

// Exponer funciones globales
window.descargarReporte = descargarReporte;
window.generarLink = generarLink;
window.copiarLink = copiarLink;
window.compartirWhatsApp = compartirWhatsApp;
window.abrirModalCorreo = abrirModalCorreo;
window.cargarHistorial = cargarHistorial;