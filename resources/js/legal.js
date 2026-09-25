// ============================================
// CABOSYNC - MÓDULO LEGAL
// ============================================

// ============================================
// MODAL BLOQUEANTE DE ACEPTACIÓN
// ============================================
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalAceptarLegal");
    if (!modal) return;

    // Mostrar el modal (bloqueante)
    const bsModal = new bootstrap.Modal(modal, {
        backdrop: 'static',
        keyboard: false
    });
    bsModal.show();

    // ============================================
    // Detectar scroll al final de cada pestaña
    // ============================================
    document.querySelectorAll(".legal-scroll").forEach((scroll) => {
        const tipo = scroll.dataset.legal;
        const checkbox = document.querySelector(`.legal-check[data-legal="${tipo}"]`);
        if (!checkbox) return;

        const checkScroll = () => {
            const bottom = scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight;
            if (bottom < 30) {
                checkbox.disabled = false;
            }
        };

        // Verificar si ya está al final (contenido corto)
        setTimeout(checkScroll, 200);

        scroll.addEventListener("scroll", checkScroll);
    });

    // ============================================
    // Habilitar botón cuando ambos checkboxes estén marcados
    // ============================================
    const btnAceptar = document.getElementById("btnAceptarLegal");
    const checkTerminos = document.getElementById("checkTerminos");
    const checkAviso = document.getElementById("checkAviso");

    function validarBoton() {
        btnAceptar.disabled = !(checkTerminos.checked && checkAviso.checked);
    }

    checkTerminos?.addEventListener("change", validarBoton);
    checkAviso?.addEventListener("change", validarBoton);

    // ============================================
    // Acción de aceptar
    // ============================================
    btnAceptar?.addEventListener("click", async function () {
        btnAceptar.disabled = true;
        btnAceptar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

        try {
            const { data } = await axios.post("/legal/aceptar");

            if (data.success) {
                CaboSyncAlert.success("Gracias por aceptar. Recargando...");
                setTimeout(() => window.location.reload(), 1000);
            } else {
                CaboSyncAlert.error(data.error || "Error al guardar");
                btnAceptar.disabled = false;
                btnAceptar.innerHTML = '<i class="bi bi-check-lg"></i> Aceptar y continuar';
            }
        } catch (error) {
            console.error(error);
            CaboSyncAlert.error(error.response?.data?.error || "Error al guardar");
            btnAceptar.disabled = false;
            btnAceptar.innerHTML = '<i class="bi bi-check-lg"></i> Aceptar y continuar';
        }
    });
});

// ============================================
// GUARDAR TEXTOS (Admin)
// ============================================
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("formLegal");
    if (!form) return;

    form.addEventListener("submit", async function (e) {
        e.preventDefault();
        const btn = document.getElementById("btnGuardarLegal");
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

        const formData = new FormData(this);

        try {
            const { data } = await axios.post("/configuracion/legal/guardar", formData);

            if (data.success) {
                CaboSyncAlert.success(data.mensaje || "Textos actualizados");
            } else {
                CaboSyncAlert.error(data.error || "Error al guardar");
            }
        } catch (error) {
            console.error(error);
            if (error.response?.data?.errors) {
                const primerError = Object.values(error.response.data.errors)[0]?.[0];
                CaboSyncAlert.error(primerError);
            } else {
                CaboSyncAlert.error(error.response?.data?.error || "Error al guardar");
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Guardar cambios';
        }
    });
});