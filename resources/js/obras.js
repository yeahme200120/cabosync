// ============================================
// CABOSYNC - MÓDULO OBRAS
// ============================================

function resetFormObra() {
    const form = document.getElementById("formObra");
    if (!form) return;

    form.reset();
    document.getElementById("obraId").value = "";
    document.getElementById("modalObraTitulo").innerHTML =
        '<i class="bi bi-hammer"></i> Registrar Nueva Obra';
    document.getElementById("btnGuardarObraTexto").textContent = "Registrar";
    document.getElementById("campoEstatusObra").style.display = "none";

    if (window.OBRAS_CONFIG?.usuario?.esContratista) {
        const empresaSelect = document.getElementById("empresa_id");
        if (empresaSelect) {
            empresaSelect.value = window.OBRAS_CONFIG.usuario.empresaId || "";
            empresaSelect.disabled = true;
        }
    }
}

// ============================================
// EDITAR OBRA
// ============================================
async function editarObra(id) {
    try {
        const { data } = await axios.get(
            `${window.OBRAS_CONFIG.rutas.base}/${id}`
        );

        if (!data.success) {
            CaboSyncAlert.error(data.error || "Error al cargar obra");
            return;
        }

        const o = data.obra;

        document.getElementById("obraId").value = o.id;
        document.getElementById("empresa_id").value = o.empresa_id;
        document.getElementById("nombre").value = o.nombre;
        document.getElementById("codigo").value = o.codigo || "";
        document.getElementById("ubicacion").value = o.ubicacion || "";
        document.getElementById("fecha_inicio").value = o.fecha_inicio
            ? o.fecha_inicio.split("T")[0]
            : "";
        document.getElementById("fecha_fin").value = o.fecha_fin
            ? o.fecha_fin.split("T")[0]
            : "";
        document.getElementById("estatus").value = o.estatus;

        document.getElementById("modalObraTitulo").innerHTML =
            '<i class="bi bi-pencil-square"></i> Editar Obra';
        document.getElementById("btnGuardarObraTexto").textContent = "Actualizar";
        document.getElementById("campoEstatusObra").style.display = "block";

        if (window.OBRAS_CONFIG?.usuario?.esContratista) {
            document.getElementById("empresa_id").disabled = true;
        }

        new bootstrap.Modal(document.getElementById("modalObra")).show();
    } catch (error) {
        console.error(error);
        CaboSyncAlert.error("Error al cargar obra");
    }
}

// ============================================
// GUARDAR (CREAR O EDITAR)
// ============================================
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("formObra");
    if (!form) return;

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const id = document.getElementById("obraId").value;
        const esEdicion = !!id;

        const btnGuardar = document.getElementById("btnGuardarObra");
        const btnTexto = document.getElementById("btnGuardarObraTexto");
        btnGuardar.disabled = true;
        btnTexto.textContent = "Guardando...";

        const formData = new FormData(this);

        if (document.getElementById("empresa_id").disabled) {
            formData.set("empresa_id", document.getElementById("empresa_id").value);
        }

        const url = esEdicion
            ? `${window.OBRAS_CONFIG.rutas.base}/${id}`
            : window.OBRAS_CONFIG.rutas.store;

        try {
            const { data } = await axios.post(url, formData);

            bootstrap.Modal.getInstance(
                document.getElementById("modalObra")
            ).hide();

            if (esEdicion) {
                actualizarCardObra(id, data.card_html);
                CaboSyncAlert.success(data.mensaje || "Obra actualizada");
            } else {
                CaboSyncAlert.success(data.mensaje || "Obra creada");
                setTimeout(() => window.location.reload(), 800);
            }
        } catch (error) {
            if (error.response?.data?.errors) {
                const primerError = Object.values(error.response.data.errors)[0]?.[0];
                CaboSyncAlert.error(primerError);
            } else {
                CaboSyncAlert.error(
                    error.response?.data?.error || "Error al guardar"
                );
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
function actualizarCardObra(id, cardHtml) {
    const cardActual = document.querySelector(
        `.obra-card[data-obra-id="${id}"]`
    );
    if (!cardActual || !cardHtml) return;

    const temp = document.createElement("div");
    temp.innerHTML = cardHtml.trim();
    const nuevaCard = temp.firstElementChild;
    cardActual.replaceWith(nuevaCard);
}

// ============================================
// LISTENER MAESTRO
// ============================================
document.addEventListener("click", async function (e) {

    // EDITAR
    const btnEditar = e.target.closest(".btn-editar-obra");
    if (btnEditar) {
        e.preventDefault();
        editarObra(btnEditar.dataset.id);
        return;
    }

    // PAUSAR
    const btnPausar = e.target.closest(".btn-pausar-obra");
    if (btnPausar) {
        e.preventDefault();
        const id = btnPausar.dataset.id;
        const nombre = btnPausar.dataset.nombre;

        const confirmado = await CaboSyncAlert.confirm(
            "¿Pausar obra?",
            `La obra ${nombre} quedará en pausa. No se podrá registrar asistencia hasta que sea reactivada.`
        );

        if (!confirmado) return;

        try {
            const { data } = await axios.post(
                `${window.OBRAS_CONFIG.rutas.base}/${id}/pausar`
            );
            if (data.success) {
                actualizarCardObra(id, data.card_html);
                CaboSyncAlert.success("Obra pausada");
            }
        } catch (error) {
            CaboSyncAlert.error(error.response?.data?.error || "Error al pausar");
        }
        return;
    }

    // ACTIVAR
    const btnActivar = e.target.closest(".btn-activar-obra");
    if (btnActivar) {
        e.preventDefault();
        const id = btnActivar.dataset.id;
        const nombre = btnActivar.dataset.nombre;

        const confirmado = await CaboSyncAlert.confirm(
            "¿Activar obra?",
            `La obra ${nombre} volverá a estar activa.`
        );

        if (!confirmado) return;

        try {
            const { data } = await axios.post(
                `${window.OBRAS_CONFIG.rutas.base}/${id}/activar`
            );
            if (data.success) {
                actualizarCardObra(id, data.card_html);
                CaboSyncAlert.success("Obra activada");
            }
        } catch (error) {
            CaboSyncAlert.error(error.response?.data?.error || "Error al activar");
        }
        return;
    }

    // TERMINAR
    const btnTerminar = e.target.closest(".btn-terminar-obra");
    if (btnTerminar) {
        e.preventDefault();
        const id = btnTerminar.dataset.id;
        const nombre = btnTerminar.dataset.nombre;

        const confirmado = await CaboSyncAlert.confirm(
            "¿Marcar obra como terminada?",
            `La obra ${nombre} quedará marcada como terminada. Ya no podrá reactivarse (solo editarse manualmente).`
        );

        if (!confirmado) return;

        try {
            const { data } = await axios.post(
                `${window.OBRAS_CONFIG.rutas.base}/${id}/terminar`
            );
            if (data.success) {
                actualizarCardObra(id, data.card_html);
                CaboSyncAlert.success("Obra terminada");
            }
        } catch (error) {
            CaboSyncAlert.error(error.response?.data?.error || "Error al terminar");
        }
        return;
    }

    // ELIMINAR
    const btnEliminar = e.target.closest(".btn-eliminar-obra");
    if (btnEliminar) {
        e.preventDefault();
        const id = btnEliminar.dataset.id;
        const nombre = btnEliminar.dataset.nombre;

        const confirmado = await CaboSyncAlert.confirmDelete(
            "¿Eliminar obra PERMANENTEMENTE?",
            `Esta acción eliminará ${nombre} de forma irreversible. Solo se permite si no tiene empleados asociados.`
        );

        if (!confirmado) return;

        try {
            const { data } = await axios.delete(
                `${window.OBRAS_CONFIG.rutas.base}/${id}`
            );
            if (data.success) {
                document
                    .querySelector(`.obra-card[data-obra-id="${id}"]`)
                    ?.remove();
                CaboSyncAlert.success("Obra eliminada");

                const contador = document.getElementById("contadorObras");
                const total = document.getElementById("totalRegistros");
                if (contador)
                    contador.textContent = Math.max(0, parseInt(contador.textContent) - 1);
                if (total)
                    total.textContent = Math.max(0, parseInt(total.textContent) - 1);
            }
        } catch (error) {
            CaboSyncAlert.error(error.response?.data?.error || "Error al eliminar");
        }
        return;
    }
});

// ============================================
// EXPONER GLOBALES
// ============================================
window.resetFormObra = resetFormObra;
window.editarObra = editarObra;