// ============================================
// CABOSYNC - MÓDULO REPORTES
// Web Share API + Fallback + Modal Correo
// ============================================

let _reporteContexto = {
    week: '',
    empresa_id: '',
    obra_id: '',
};

// ============================================
// GUARDAR CONTEXTO ACTUAL
// ============================================
function setReporteContexto(ctx) {
    _reporteContexto = { ..._reporteContexto, ...ctx };
}

// ============================================
// COMPARTIR POR WHATSAPP
// ============================================
async function compartirWhatsApp() {
    const ctx = _reporteContexto;

    if (!ctx.week || !ctx.empresa_id) {
        CaboSyncAlert.error('Falta información de la semana');
        return;
    }

    // Detectar soporte de Web Share API nivel 2 (archivos)
    const soportaWebShare = !!(navigator.canShare && navigator.share);

    if (soportaWebShare) {
        // Intentar compartir archivos directamente (solo móvil)
        try {
            CaboSyncLoader.show('Preparando archivos...');

            const [pdfBlob, excelBlob] = await Promise.all([
                fetchBlobReporte('pdf'),
                fetchBlobReporte('excel'),
            ]);

            const pdfFile = new File([pdfBlob], `Reporte-${ctx.week}.pdf`, { type: 'application/pdf' });
            const excelFile = new File([excelBlob], `Reporte-${ctx.week}.xlsx`, {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });

            // Verificar que el navegador puede compartir estos archivos
            if (navigator.canShare({ files: [pdfFile, excelFile] })) {
                await navigator.share({
                    files: [pdfFile, excelFile],
                    title: '',   // Vacío para iOS
                    text: '',    // Vacío para iOS
                });
                CaboSyncAlert.success('Compartido');
                return;
            }
        } catch (error) {
            if (error.name === 'AbortError') return; // Usuario canceló
            console.error('Error compartiendo:', error);
        } finally {
            CaboSyncLoader.hide();
        }
    }

    // Fallback: escritorio o navegador sin soporte
    await compartirWhatsAppFallback();
}

// ============================================
// FALLBACK WHATSAPP (escritorio)
// ============================================
async function compartirWhatsAppFallback() {
    const ctx = _reporteContexto;

    const confirmado = await CaboSyncAlert.confirm(
        'Compartir por WhatsApp',
        'Tu navegador no puede adjuntar archivos directamente. Se descargarán los archivos y se generará un enlace de descarga para compartir por WhatsApp. ¿Continuar?',
        { confirmText: 'Sí, continuar' }
    );

    if (!confirmado) return;

    try {
        CaboSyncLoader.show('Generando archivos...');

        // Descargar automáticamente PDF + Excel
        await Promise.all([
            descargarArchivoBlob(ctx.week, ctx.empresa_id, ctx.obra_id, 'pdf'),
            descargarArchivoBlob(ctx.week, ctx.empresa_id, ctx.obra_id, 'excel'),
        ]);

        // Generar link temporal
        const { data } = await axios.post('/reportes/generar-link', {
            week: ctx.week,
            empresa_id: ctx.empresa_id,
            obra_id: ctx.obra_id || null,
        });

        if (!data.success) {
            CaboSyncAlert.error(data.error || 'Error al generar el enlace');
            return;
        }

        // Abrir WhatsApp con mensaje pre-armado
        const mensaje = `Lista de asistencia semana ${ctx.week}. Descargar: ${data.link}`;
        const urlWA = `https://wa.me/?text=${encodeURIComponent(mensaje)}`;

        window.open(urlWA, '_blank');

        CaboSyncAlert.success('Archivos descargados. Enlace copiado para WhatsApp.');

    } catch (error) {
        console.error(error);
        CaboSyncAlert.error('Error al preparar el compartido');
    } finally {
        CaboSyncLoader.hide();
    }
}

// ============================================
// FETCH BLOB (para Web Share)
// ============================================
async function fetchBlobReporte(tipo) {
    const ctx = _reporteContexto;
    const params = new URLSearchParams({
        week: ctx.week,
        empresa_id: ctx.empresa_id,
        obra_id: ctx.obra_id || '',
    });

    const response = await fetch(`/reportes/blob/${tipo}?${params}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
    });

    if (!response.ok) {
        throw new Error('Error al generar el archivo');
    }

    return await response.blob();
}

// ============================================
// DESCARGAR REPORTE (botón directo)
// ============================================
function descargarReporte(tipo) {
    const ctx = _reporteContexto;
    const params = new URLSearchParams({
        week: ctx.week,
        empresa_id: ctx.empresa_id,
        obra_id: ctx.obra_id || '',
    });
    window.location.href = `/reportes/descargar/${tipo}?${params}`;
}

// ============================================
// DESCARGAR ARCHIVO (vía blob, para fallback)
// ============================================
async function descargarArchivoBlob(week, empresaId, obraId, tipo) {
    const params = new URLSearchParams({
        week: week,
        empresa_id: empresaId,
        obra_id: obraId || '',
    });

    const response = await fetch(`/reportes/blob/${tipo}?${params}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
    });

    if (!response.ok) throw new Error('Error descargando archivo');

    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Reporte-${week}.${tipo === 'pdf' ? 'pdf' : 'xlsx'}`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// ============================================
// MODAL CORREO
// ============================================
function abrirModalCorreo() {
    const ctx = _reporteContexto;

    // Pre-llenar con el RH configurado (si existe)
    const rhEmail = document.querySelector('meta[name="rh-email"]')?.content || '';

    document.getElementById('correoDestinatarios').value = rhEmail;
    document.getElementById('correoCC').value = '';
    document.getElementById('correoMensaje').value =
        `Lista de asistencia semana del ${ctx.semana_texto || ctx.week}.`;

    new bootstrap.Modal(document.getElementById('modalEnviarCorreo')).show();
}

async function enviarCorreo() {
    const ctx = _reporteContexto;

    const destinatarios = document.getElementById('correoDestinatarios').value.trim();
    const cc = document.getElementById('correoCC').value.trim();
    const mensaje = document.getElementById('correoMensaje').value.trim();

    if (!destinatarios) {
        CaboSyncAlert.warning('Debes indicar al menos un destinatario');
        return;
    }

    const btn = document.getElementById('btnEnviarCorreo');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';

    try {
        const { data } = await axios.post('/reportes/enviar-correo', {
            week: ctx.week,
            empresa_id: ctx.empresa_id,
            obra_id: ctx.obra_id || null,
            destinatarios: destinatarios,
            cc: cc,
            mensaje: mensaje,
        });

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEnviarCorreo')).hide();
            CaboSyncAlert.success(data.mensaje || 'Correo enviado correctamente');
        } else {
            CaboSyncAlert.error(data.error || 'Error al enviar');
        }
    } catch (error) {
        console.error(error);
        CaboSyncAlert.error(error?.response?.data?.error || 'Error al enviar el correo');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> Enviar Correo';
    }
}

// ============================================
// INIT
// ============================================
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('btnEnviarCorreo')?.addEventListener('click', enviarCorreo);
});

// Exponer globalmente
window.compartirWhatsApp = compartirWhatsApp;
window.descargarReporte = descargarReporte;
window.abrirModalCorreo = abrirModalCorreo;
window.setReporteContexto = setReporteContexto;