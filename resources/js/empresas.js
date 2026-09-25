// ============================================
// CABOSYNC - MÓDULO EMPRESAS
// ============================================

// EMPRESAS_CONFIG se define en el blade empresas/index.blade.php

// ============================================
// RESET FORM (CREAR)
// ============================================
function resetFormEmpresa() {
    const form = document.getElementById("formEmpresa");
    if (!form) return;

    form.reset();
    document.getElementById("empresaId").value = "";
    document.getElementById("modalEmpresaTitulo").innerHTML =
        '<i class="bi bi-building-add"></i> Registrar Nueva Empresa';
    document.getElementById("btnGuardarEmpresaTexto").textContent = "Registrar";
    document.getElementById("campoEstatusEmpresa").style.display = "none";

    if (window.EMPRESAS_CONFIG?.usuario?.esAdmin) {
        document.getElementById("tipo").value = "externa";
    }
}

// ============================================
// EDITAR EMPRESA
// ============================================
async function editarEmpresa(id) {
    try {
        const { data } = await axios.get(
            `${window.EMPRESAS_CONFIG.rutas.base}/${id}`
        );

        if (!data.success) {
            CaboSyncAlert.error(data.error || "Error al cargar empresa");
            return;
        }

        const e = data.empresa;

        document.getElementById("empresaId").value = e.id;
        document.getElementById("nombre").value = e.nombre;
        document.getElementById("rfc").value = e.rfc || "";
        if (document.getElementById("tipo")) {
            document.getElementById("tipo").value = e.tipo;
        }
        if (document.getElementById("estatus")) {
            document.getElementById("estatus").value = e.estatus;
        }

        document.getElementById("modalEmpresaTitulo").innerHTML =
            '<i class="bi bi-pencil-square"></i> Editar Empresa';
        document.getElementById("btnGuardarEmpresaTexto").textContent = "Actualizar";
        document.getElementById("campoEstatusEmpresa").style.display = "block";

        new bootstrap.Modal(document.getElementById("modalEmpresa")).show();
    } catch (error) {
        console.error(error);
        CaboSyncAlert.error("Error al cargar empresa");
    }
}

// ============================================
// GUARDAR (CREAR O EDITAR)
// ============================================
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("formEmpresa");
    if (!form) return;

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const id = document.getElementById("empresaId").value;
        const esEdicion = !!id;

        const btnGuardar = document.getElementById("btnGuardarEmpresa");
        const btnTexto = document.getElementById("btnGuardarEmpresaTexto");
        btnGuardar.disabled = true;
        btnTexto.textContent = "Guardando...";

        const formData = new FormData(this);

        const url = esEdicion
            ? `${window.EMPRESAS_CONFIG.rutas.base}/${id}`
            : window.EMPRESAS_CONFIG.rutas.store;

        try {
            const { data } = await axios.post(url, formData);

            bootstrap.Modal.getInstance(
                document.getElementById("modalEmpresa")
            ).hide();

            if (esEdicion) {
                actualizarCardEmpresa(id, data.card_html);
                CaboSyncAlert.success(data.mensaje || "Empresa actualizada");
            } else {
                // Recargar para ver la nueva empresa en el grid
                CaboSyncAlert.success(data.mensaje || "Empresa creada");
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
function actualizarCardEmpresa(id, cardHtml) {
    const cardActual = document.querySelector(
        `.empresa-card[data-empresa-id="${id}"]`
    );
    if (!cardActual || !cardHtml) return;

    const temp = document.createElement("div");
    temp.innerHTML = cardHtml.trim();
    const nuevaCard = temp.firstElementChild;
    cardActual.replaceWith(nuevaCard);
}

// ============================================
// LISTENER MAESTRO PARA ACCIONES DE CARDS
// ============================================
document.addEventListener("click", async function (e) {

    // ------------------------------------------------
    // EDITAR
    // ------------------------------------------------
    const btnEditar = e.target.closest(".btn-editar-empresa");
    if (btnEditar) {
        e.preventDefault();
        editarEmpresa(btnEditar.dataset.id);
        return;
    }

    // ------------------------------------------------
    // DESACTIVAR
    // ------------------------------------------------
    const btnDesactivar = e.target.closest(".btn-desactivar-empresa");
    if (btnDesactivar) {
        e.preventDefault();
        const id = btnDesactivar.dataset.id;
        const nombre = btnDesactivar.dataset.nombre;

        const confirmado = await CaboSyncAlert.confirm(
            "¿Desactivar empresa?",
            `La empresa ${nombre} quedará inactiva. Sus usuarios no podrán iniciar sesión hasta que sea reactivada.`
        );

        if (!confirmado) return;

        try {
            const { data } = await axios.post(
                `${window.EMPRESAS_CONFIG.rutas.base}/${id}/desactivar`
            );
            if (data.success) {
                actualizarCardEmpresa(id, data.card_html);
                CaboSyncAlert.success("Empresa desactivada");
            }
        } catch (error) {
            CaboSyncAlert.error(
                error.response?.data?.error || "Error al desactivar"
            );
        }
        return;
    }

    // ------------------------------------------------
    // ELIMINAR
    // ------------------------------------------------
    const btnEliminar = e.target.closest(".btn-eliminar-empresa");
    if (btnEliminar) {
        e.preventDefault();
        const id = btnEliminar.dataset.id;
        const nombre = btnEliminar.dataset.nombre;

        const confirmado = await CaboSyncAlert.confirmDelete(
            "¿Eliminar empresa PERMANENTEMENTE?",
            `Esta acción eliminará ${nombre} de forma irreversible. Solo se permite si no tiene usuarios ni obras asociadas.`
        );

        if (!confirmado) return;

        try {
            const { data } = await axios.delete(
                `${window.EMPRESAS_CONFIG.rutas.base}/${id}`
            );
            if (data.success) {
                document
                    .querySelector(`.empresa-card[data-empresa-id="${id}"]`)
                    ?.remove();
                CaboSyncAlert.success("Empresa eliminada");

                const contador = document.getElementById("contadorEmpresas");
                const total = document.getElementById("totalRegistros");
                if (contador)
                    contador.textContent = Math.max(0, parseInt(contador.textContent) - 1);
                if (total)
                    total.textContent = Math.max(0, parseInt(total.textContent) - 1);
            }
        } catch (error) {
            CaboSyncAlert.error(
                error.response?.data?.error || "Error al eliminar"
            );
        }
        return;
    }
});

// ============================================
// EXPONER GLOBALES
// ============================================
window.resetFormEmpresa = resetFormEmpresa;
window.editarEmpresa = editarEmpresa;