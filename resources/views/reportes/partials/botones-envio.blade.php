{{-- ============================================
     BOTONES DE ENVÍO - SOLO MODO LECTURA
     ============================================ --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="mb-1 text-cabosync-primary">
                    <i class="bi bi-send-fill"></i> Enviar reporte
                </h6>
                <small class="text-muted">Envía la lista de asistencia a RH o compártela</small>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-success btn-sm" onclick="compartirWhatsApp()">
                    <i class="bi bi-whatsapp"></i> Compartir por WhatsApp
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="abrirModalCorreo()">
                    <i class="bi bi-envelope-fill"></i> Enviar por Correo
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="descargarReporte('pdf')">
                    <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="descargarReporte('excel')">
                    <i class="bi bi-file-earmark-excel"></i> Descargar Excel
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================
     MODAL ENVIAR CORREO
     ============================================ --}}
<div class="modal fade" id="modalEnviarCorreo" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-envelope-fill"></i> Enviar por Correo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase">
                        Destinatarios <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="correoDestinatarios" class="form-control"
                           placeholder="rh@empresa.com, gerente@empresa.com">
                    <small class="text-muted">Separa múltiples correos con coma</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase">CC (opcional)</label>
                    <input type="text" id="correoCC" class="form-control"
                           placeholder="supervisor@empresa.com">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase">Mensaje</label>
                    <textarea id="correoMensaje" class="form-control" rows="3"></textarea>
                </div>

                <div class="alert alert-info small mb-0">
                    <i class="bi bi-paperclip"></i>
                    Se adjuntarán automáticamente el PDF y el Excel del reporte.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnEnviarCorreo">
                    <i class="bi bi-send-fill"></i> Enviar Correo
                </button>
            </div>
        </div>
    </div>
</div>