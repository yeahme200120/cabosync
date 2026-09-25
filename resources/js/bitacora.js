// ============================================
// CABOSYNC - MÓDULO BITÁCORA
// ============================================

document.addEventListener("click", async function (e) {

    const fila = e.target.closest(".fila-bitacora");
    if (!fila) return;

    // Ignorar clics en enlaces (Google Maps)
    if (e.target.closest("a")) return;

    const id = fila.dataset.bitacoraId;
    if (!id) return;

    // Resetear modal
    document.getElementById("detalleLoading").classList.remove("d-none");
    document.getElementById("detalleContenido").classList.add("d-none");

    new bootstrap.Modal(document.getElementById("modalDetalleBitacora")).show();

    try {
        const { data } = await axios.get(
            `${window.BITACORA_CONFIG.rutas.base}/${id}`
        );

        if (!data.success) {
            CaboSyncAlert.error(data.error || "Error al cargar detalle");
            return;
        }

        const r = data.registro;

        // Acción y tipo
        document.getElementById("detalleAccion").textContent = r.accion;
        document.getElementById("detalleTipoAccion").textContent = r.tipo_accion;
        document.getElementById("detalleEsPublico").classList.toggle("d-none", !r.es_publico);
        document.getElementById("detalleDescripcion").textContent = r.descripcion;
        document.getElementById("detalleFecha").textContent = r.fecha || "-";
        document.getElementById("detalleFechaHumana").textContent = r.fecha_humana ? `(${r.fecha_humana})` : "";

        // Usuario y empresa
        document.getElementById("detalleUsuario").innerHTML = r.usuario
            ? `<strong>${r.usuario.nombre}</strong><br><small class="text-muted">${r.usuario.email}</small>`
            : '<span class="badge bg-warning text-dark"><i class="bi bi-globe"></i> Público general</span>';

        document.getElementById("detalleEmpresa").innerHTML = r.empresa
            ? `<strong>${r.empresa.nombre}</strong><br><small class="text-muted">RFC: ${r.empresa.rfc || "-"}</small>`
            : "-";

        // Modelo afectado
        document.getElementById("detalleModelo").textContent = r.modelo_afectado
            ? r.modelo_afectado.split("\\").pop()
            : "-";
        document.getElementById("detalleModeloId").textContent = r.modelo_id || "-";

        // Diff
        const diffSection = document.getElementById("detalleDiffSection");
        const diffDiv = document.getElementById("detalleDiff");
        diffDiv.innerHTML = "";

        if (r.datos_antes || r.datos_despues) {
            diffSection.classList.remove("d-none");

            const antes = r.datos_antes || {};
            const despues = r.datos_despues || {};
            const todosLosCampos = new Set([
                ...Object.keys(antes),
                ...Object.keys(despues),
            ]);

            if (todosLosCampos.size === 0) {
                diffDiv.innerHTML = '<p class="text-muted small mb-0">Sin cambios registrados.</p>';
            } else {
                todosLosCampos.forEach((campo) => {
                    const valAntes = antes[campo];
                    const valDespues = despues[campo];

                    const antesStr = formatearValor(valAntes);
                    const despuesStr = formatearValor(valDespues);

                    // Solo mostrar si cambió
                    if (antesStr === despuesStr) return;

                    const div = document.createElement("div");
                    div.className = "diff-campo mb-2 p-2 rounded";
                    div.innerHTML = `
                        <div class="fw-semibold small text-cabosync-primary">${campo}</div>
                        <div class="small">
                            <span class="diff-antes">${antesStr}</span>
                            <i class="bi bi-arrow-right mx-1"></i>
                            <span class="diff-despues">${despuesStr}</span>
                        </div>
                    `;
                    diffDiv.appendChild(div);
                });

                if (diffDiv.innerHTML === "") {
                    diffDiv.innerHTML = '<p class="text-muted small mb-0">Sin cambios detectados en los campos.</p>';
                }
            }
        } else {
            diffSection.classList.add("d-none");
        }

        // Contexto técnico
        document.getElementById("detalleIp").textContent = r.direccion_ip || "-";
        document.getElementById("detallePlataforma").textContent = r.plataforma || "-";
        document.getElementById("detalleNavegador").textContent = r.navegador || "-";
        document.getElementById("detalleDeviceId").textContent = r.device_id || "-";

        const geoDiv = document.getElementById("detalleGeo");
        if (r.latitud && r.longitud) {
            const precision = r.precision_geo ? ` (${r.precision_geo})` : "";
            geoDiv.innerHTML = `
                <a href="https://www.google.com/maps?q=${r.latitud},${r.longitud}"
                   target="_blank"
                   class="text-decoration-none">
                    <i class="bi bi-geo-alt-fill text-cabosync-secondary"></i>
                    ${r.latitud}, ${r.longitud}${precision}
                </a>
            `;
        } else {
            geoDiv.textContent = "Sin datos de ubicación";
        }

        // Mostrar
        document.getElementById("detalleLoading").classList.add("d-none");
        document.getElementById("detalleContenido").classList.remove("d-none");

    } catch (error) {
        console.error(error);
        CaboSyncAlert.error(error.response?.data?.error || "Error al cargar detalle");
        document.getElementById("detalleLoading").classList.add("d-none");
    }
});

// ============================================
// Helper: formatear valor para el diff
// ============================================
function formatearValor(valor) {
    if (valor === null || valor === undefined || valor === "") {
        return '<em class="text-muted">vacío</em>';
    }
    if (typeof valor === "object") {
        return JSON.stringify(valor);
    }
    if (typeof valor === "boolean") {
        return valor ? "Sí" : "No";
    }
    return String(valor);
}