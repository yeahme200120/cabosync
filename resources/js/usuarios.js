// ============================================
// CABOSYNC - MÓDULO USUARIOS
// ============================================

// ============================================
// RESET FORM (CREAR)
// ============================================
function resetFormUsuario() {
    const form = document.getElementById("formUsuario");
    if (!form) return;

    form.reset();
    document.getElementById("usuarioId").value = "";
    document.getElementById("modalUsuarioTitulo").innerHTML =
        '<i class="bi bi-person-plus-fill"></i> Registrar Nuevo Usuario';
    document.getElementById("btnGuardarUsuarioTexto").textContent = "Registrar";
    document.getElementById("campoEstatusUsuario").classList.add("d-none");
    document.getElementById("avisoPasswordTemporal").style.display = "block";

    if (window.USUARIOS_CONFIG?.usuario?.esContratista) {
        const empresaSelect = document.getElementById("empresa_id");
        if (empresaSelect) {
            empresaSelect.value = window.USUARIOS_CONFIG.usuario.empresaId || "";
            empresaSelect.disabled = true;
        }
    }
}

// ============================================
// EDITAR USUARIO
// ============================================
async function editarUsuario(id) {
    try {
        const { data } = await axios.get(`${window.USUARIOS_CONFIG.rutas.base}/${id}`);
        if (!data.success) {
            CaboSyncAlert.error(data.error || "Error al cargar usuario");
            return;
        }
        const u = data.usuario;
        document.getElementById("usuarioId").value = u.id;
        document.getElementById("nombre").value = u.nombre;
        document.getElementById("email").value = u.email;
        document.getElementById("rol_id").value = u.rol_id;
        document.getElementById("empresa_id").value = u.empresa_id || "";
        document.getElementById("estatus").value = u.estatus;

        document.getElementById("modalUsuarioTitulo").innerHTML =
            '<i class="bi bi-pencil-square"></i> Editar Usuario';
        document.getElementById("btnGuardarUsuarioTexto").textContent = "Actualizar";
        document.getElementById("campoEstatusUsuario").classList.remove("d-none");
        document.getElementById("avisoPasswordTemporal").style.display = "none";

        if (window.USUARIOS_CONFIG?.usuario?.esContratista) {
            document.getElementById("empresa_id").disabled = true;
        }
        new bootstrap.Modal(document.getElementById("modalUsuario")).show();
    } catch (error) {
        console.error(error);
        CaboSyncAlert.error("Error al cargar usuario");
    }
}

// ============================================
// GUARDAR (CREAR O EDITAR)
// ============================================
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("formUsuario");
    if (!form) return;

    form.addEventListener("submit", async function (e) {
        e.preventDefault();
        const id = document.getElementById("usuarioId").value;
        const esEdicion = !!id;
        const btnGuardar = document.getElementById("btnGuardarUsuario");
        const btnTexto = document.getElementById("btnGuardarUsuarioTexto");
        btnGuardar.disabled = true;
        btnTexto.textContent = "Guardando...";

        const formData = new FormData(this);
        if (document.getElementById("empresa_id").disabled) {
            formData.set("empresa_id", document.getElementById("empresa_id").value);
        }
        const url = esEdicion
            ? `${window.USUARIOS_CONFIG.rutas.base}/${id}`
            : window.USUARIOS_CONFIG.rutas.store;

        try {
            const { data } = await axios.post(url, formData);
            bootstrap.Modal.getInstance(document.getElementById("modalUsuario")).hide();
            if (esEdicion) {
                actualizarCard(id, data.card_html);
                CaboSyncAlert.success(data.mensaje || "Usuario actualizado");
            } else {
                document.getElementById("resultEmail").textContent = data.usuario.email;
                document.getElementById("resultPassword").textContent = data.password_temporal;
                new bootstrap.Modal(document.getElementById("modalPasswordTemporal")).show();
                setTimeout(() => window.location.reload(), 5000);
            }
        } catch (error) {
            if (error.response?.data?.errors) {
                const primerError = Object.values(error.response.data.errors)[0]?.[0];
                CaboSyncAlert.error(primerError);
            } else {
                CaboSyncAlert.error(error.response?.data?.error || "Error al guardar");
            }
        } finally {
            btnGuardar.disabled = false;
            btnTexto.textContent = esEdicion ? "Actualizar" : "Registrar";
        }
    });
});

// ============================================
// ACTUALIZAR CARD EN VIVO
// ============================================
function actualizarCard(id, cardHtml) {
    const cardActual = document.querySelector(`.usuario-card[data-usuario-id="${id}"]`);
    if (!cardActual || !cardHtml) return;
    const temp = document.createElement("div");
    temp.innerHTML = cardHtml.trim();
    const nuevaCard = temp.firstElementChild;
    cardActual.replaceWith(nuevaCard);
}

// ============================================
// LISTENER ÚNICO Y MAESTRO PARA TODAS LAS ACCIONES
// ============================================
document.addEventListener("click", async function (e) {

    // ------------------------------------------------
    // 1. IMPERSONAR (primero, para que no lo bloquee nada)
    // ------------------------------------------------
    const btnImpersonar = e.target.closest(".btn-impersonar");
    if (btnImpersonar) {
        e.preventDefault();
        const id = btnImpersonar.dataset.id;
        const nombre = btnImpersonar.dataset.nombre;

        const confirmado = await CaboSyncAlert.confirm(
            "¿Impersonar usuario?",
            `Vas a iniciar sesión como ${nombre}. Podrás regresar a tu cuenta desde el banner superior.`
        );
        if (!confirmado) return;

        try {
            const { data } = await axios.post(
                `${window.USUARIOS_CONFIG.rutas.base}/${id}/impersonar`
            );
            if (data.success) {
                window.location.href = data.redirect_to;
            } else {
                CaboSyncAlert.error(data.error || "Error al impersonar");
            }
        } catch (error) {
            console.error(error);
            
            console.error("Error impersonar:", error.response?.data);
            CaboSyncAlert.error(error.response?.data?.error || "Error al impersonar");
        }
        return;
    }

    // ------------------------------------------------
    // 2. DESCARGAR PLANTILLAS
    // ------------------------------------------------
    const btnPlantilla = e.target.closest(".btn-descargar-plantilla-usuario");
    if (btnPlantilla) {
        e.preventDefault();
        const tipo = btnPlantilla.dataset.tipo;
        const baseUrl = window.USUARIOS_CONFIG?.rutas?.base || "/usuarios";
        const url = tipo === "csv" ? `${baseUrl}/plantilla` : `${baseUrl}/plantilla-excel`;
        console.log("[Usuarios] Descargando plantilla:", tipo, url);

        const iframe = document.createElement("iframe");
        iframe.style.display = "none";
        iframe.src = url;
        document.body.appendChild(iframe);
        setTimeout(() => {
            if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
        }, 5000);
        setTimeout(() => CaboSyncAlert.success("Plantilla descargada"), 400);
        return;
    }

    // ------------------------------------------------
    // 3. EDITAR
    // ------------------------------------------------
    const btnEditar = e.target.closest(".btn-editar-usuario");
    if (btnEditar) {
        e.preventDefault();
        editarUsuario(btnEditar.dataset.id);
        return;
    }

    // ------------------------------------------------
    // 4. DESACTIVAR
    // ------------------------------------------------
    const btnDesactivar = e.target.closest(".btn-desactivar-usuario");
    if (btnDesactivar) {
        e.preventDefault();
        const id = btnDesactivar.dataset.id;
        const nombre = btnDesactivar.dataset.nombre;

        const confirmado = await CaboSyncAlert.confirm(
            "¿Desactivar usuario?",
            `El usuario ${nombre} quedará inactivo y no podrá iniciar sesión.`
        );
        if (!confirmado) return;

        try {
            const { data } = await axios.post(
                `${window.USUARIOS_CONFIG.rutas.base}/${id}/desactivar`
            );
            if (data.success) {
                actualizarCard(id, data.card_html);
                CaboSyncAlert.success("Usuario desactivado");
            }
        } catch (error) {
            CaboSyncAlert.error(error.response?.data?.error || "Error al desactivar");
        }
        return;
    }

    // ------------------------------------------------
    // 5. ELIMINAR
    // ------------------------------------------------
    const btnEliminar = e.target.closest(".btn-eliminar-usuario");
    if (btnEliminar) {
        e.preventDefault();
        const id = btnEliminar.dataset.id;
        const nombre = btnEliminar.dataset.nombre;

        const confirmado = await CaboSyncAlert.confirmDelete(
            "¿Eliminar usuario PERMANENTEMENTE?",
            `Esta acción eliminará a ${nombre} de forma irreversible.`
        );
        if (!confirmado) return;

        try {
            const { data } = await axios.delete(
                `${window.USUARIOS_CONFIG.rutas.base}/${id}`
            );
            if (data.success) {
                document.querySelector(`.usuario-card[data-usuario-id="${id}"]`)?.remove();
                CaboSyncAlert.success("Usuario eliminado");
                const contador = document.getElementById("contadorUsuarios");
                const total = document.getElementById("totalRegistros");
                if (contador) contador.textContent = Math.max(0, parseInt(contador.textContent) - 1);
                if (total) total.textContent = Math.max(0, parseInt(total.textContent) - 1);
            }
        } catch (error) {
            CaboSyncAlert.error(error.response?.data?.error || "Error al eliminar");
        }
        return;
    }

    // ------------------------------------------------
    // 6. RESET PASSWORD
    // ------------------------------------------------
    const btnReset = e.target.closest(".btn-reset-password");
    if (btnReset) {
        e.preventDefault();
        const id = btnReset.dataset.id;
        const nombre = btnReset.dataset.nombre;

        const confirmado = await CaboSyncAlert.confirm(
            "¿Resetear contraseña?",
            `Se generará una nueva contraseña temporal para ${nombre} y se enviará por correo.`
        );
        if (!confirmado) return;

        try {
            const { data } = await axios.post(
                `${window.USUARIOS_CONFIG.rutas.base}/${id}/reset-password`
            );
            if (data.success) {
                document.getElementById("resultEmail").textContent = "";
                document.getElementById("resultPassword").textContent = data.password_temporal;
                new bootstrap.Modal(document.getElementById("modalPasswordTemporal")).show();
            }
        } catch (error) {
            CaboSyncAlert.error(error.response?.data?.error || "Error al resetear");
        }
        return;
    }
});

// ============================================
// IMPORTAR USUARIOS - MODAL DE 4 PASOS
// ============================================
let importUsuarioFormato = null;
let importUsuarioArchivo = null;
let importUsuarioFilas = [];

document.querySelectorAll("#modalImportarUsuarios .import-formato-card").forEach((card) => {
    card.addEventListener("click", async () => {
        document.querySelectorAll("#modalImportarUsuarios .import-formato-card").forEach((el) => el.classList.remove("active"));
        card.classList.add("active");
        importUsuarioFormato = card.dataset.formato;

        const input = document.createElement("input");
        input.type = "file";
        input.accept = importUsuarioFormato === "sql" ? ".sql" : importUsuarioFormato === "csv" ? ".csv,.txt" : ".xlsx,.xls";

        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            importUsuarioArchivo = file;
            if (importUsuarioFormato === "sql") {
                await previsualizarUsuarioSQL(file);
            } else {
                await previsualizarUsuarioCSVExcel(file);
            }
        };
        input.click();
    });
});

async function previsualizarUsuarioCSVExcel(file) {
    try {
        const ext = file.name.split(".").pop().toLowerCase();
        let filas = [];
        let encabezados = [];

        if (ext === "xlsx" || ext === "xls") {
            const buffer = await file.arrayBuffer();
            const workbook = window.XLSX.read(buffer, { type: "array" });
            const hoja = workbook.Sheets[workbook.SheetNames[0]];
            const datos = window.XLSX.utils.sheet_to_json(hoja, { header: 1, defval: "", raw: false });
            if (datos.length < 2) { CaboSyncAlert.error("El archivo está vacío"); return; }
            encabezados = datos[0].map((h) => String(h).trim());
            for (let i = 1; i < datos.length; i++) {
                const fila = {};
                encabezados.forEach((enc, idx) => {
                    fila[enc] = datos[i][idx] !== undefined ? String(datos[i][idx]).trim() : "";
                });
                filas.push(fila);
            }
        } else {
            const texto = await file.text();
            const lineas = texto.split(/\r?\n/).filter((l) => l.trim());
            if (lineas.length < 2) { CaboSyncAlert.error("El archivo está vacío"); return; }
            const sep = lineas[0].includes(";") ? ";" : ",";
            encabezados = lineas[0].split(sep).map((h) => h.trim().replace(/^["']|["']$/g, ""));
            for (let i = 1; i < lineas.length; i++) {
                const valores = lineas[i].split(sep).map((v) => v.trim().replace(/^["']|["']$/g, ""));
                if (valores.length === encabezados.length) {
                    const fila = {};
                    encabezados.forEach((enc, idx) => { fila[enc] = valores[idx]; });
                    filas.push(fila);
                }
            }
        }

        importUsuarioFilas = filas;
        document.getElementById("importUsuarioNombreArchivo").textContent = file.name;
        document.getElementById("importUsuarioTotalFilas").textContent = filas.length;
        document.getElementById("importUsuarioTotalFilas2").textContent = filas.length;
        document.getElementById("importUsuarioCantidadBtn").textContent = `${filas.length} registros`;

        const thead = document.getElementById("importUsuarioPreviewHead");
        const tbody = document.getElementById("importUsuarioPreviewBody");
        thead.innerHTML = "<th>#</th>";
        encabezados.forEach((enc) => {
            const th = document.createElement("th");
            th.textContent = enc;
            thead.appendChild(th);
        });
        tbody.innerHTML = "";
        filas.slice(0, 10).forEach((fila, idx) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `<td><strong>${idx + 2}</strong></td>` +
                encabezados.map((enc) => `<td class="import-table-truncate" title="${fila[enc] || ""}">${fila[enc] || ""}</td>`).join("");
            tbody.appendChild(tr);
        });

        document.getElementById("importUsuarioTablaPreview").classList.remove("d-none");
        document.getElementById("importUsuarioSqlPreview").classList.add("d-none");
        mostrarPasoUsuario(2);
    } catch (error) {
        console.error(error);
        CaboSyncAlert.error("Error al leer archivo: " + error.message);
    }
}

async function previsualizarUsuarioSQL(file) {
    try {
        const texto = await file.text();
        const insertMatches = texto.match(/INSERT\s+INTO\s+`?users`?/gi) || [];
        let totalFilas = 0;
        const regexValues = /VALUES\s*([\s\S]*?);/gi;
        let match;
        while ((match = regexValues.exec(texto)) !== null) {
            const bloque = match[1];
            const filas = bloque.match(/\([^)]*\)/g) || [];
            totalFilas += filas.length;
        }
        const lineas = texto.split("\n");
        const preview = lineas.slice(0, 50).join("\n");
        const hayMas = lineas.length > 50;

        document.getElementById("importUsuarioSqlText").value = preview + (hayMas ? `\n\n... (${lineas.length - 50} líneas más)` : "");
        document.getElementById("importUsuarioSqlPreview").classList.remove("d-none");
        document.getElementById("importUsuarioTablaPreview").classList.add("d-none");
        document.getElementById("importUsuarioNombreArchivo").textContent = file.name;
        document.getElementById("importUsuarioTotalFilas").textContent = totalFilas || insertMatches.length;
        document.getElementById("importUsuarioTotalFilas2").textContent = `${totalFilas} filas en ${insertMatches.length} INSERTs`;
        document.getElementById("importUsuarioCantidadBtn").textContent = `${totalFilas} registros`;
        mostrarPasoUsuario(2);
    } catch (error) {
        console.error(error);
        CaboSyncAlert.error("Error al leer SQL");
    }
}

function mostrarPasoUsuario(paso) {
    ["importUsuarioPaso1", "importUsuarioPaso2", "importUsuarioPaso3", "importUsuarioPaso4"].forEach((id) => {
        document.getElementById(id)?.classList.add("d-none");
    });
    document.getElementById("importUsuarioPaso" + paso)?.classList.remove("d-none");
    document.getElementById("importUsuarioPasoBadge").textContent = `Paso ${paso} de 4`;

    ["importUsuarioFooterPaso1", "importUsuarioFooterPaso2", "importUsuarioFooterPaso3", "importUsuarioFooterPaso4"].forEach((id) => {
        document.getElementById(id)?.classList.add("d-none");
        document.getElementById(id)?.classList.remove("d-flex");
    });
    const footer = document.getElementById("importUsuarioFooterPaso" + paso);
    footer?.classList.remove("d-none");
    footer?.classList.add("d-flex");
}

function volverPaso1Usuario() {
    importUsuarioArchivo = null;
    importUsuarioFilas = [];
    mostrarPasoUsuario(1);
}

function volverAlPaso1DesdeResultadoUsuario() {
    importUsuarioFormato = null;
    importUsuarioArchivo = null;
    importUsuarioFilas = [];
    document.querySelectorAll("#modalImportarUsuarios .import-formato-card").forEach((el) => el.classList.remove("active"));
    mostrarPasoUsuario(1);
}

async function iniciarImportacionUsuario() {
    if (!importUsuarioArchivo) { CaboSyncAlert.error("No hay archivo"); return; }
    mostrarPasoUsuario(3);

    const formData = new FormData();
    let url;
    if (importUsuarioFormato === "sql") {
        formData.append("archivo_sql", importUsuarioArchivo);
        url = `${window.USUARIOS_CONFIG.rutas.base}/importar-sql`;
    } else {
        formData.append("archivo", importUsuarioArchivo);
        url = `${window.USUARIOS_CONFIG.rutas.base}/importar`;
    }

    let progreso = 0;
    const intervalo = setInterval(() => {
        progreso = Math.min(90, progreso + 3);
        document.getElementById("importUsuarioProgressBar").style.width = progreso + "%";
        document.getElementById("importUsuarioProgressBar").textContent = progreso + "%";
    }, 150);

    try {
        const { data } = await axios.post(url, formData, { headers: { "Content-Type": "multipart/form-data" } });
        clearInterval(intervalo);
        document.getElementById("importUsuarioProgressBar").style.width = "100%";
        document.getElementById("importUsuarioProgressBar").textContent = "100%";

        if (data.success) {
            document.getElementById("resumenUsuariosCargados").textContent = data.resumen.cargados;
            document.getElementById("resumenUsuariosActualizados").textContent = data.resumen.actualizados;
            document.getElementById("resumenUsuariosOmitidos").textContent = data.resumen.omitidos || 0;
            document.getElementById("resumenUsuariosErrores").textContent = data.resumen.errores;
            document.getElementById("badgeUsuariosCargados").textContent = data.resumen.cargados;
            document.getElementById("badgeUsuariosActualizados").textContent = data.resumen.actualizados;
            document.getElementById("badgeUsuariosOmitidos").textContent = data.resumen.omitidos || 0;
            document.getElementById("badgeUsuariosErrores").textContent = data.resumen.errores;

            const tablaCargados = document.getElementById("tablaUsuariosCargados");
            tablaCargados.innerHTML = data.cargados.length === 0
                ? '<tr><td colspan="4" class="text-center text-muted py-3">Sin registros</td></tr>'
                : data.cargados.map(c => `<tr><td>${c.fila}</td><td>${c.nombre}</td><td>${c.email}</td><td><span class="badge bg-success">#${c.id}</span></td></tr>`).join("");

            const tablaActualizados = document.getElementById("tablaUsuariosActualizados");
            tablaActualizados.innerHTML = data.actualizados.length === 0
                ? '<tr><td colspan="4" class="text-center text-muted py-3">Sin registros</td></tr>'
                : data.actualizados.map(a => {
                    let cambiosHtml = "";
                    Object.entries(a.cambios).forEach(([campo, diff]) => {
                        cambiosHtml += `<div class="mb-1"><strong>${campo}:</strong> <span class="cambio-campo__antes">${diff.antes ?? "vacío"}</span> → <span class="cambio-campo__despues">${diff.despues ?? "vacío"}</span></div>`;
                    });
                    return `<tr><td>${a.fila}</td><td>${a.nombre}</td><td>${a.email}</td><td>${cambiosHtml}</td></tr>`;
                }).join("");

            const tablaOmitidos = document.getElementById("tablaUsuariosOmitidos");
            tablaOmitidos.innerHTML = data.omitidos.length === 0
                ? '<tr><td colspan="4" class="text-center text-muted py-3">Sin registros</td></tr>'
                : data.omitidos.map(o => `<tr><td>${o.fila}</td><td>${o.nombre || "-"}</td><td>${o.email || "-"}</td><td><span class="text-muted">${o.motivo}</span></td></tr>`).join("");

            const tablaErrores = document.getElementById("tablaUsuariosErrores");
            tablaErrores.innerHTML = data.errores.length === 0
                ? '<tr><td colspan="3" class="text-center text-muted py-3">Sin errores 🎉</td></tr>'
                : data.errores.map(e => `<tr><td>${e.fila}</td><td><span class="badge bg-danger">${e.campo}</span></td><td>${e.motivo}</td></tr>`).join("");

            mostrarPasoUsuario(4);
            if (data.resumen.cargados > 0 || data.resumen.actualizados > 0) {
                CaboSyncAlert.success(`Importación: ${data.resumen.cargados} cargados, ${data.resumen.actualizados} actualizados`);
            }
        }
    } catch (error) {
        clearInterval(intervalo);
        console.error(error);
        CaboSyncAlert.error(error.response?.data?.error || "Error al importar");
        mostrarPasoUsuario(2);
    }
}

window.resetFormUsuario = resetFormUsuario;
window.editarUsuario = editarUsuario;
window.volverPaso1Usuario = volverPaso1Usuario;
window.volverAlPaso1DesdeResultadoUsuario = volverAlPaso1DesdeResultadoUsuario;
window.iniciarImportacionUsuario = iniciarImportacionUsuario;