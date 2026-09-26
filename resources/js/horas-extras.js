// ============================================
// CABOSYNC - MÓDULO HORAS EXTRAS
// ============================================

function getFiltros() {
    return {
        empresa_id: document.getElementById("empresa_id")?.value || "",
        obra_id:    document.getElementById("obra_id")?.value || "",
        week:       document.getElementById("week")?.value || "",
        estado:     document.getElementById("estado")?.value || "",
    };
}

function limpiarFiltros() {
    const empresa = document.getElementById("empresa_id");
    const obra = document.getElementById("obra_id");
    const estado = document.getElementById("estado");

    if (empresa) empresa.value = "";
    if (obra) obra.value = "";
    if (estado) estado.value = "";

    cargarTabla();
}

// ============================================
// CARGAR TABLA
// ============================================
async function cargarTabla() {
    const loading = document.getElementById("tablaLoading");
    const vacia = document.getElementById("tablaVacia");
    const contenido = document.getElementById("tablaContenido");
    const tbody = document.getElementById("tablaBody");

    loading.classList.remove("d-none");
    vacia.classList.add("d-none");
    contenido.classList.add("d-none");

    try {
        const filtros = getFiltros();
        const params = new URLSearchParams(filtros).toString();
        const { data } = await axios.get(
            `${window.HORAS_EXTRAS_CONFIG.rutas.historial}?${params}`
        );

        loading.classList.add("d-none");

        if (!data.success || !data.registros || data.registros.length === 0) {
            vacia.classList.remove("d-none");
            return;
        }

        tbody.innerHTML = "";
        data.registros.forEach((emp) => {
            const dias = emp.dias;
            const celdas = [1, 2, 3, 4, 5, 6].map((dia) => {
                const d = dias[dia];
                if (!d) {
                    return `<td class="text-center text-muted">—</td>`;
                }

                const color = d.badge_estado;
                const disabled = d.estado !== "pendiente" ? "disabled" : "";

                return `
                    <td class="text-center">
                        <div class="celda-hora celda-hora--${color}" title="${d.texto_estado}">
                            <input type="checkbox" class="form-check-input check-registro me-1"
                                   value="${d.id}" data-empleado="${emp.empleado.id}" ${disabled}>
                            <span class="fw-bold">${d.horas_solicitadas}h</span>
                            ${d.horas_aprobadas > 0 ? `<div class="small text-success">✅ ${d.horas_aprobadas}h</div>` : ""}
                        </div>
                    </td>
                `;
            }).join("");

            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input check-empleado"
                           data-empleado="${emp.empleado.id}">
                </td>
                <td>
                    <div class="fw-semibold small">${emp.empleado.nombre}</div>
                    <div class="text-muted" style="font-size: 0.72rem;">
                        ${emp.empleado.puesto}
                        ${emp.empleado.obra ? " · " + emp.empleado.obra : ""}
                    </div>
                </td>
                ${celdas}
                <td class="text-center">
                    <span class="badge bg-primary">${emp.totales.solicitadas}h</span>
                    ${emp.totales.aprobadas > 0 ? `<span class="badge bg-success">${emp.totales.aprobadas}h</span>` : ""}
                </td>
            `;
            tbody.appendChild(tr);
        });

        contenido.classList.remove("d-none");
        bindCheckboxes();
    } catch (error) {
        console.error(error);
        loading.classList.add("d-none");
        vacia.classList.remove("d-none");
    }
}

// ============================================
// BIND CHECKBOXES
// ============================================
function bindCheckboxes() {
    // "Seleccionar todo"
    const checkAll = document.getElementById("checkAll");
    if (checkAll) {
        checkAll.checked = false;
        checkAll.onclick = function () {
            document.querySelectorAll(".check-registro:not(:disabled)").forEach((cb) => {
                cb.checked = checkAll.checked;
            });
        };
    }

    // Selección por empleado
    document.querySelectorAll(".check-empleado").forEach((cb) => {
        cb.onclick = function () {
            const empId = this.dataset.empleado;
            document.querySelectorAll(`.check-registro[data-empleado="${empId}"]:not(:disabled)`).forEach((c) => {
                c.checked = this.checked;
            });
        };
    });
}

// ============================================
// OBTENER IDs SELECCIONADOS
// ============================================
function getIdsSeleccionados() {
    const ids = [];
    document.querySelectorAll(".check-registro:checked").forEach((cb) => {
        ids.push(cb.value);
    });
    return ids;
}

// ============================================
// APROBAR SELECCIONADOS
// ============================================
async function aprobarSeleccionados() {
    const ids = getIdsSeleccionados();
    if (ids.length === 0) {
        CaboSyncAlert.error("Selecciona al menos un registro.");
        return;
    }

    const confirmado = await CaboSyncAlert.confirm(
        "¿Aprobar horas extras?",
        `Se aprobarán ${ids.length} registros seleccionados.`
    );
    if (!confirmado) return;

    try {
        const { data } = await axios.post(
            window.HORAS_EXTRAS_CONFIG.rutas.aprobarMasivo,
            { ids }
        );
        if (data.success) {
            CaboSyncAlert.success(data.mensaje);
            cargarTabla();
        } else {
            CaboSyncAlert.error(data.error);
        }
    } catch (error) {
        console.error(error);
        CaboSyncAlert.error(error.response?.data?.error || "Error al aprobar");
    }
}

// ============================================
// RECHAZAR SELECCIONADOS
// ============================================
async function rechazarSeleccionados() {
    const ids = getIdsSeleccionados();
    if (ids.length === 0) {
        CaboSyncAlert.error("Selecciona al menos un registro.");
        return;
    }

    const confirmado = await CaboSyncAlert.confirmDelete(
        "¿Rechazar horas extras?",
        `Se rechazarán ${ids.length} registros seleccionados.`
    );
    if (!confirmado) return;

    try {
        const { data } = await axios.post(
            window.HORAS_EXTRAS_CONFIG.rutas.rechazarMasivo,
            { ids }
        );
        if (data.success) {
            CaboSyncAlert.success(data.mensaje);
            cargarTabla();
        } else {
            CaboSyncAlert.error(data.error);
        }
    } catch (error) {
        console.error(error);
        CaboSyncAlert.error(error.response?.data?.error || "Error al rechazar");
    }
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener("DOMContentLoaded", function () {
    // Autocargar al cambiar filtros
    ["empresa_id", "obra_id", "week", "estado"].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener("change", cargarTabla);
    });

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

    // Cargar tabla al entrar
    if (document.getElementById("tablaBody")) {
        cargarTabla();
    }
});

// Exponer globales
window.limpiarFiltros = limpiarFiltros;
window.aprobarSeleccionados = aprobarSeleccionados;
window.rechazarSeleccionados = rechazarSeleccionados;
window.cargarTabla = cargarTabla;