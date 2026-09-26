@extends('layouts.app')

@section('title', 'Reportes')

@push('styles')
    @vite(['resources/css/reportes-ui.css'])
@endpush

@section('content')
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-file-earmark-bar-graph"></i> Módulo de Reportes
                </h2>
                <small class="text-muted">Genera, envía y consulta reportes semanales de asistencia</small>
            </div>
        </div>

        {{-- GENERADOR --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-plus-circle"></i> Generar nuevo reporte
                </h6>
            </div>
            <div class="card-body">
                <form id="formGenerarReporte" class="row g-3 align-items-end">

                    {{-- EMPRESA --}}
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label small fw-bold">
                            Empresa <span class="text-danger">*</span>
                        </label>
                        <select name="empresa_id" id="empresa_id" class="form-select" required>
                            <option value="">Selecciona una empresa</option>
                            @foreach ($empresas as $emp)
                                <option value="{{ $emp->id }}"
                                    {{ (!auth()->user()->esAdministrador() && auth()->user()->empresa_id == $emp->id) ? 'selected' : '' }}>
                                    {{ $emp->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- OBRA --}}
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label small fw-bold">Obra</label>
                        <select name="obra_id" id="obra_id" class="form-select">
                            <option value="">Todas las obras</option>
                            @foreach ($obras as $obra)
                                <option value="{{ $obra->id }}" data-empresa="{{ $obra->empresa_id }}">
                                    {{ $obra->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- SEMANA --}}
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label small fw-bold">
                            Semana <span class="text-danger">*</span>
                        </label>
                        <input type="week" name="week" id="week" class="form-control"
                               value="{{ now()->format('o-\WW') }}" required>
                    </div>

                    {{-- BOTONES DE DESCARGA --}}
                    <div class="col-12 col-md-6 col-lg-3 d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-danger flex-fill"
                                onclick="descargarReporte('pdf')">
                            <i class="bi bi-file-earmark-pdf"></i> PDF
                        </button>
                        <button type="button" class="btn btn-outline-success flex-fill"
                                onclick="descargarReporte('excel')">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                    </div>

                    {{-- BOTONES DE ENVÍO --}}
                    <div class="col-12 d-flex gap-2 flex-wrap justify-content-end">
                        <button type="button" class="btn btn-success"
                                onclick="compartirWhatsApp()">
                            <i class="bi bi-whatsapp"></i> Compartir por WhatsApp
                        </button>
                        <button type="button" class="btn btn-danger"
                                onclick="abrirModalCorreo()">
                            <i class="bi bi-envelope-fill"></i> Enviar por Correo
                        </button>
                        <button type="button" class="btn btn-cabosync-secondary"
                                onclick="generarLink()">
                            <i class="bi bi-link-45deg"></i> Generar link temporal
                        </button>
                    </div>
                </form>

                <div id="linkGenerado" class="alert alert-info mt-3 d-none">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <strong><i class="bi bi-link-45deg"></i> Link generado:</strong>
                            <div class="mt-1">
                                <a href="#" id="linkPublicoUrl" target="_blank" class="text-break"></a>
                            </div>
                            <small class="text-muted">
                                Expira: <span id="linkExpira"></span>
                            </small>
                        </div>
                        <button class="btn btn-sm btn-cabosync-primary" onclick="copiarLink()">
                            <i class="bi bi-clipboard"></i> Copiar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- HISTORIAL --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-clock-history"></i> Historial de reportes generados
                </h6>

                <div class="d-flex gap-2 flex-wrap">
                    <select id="filtroEmpresa" class="form-select form-select-sm" style="width: auto;">
                        @if (auth()->user()->esAdministrador())
                            <option value="">Todas las empresas</option>
                        @endif
                        @foreach ($empresas as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                        @endforeach
                    </select>

                    <select id="filtroEstado" class="form-select form-select-sm" style="width: auto;">
                        <option value="">Todos</option>
                        <option value="vigentes">Vigentes</option>
                        <option value="expirados">Expirados</option>
                    </select>

                    <button class="btn btn-sm btn-outline-secondary" onclick="cargarHistorial()">
                        <i class="bi bi-arrow-clockwise"></i> Actualizar
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div id="historialLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>

                <div id="historialVacio" class="text-center py-5 d-none">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.4;"></i>
                    <p class="text-muted mt-2 mb-0">No hay reportes generados con esos filtros.</p>
                </div>

                <div id="historialContenido" class="table-responsive">
                    <table class="table table-hover align-middle mb-0 reportes-tabla">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Fecha</th>
                                <th>Empresa</th>
                                <th>Obra</th>
                                <th>Semana</th>
                                <th>Vigencia</th>
                                <th>Descargas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="historialBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- MODAL ENVIAR CORREO --}}
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
                        <input type="text" id="correoCC" class="form-control" placeholder="supervisor@empresa.com">
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
@endsection

@push('scripts')
    <script>
        window.REPORTES_CONFIG = {
            rutas: {
                index: "{{ route('reportes.index') }}",
                historial: "{{ route('reportes.historial') }}",
                descargar: "{{ url('reportes/descargar') }}",
                blob: "{{ url('reportes/blob') }}",
                generarLink: "{{ route('reportes.generarLink') }}",
                enviarCorreo: "{{ route('reportes.enviarCorreo') }}",
            },
            usuario: {
                esAdmin: {{ auth()->user()->esAdministrador() ? 'true' : 'false' }},
                empresaId: {{ auth()->user()->empresa_id ?? 'null' }},
            }
        };
    </script>

    @vite(['resources/js/reportes-ui.js'])
@endpush