@extends('layouts.app')

@section('title', 'Conciliación')

@push('styles')
    @vite(['resources/css/conciliacion.css', 'resources/css/reportes.css'])
@endpush

@section('content')
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-shuffle"></i> Conciliación de Asistencia
                </h2>
                <small class="text-muted">
                    Semana del <span id="semanaInicio">-</span> al <span id="semanaFin">-</span>
                    &nbsp;|&nbsp; Hoy: <strong id="hoyTexto">-</strong>
                </small>
            </div>

            <div class="d-flex gap-2 align-items-center flex-wrap">
                @if (auth()->user()->esContratista() || auth()->user()->esAdministrador())
                    <button type="button" class="btn btn-outline-cabosync-primary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#modalPermisosJefes">
                        <i class="bi bi-person-check"></i> Permisos de Jefes
                    </button>
                @endif
            </div>
        </div>

        {{-- FILTROS --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    @if (auth()->user()->esAdministrador() || auth()->user()->esContratista() && $empresas->count() > 0)
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Empresa</label>
                            <select id="filtroEmpresa" class="form-select form-select-sm">
                                <option value="" disabled>Todas</option>
                                @foreach ($empresas as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Obra</label>
                        <select id="filtroObra" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            @foreach ($obras as $obra)
                                <option value="{{ $obra->id }}">{{ $obra->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Semana</label>
                        <input type="week" id="filtroWeek" class="form-control form-control-sm"
                            value="{{ $weekInput }}">
                    </div>

                    <div class="col-md-3 d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-cabosync-primary" onclick="cargarMatriz()">
                            <i class="bi bi-arrow-clockwise"></i> Cargar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodoJefe()">
                            <i class="bi bi-person-badge"></i> Todo Jefe
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodoSeguridad()">
                            <i class="bi bi-shield-fill"></i> Todo Seguridad
                        </button>
                        <button type="button" class="btn btn-sm btn-cabosync-secondary"
                            onclick="conciliarSemanaCompleta()">
                            <i class="bi bi-check2-circle"></i> Conciliar semana completa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- LEYENDA --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex gap-3 flex-wrap small">
                        <span><span class="badge bg-success">&nbsp;</span> Coinciden</span>
                        <span><span class="badge bg-warning text-dark">&nbsp;</span> Discrepancia</span>
                        <span><span class="badge bg-secondary">&nbsp;</span> Sin datos</span>
                        <span><span class="badge bg-info text-dark">&nbsp;</span> Conciliado</span>
                        <span><span class="badge bg-dark">&nbsp;</span> Bloqueado</span>
                    </div>
                    <div class="small text-muted">
                        <strong>J:</strong> Jefe de Obra &nbsp;|&nbsp;
                        <strong>S:</strong> Seguridad
                    </div>
                </div>
            </div>
        </div>

        {{-- PAGINACIÓN SUPERIOR --}}
        <div id="paginacionTop" class="paginacion-conciliacion d-none">
            <div class="small text-muted" id="paginacionTopTexto"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginacionTopLinks"></ul>
            </nav>
        </div>

        {{-- CONTENEDOR PRINCIPAL --}}
        <div id="conciliacionContenedor">
            <div id="conciliacionLoading" class="text-center py-5">
                <div class="spinner-border text-cabosync-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-muted mt-2 mb-0">Cargando conciliación...</p>
            </div>

            <div id="conciliacionEmpty" class="empty-state d-none">
                <i class="bi bi-people"></i>
                <h4>No hay empleados registrados</h4>
                <p class="text-muted">Ajusta los filtros o registra empleados primero.</p>
            </div>

            <div id="conciliacionContenido" class="d-none">
                <div class="scroll-hint-text">
                    <i class="bi bi-arrows-move"></i> Desliza horizontalmente para ver todos los días
                </div>
                <div class="tabla-conciliacion-wrapper">
                    <table class="tabla-conciliacion" id="tablaConciliacion">
                        <thead>
                            <tr>
                                <th class="col-empleado">Empleado</th>
                                <th class="col-puesto">Puesto</th>
                                {{-- Días L M M J V S se llenan dinámicamente --}}
                            </tr>
                        </thead>
                        <tbody id="tbodyConciliacion"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Botones de envío — solo modo lectura --}}
        <div id="botonesEnvioReporte" class="d-none">
            @include('reportes.partials.botones-envio')
        </div>

        {{-- PAGINACIÓN INFERIOR --}}
        <div id="paginacionBottom" class="paginacion-conciliacion d-none mt-3">
            <div class="small text-muted" id="paginacionBottomTexto"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginacionBottomLinks"></ul>
            </nav>
        </div>

    </div>

    {{-- ============================================
     MODAL DETALLE CELDA / CONCILIAR DÍA
     ============================================ --}}
    <div class="modal fade" id="modalDetalleCelda" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-cabosync-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-shuffle"></i> Detalle de Conciliación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="detalleCeldaBody">
                    {{-- Se llena dinámicamente --}}
                </div>

                <div class="modal-footer" id="detalleCeldaFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================
     MODAL JUSTIFICAR
     ============================================ --}}
    <div class="modal fade" id="modalJustificarConciliacion" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formJustificarConciliacion" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="justificarConcEmpleadoId" name="empleado_id">
                    <input type="hidden" id="justificarConcFecha" name="fecha">
                    <input type="hidden" id="justificarConcAdoptar" name="adoptar" value="justificado">

                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle-fill"></i> Justificar Falta
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-info small mb-3">
                            <i class="bi bi-person-fill"></i>
                            <strong id="justificarConcNombre">-</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Motivo <span
                                    class="text-danger">*</span></label>
                            <select name="motivo" id="justificarConcMotivo" class="form-select" required>
                                <option value="">Selecciona un motivo</option>
                                <option value="permiso">Permiso</option>
                                <option value="enfermedad">Enfermedad</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Descripción (opcional)</label>
                            <textarea name="descripcion" id="justificarConcDescripcion" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Evidencia (opcional)</label>
                            <input type="file" name="evidencia" id="justificarConcEvidencia" class="form-control"
                                accept="image/jpeg,image/png,application/pdf">
                            <small class="text-muted">JPG, PNG o PDF. Máx 5MB.</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning" id="btnGuardarJustificacionConc">
                            <i class="bi bi-check-lg"></i> Guardar Justificación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================
     MODAL PERMISOS DE JEFES
     ============================================ --}}
    <div class="modal fade" id="modalPermisosJefes" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-cabosync-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-check"></i> Permisos de Conciliación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted">
                        Activa el permiso para que un Jefe de Obra pueda conciliar la semana actual.
                    </p>
                    <div id="listaJefesPermisos">
                        <div class="text-center py-3">
                            <div class="spinner-border text-cabosync-primary" role="status"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite(['resources/js/reportes.js'])
    <script>
        // ============================================
        // ESTADO GLOBAL
        // ============================================
        let conciliacionData = null;
        let filtrosConciliacion = {
            empresa_id: '',
            obra_id: '',
            week: '',
            page: 1,
        };

        // ============================================
        // INICIALIZACIÓN
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            cargarMatriz();

            document.getElementById('filtroEmpresa')?.addEventListener('change', () => {
                filtrosConciliacion.page = 1;
                cargarMatriz();
            });
            document.getElementById('filtroObra')?.addEventListener('change', () => {
                filtrosConciliacion.page = 1;
                cargarMatriz();
            });
            document.getElementById('filtroWeek')?.addEventListener('change', () => {
                filtrosConciliacion.page = 1;
                cargarMatriz();
            });

            document.getElementById('formJustificarConciliacion')?.addEventListener('submit',
                guardarJustificacionConciliacion);
            document.getElementById('modalPermisosJefes')?.addEventListener('show.bs.modal', cargarListaJefes);
        });

        // ============================================
        // CARGAR MATRIZ
        // ============================================
        async function cargarMatriz() {
            document.getElementById('conciliacionLoading').classList.remove('d-none');
            document.getElementById('conciliacionContenido').classList.add('d-none');
            document.getElementById('conciliacionEmpty').classList.add('d-none');
            document.getElementById('paginacionTop').classList.add('d-none');
            document.getElementById('paginacionBottom').classList.add('d-none');

            filtrosConciliacion.empresa_id = document.getElementById('filtroEmpresa')?.value || '';
            filtrosConciliacion.obra_id = document.getElementById('filtroObra')?.value || '';
            filtrosConciliacion.week = document.getElementById('filtroWeek')?.value || '';

            try {
                const params = new URLSearchParams(filtrosConciliacion);
                const {
                    data
                } = await axios.get(`{{ route('conciliacion.datos') }}?${params}`);

                if (!data.success) {
                    CaboSyncAlert.error(data.error || 'Error al cargar');
                    return;
                }

                conciliacionData = data;

                document.getElementById('semanaInicio').textContent = data.semana.inicio;
                document.getElementById('semanaFin').textContent = data.semana.fin;
                document.getElementById('hoyTexto').textContent = formatearFechaTitulo(data.hoy);

                renderizarMatriz(data);

                document.getElementById('conciliacionLoading').classList.add('d-none');

                if (data.total_empleados === 0) {
                    document.getElementById('conciliacionEmpty').classList.remove('d-none');
                } else {
                    document.getElementById('conciliacionContenido').classList.remove('d-none');
                    renderizarPaginacion(data.paginacion);
                    document.getElementById('paginacionTop').classList.remove('d-none');
                    document.getElementById('paginacionBottom').classList.remove('d-none');
                }

            } catch (error) {
                console.error(error);
                document.getElementById('conciliacionLoading').classList.add('d-none');
            }
        }

        // ============================================
        // RENDERIZAR MATRIZ
        // ============================================
        function renderizarMatriz(data) {
            const theadTr = document.querySelector('#tablaConciliacion thead tr');
            const tbody = document.getElementById('tbodyConciliacion');

            const modoLectura = data.modo === 'lectura';

            // =========================================================
            // Ajustar UI según el modo
            // =========================================================
            const btnJefe = document.querySelector('button[onclick="marcarTodoJefe()"]');
            const btnSeguridad = document.querySelector('button[onclick="marcarTodoSeguridad()"]');
            const btnCompleta = document.querySelector('button[onclick="conciliarSemanaCompleta()"]');

            if (modoLectura) {
                btnJefe?.classList.add('d-none');
                btnSeguridad?.classList.add('d-none');
                btnCompleta?.classList.add('d-none');
                mostrarBannerLectura(data.semana, {{ auth()->user()->esAdministrador() ? 'true' : 'false' }});
            } else {
                btnJefe?.classList.remove('d-none');
                btnSeguridad?.classList.remove('d-none');
                btnCompleta?.classList.remove('d-none');
                ocultarBannerLectura();
            }

            // Mostrar/ocultar botones de envío
            const botonesEnvio = document.getElementById('botonesEnvioReporte');
            if (modoLectura) {
                botonesEnvio?.classList.remove('d-none');

                // Configurar contexto para los reportes
                if (typeof setReporteContexto === 'function') {
                    setReporteContexto({
                        week: data.semana.week,
                        empresa_id: filtrosConciliacion.empresa_id || '{{ auth()->user()->empresa_id ?? '' }}',
                        obra_id: filtrosConciliacion.obra_id || '',
                        semana_texto: `${data.semana.inicio} al ${data.semana.fin}`,
                    });
                }
            } else {
                botonesEnvio?.classList.add('d-none');
            }

            // =========================================================
            // Reconstruir thead
            // =========================================================
            theadTr.innerHTML = '<th class="col-empleado">Empleado</th>';
            theadTr.innerHTML += '<th class="col-puesto">Puesto</th>';

            Object.entries(data.semana.fechas).forEach(([fecha, letra]) => {
                const diaNum = new Date(fecha + 'T00:00:00').getDate();
                const esHoy = fecha === data.hoy;
                theadTr.innerHTML += `
            <th class="col-dia ${esHoy ? 'col-dia-hoy' : ''}">
                ${letra}<br><small>${diaNum}</small>
            </th>
        `;
            });

            if (!modoLectura) {
                theadTr.innerHTML += '<th class="col-discrepancias">Disp.</th>';
            }

            // =========================================================
            // Reconstruir tbody
            // =========================================================
            tbody.innerHTML = '';

            data.empleados.forEach(emp => {
                const tr = document.createElement('tr');
                tr.className = 'fila-empleado';
                tr.dataset.empleadoId = emp.id;

                let html = `
            <td>
                <div class="empleado-info-simple">
                    <img src="${emp.foto_url}" alt="${emp.nombre_completo}" class="empleado-foto"
                         onerror="this.src='{{ asset('img/avatar-default.png') }}'">
                    <p class="empleado-nombre">${emp.nombre_completo}</p>
                </div>
            </td>
            <td class="celda-puesto">
                <span class="badge bg-cabosync-primary">${emp.puesto_cargo}</span>
            </td>
        `;

                Object.keys(data.semana.fechas).forEach(fecha => {
                    html += modoLectura ?
                        renderizarCeldaLectura(emp, fecha, data) :
                        renderizarCeldaConciliacion(emp, fecha, data);
                });

                if (!modoLectura) {
                    html += `
                <td class="celda-discrepancias">
                    ${emp.discrepancias > 0
                        ? `<span class="badge bg-warning text-dark">${emp.discrepancias}</span>`
                        : `<span class="badge bg-success">0</span>`}
                </td>
            `;
                }

                tr.innerHTML = html;
                tbody.appendChild(tr);
            });
        }

        // =========================================================
        // CELDA MODO LECTURA
        // =========================================================
        function renderizarCeldaLectura(emp, fecha, data) {
            const celda = emp.matriz[fecha];
            const esHoy = fecha === data.hoy;

            let clase = 'sin-datos';
            let icono = '🚫';
            let texto = '';
            let tooltip = 'Sin asistencia ni falta registrada';

            if (celda.final && celda.final.estado_final) {
                switch (celda.final.estado_final) {
                    case 'asistencia':
                        clase = 'estado-presente';
                        icono = '✓';
                        texto = 'Asistió';
                        tooltip = `Asistió (${celda.final.origen_adoptado})`;
                        break;
                    case 'asistencia_justificada':
                        clase = 'estado-justificada';
                        icono = '⚠';
                        texto = 'Justificada';
                        tooltip = `Falta justificada (${celda.final.origen_adoptado})`;
                        break;
                    case 'falta_injustificada':
                        clase = 'estado-falta';
                        icono = '✗';
                        texto = 'Falta';
                        tooltip = `Falta injustificada (${celda.final.origen_adoptado})`;
                        break;
                }
            }

            return `
        <td class="celda-lectura ${clase} ${esHoy ? 'celda-hoy' : ''}"
            data-empleado-id="${emp.id}"
            data-fecha="${fecha}"
            title="${tooltip}">
            <div class="celda-lectura__contenido">
                <span class="celda-lectura__icono">${icono}</span>
                <span class="celda-lectura__texto">${texto}</span>
            </div>
        </td>
    `;
        }

        // =========================================================
        // BANNER DE MODO LECTURA
        // =========================================================
        function mostrarBannerLectura(semana, esAdmin) {
            let banner = document.getElementById('bannerLectura');
            if (!banner) {
                banner = document.createElement('div');
                banner.id = 'bannerLectura';
                banner.className = 'alert alert-info d-flex justify-content-between align-items-center mb-3';

                const contenedorFiltros = document.querySelector('.card.border-0.shadow-sm.mb-4');
                contenedorFiltros.parentNode.insertBefore(banner, contenedorFiltros.nextSibling);
            }

            banner.innerHTML = `
        <div>
            <i class="bi bi-lock-fill"></i>
            <strong>Semana conciliada.</strong>
            Esta semana ya fue conciliada y está bloqueada para edición.
            ${esAdmin ? 'Como Admin puedes desbloquearla para volver a editar.' : 'Solo el Admin puede desbloquearla.'}
        </div>
        ${esAdmin ? `
                            <button type="button" class="btn btn-sm btn-danger" onclick="desbloquearSemanaCompleta()">
                                <i class="bi bi-unlock"></i> Desbloquear semana
                            </button>
                        ` : ''}
    `;
            banner.classList.remove('d-none');
        }

        function ocultarBannerLectura() {
            const banner = document.getElementById('bannerLectura');
            if (banner) banner.classList.add('d-none');
        }

        // =========================================================
        // CONCILIAR SEMANA COMPLETA
        // =========================================================
        async function conciliarSemanaCompleta() {
            if (!conciliacionData) {
                CaboSyncAlert.warning('Primero carga la semana');
                return;
            }

            // Contar discrepancias y celdas pendientes
            let discrepanciasPendientes = 0;
            let celdasPendientes = 0;

            conciliacionData.empleados.forEach(emp => {
                Object.keys(emp.matriz).forEach(fecha => {
                    const celda = emp.matriz[fecha];

                    if (celda.final) return;

                    celdasPendientes++;

                    if (celda.discrepancia) {
                        discrepanciasPendientes++;
                    }
                });
            });

            // Si hay discrepancias pendientes
            if (discrepanciasPendientes > 0) {
                CaboSyncAlert.warning(
                    `Hay ${discrepanciasPendientes} discrepancias pendientes. ` +
                    `Resuélvelas antes de conciliar la semana completa.`
                );
                return;
            }

            // No hay discrepancias → conciliar todo
            const continuar = await CaboSyncAlert.confirm(
                '¿Conciliar semana completa?',
                `Se generarán registros definitivos para todos los días de la semana. ` +
                `Si no hay discrepancias pendientes, la semana se bloqueará para edición.`, {
                    confirmText: 'Sí, conciliar semana'
                }
            );

            if (!continuar) return;

            try {
                const {
                    data
                } = await axios.post(
                    "{{ route('conciliacion.conciliarSemanaCompleta') }}", {
                        week: filtrosConciliacion.week,
                        empresa_id: filtrosConciliacion.empresa_id || null,
                        obra_id: filtrosConciliacion.obra_id || null,
                    }
                );

                if (data.success) {
                    CaboSyncAlert.success(
                        `Semana conciliada. ${data.generados} días registrados, ${data.nulos} sin asistencia.`
                    );
                    await cargarMatriz();
                }
            } catch (error) {
                console.error(error);
                const msg = error?.response?.data?.error || 'Error al conciliar la semana';

                if (error?.response?.status === 422 && error?.response?.data?.discrepancias_pendientes) {
                    CaboSyncAlert.warning(
                        `Hay ${error.response.data.discrepancias_pendientes} discrepancias pendientes. ` +
                        `Resuélvelas antes de conciliar la semana completa.`
                    );
                } else {
                    CaboSyncAlert.error(msg);
                }
            }
        }

        // =========================================================
        // DESBLOQUEAR SEMANA COMPLETA
        // =========================================================
        async function desbloquearSemanaCompleta() {
            const confirmado = await CaboSyncAlert.confirmDelete(
                '¿Desbloquear semana completa?',
                'Se reabrirá la edición de todos los registros de esta semana. Volverán a mostrarse las discrepancias entre Jefe y Seguridad.', {
                    confirmText: 'Sí, desbloquear'
                }
            );

            if (!confirmado) return;

            try {
                const {
                    data
                } = await axios.post(
                    "{{ route('conciliacion.desbloquearSemana') }}", {
                        week: filtrosConciliacion.week,
                        empresa_id: filtrosConciliacion.empresa_id || null,
                        obra_id: filtrosConciliacion.obra_id || null,
                    }
                );

                if (data.success) {
                    CaboSyncAlert.success(`Semana desbloqueada. ${data.desbloqueados} registros editables.`);
                    await cargarMatriz();
                }
            } catch (error) {
                console.error(error);
                CaboSyncAlert.error(error?.response?.data?.error || 'Error al desbloquear');
            }
        }

        // ============================================
        // CELDA DE CONCILIACIÓN
        // ============================================
        function renderizarCeldaConciliacion(emp, fecha, data) {
            const celda = emp.matriz[fecha];
            const esHoy = fecha === data.hoy;

            let clase = 'sin-datos';
            let etiquetaJ = '—';
            let etiquetaS = '—';

            if (celda.jefe) {
                etiquetaJ = celda.jefe.estado === 'presente' ? '✓' : (celda.jefe.es_justificada ? '⚠' : '✗');
            }
            if (celda.seguridad) {
                etiquetaS = celda.seguridad.estado === 'presente' ? '✓' : (celda.seguridad.es_justificada ? '⚠' : '✗');
            }

            let badgeEstado = '';

            if (celda.final) {
                clase = celda.final.bloqueado ? 'conciliado-bloqueado' : 'conciliado';
                const origen = celda.final.origen_adoptado === 'jefe_obra' ? 'J' :
                    (celda.final.origen_adoptado === 'seguridad' ? 'S' : '★');
                badgeEstado = celda.final.bloqueado ?
                    `<span class="celda-badge"><i class="bi bi-lock-fill"></i> ${origen}</span>` :
                    `<span class="celda-badge"><i class="bi bi-check-circle"></i> ${origen}</span>`;
            } else if (celda.discrepancia) {
                clase = 'discrepancia';
                badgeEstado = `<span class="celda-badge">⚠</span>`;
            } else if (celda.jefe || celda.seguridad) {
                clase = 'coincide';
            }

            return `
        <td class="celda-conciliacion ${clase} ${esHoy ? 'celda-hoy' : ''}"
            data-empleado-id="${emp.id}"
            data-fecha="${fecha}"
            data-discrepancia="${celda.discrepancia ? '1' : '0'}"
            title="${formatearFechaTitulo(fecha)}">
            <div class="celda-conciliacion__contenido">
                <div class="celda-conciliacion__marcas">
                    <span class="marca-jefe" title="Jefe de Obra">J: ${etiquetaJ}</span>
                    <span class="marca-seguridad" title="Seguridad">S: ${etiquetaS}</span>
                </div>
                ${badgeEstado}
            </div>
        </td>
    `;
        }

        // ============================================
        // CLICK EN CELDA → ABRIR MODAL DETALLE
        // ============================================
        document.addEventListener('click', async function(e) {
            const celda = e.target.closest('.celda-conciliacion');
            if (!celda) return;

            const empleadoId = celda.dataset.empleadoId;
            const fecha = celda.dataset.fecha;

            await abrirDetalleCelda(empleadoId, fecha);
        });

        // ============================================
        // ABRIR MODAL DETALLE
        // ============================================
        async function abrirDetalleCelda(empleadoId, fecha) {
            try {
                const {
                    data
                } = await axios.get(
                    `{{ route('conciliacion.detalle') }}?empleado_id=${empleadoId}&fecha=${fecha}`, {
                        skipPreloader: true
                    }
                );

                if (!data.success) {
                    CaboSyncAlert.error(data.error || 'Error al cargar detalle');
                    return;
                }

                const emp = data.empleado;
                const jefe = data.jefe;
                const seguridad = data.seguridad;
                const final = data.final;
                const puede = data.puede_conciliar;

                let html = `
            <div class="alert alert-info small mb-3">
                <div class="d-flex align-items-center gap-2">
                    <img src="${emp.foto_url}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                    <div>
                        <strong>${emp.nombre_completo}</strong><br>
                        <small>${emp.puesto_cargo} — ${formatearFechaTitulo(fecha)}</small>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="card-detalle ${jefe ? 'tiene-registro' : 'sin-registro'}">
                        <div class="card-detalle__titulo">
                            <i class="bi bi-person-badge"></i> Jefe de Obra
                        </div>
                        ${jefe ? `
                                                    <div class="card-detalle__cuerpo">
                                                        <p class="mb-1"><strong>Estado:</strong> ${etiquetaEstado(jefe.estado, jefe.es_justificada)}</p>
                                                        <p class="mb-1"><small>Registrado por: ${jefe.usuario || 'N/D'}</small></p>
                                                        <p class="mb-0"><small>Última actualización: ${jefe.updated_at || 'N/D'}</small></p>
                                                    </div>
                                                ` : '<div class="card-detalle__cuerpo text-muted">Sin registro</div>'}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card-detalle ${seguridad ? 'tiene-registro' : 'sin-registro'}">
                        <div class="card-detalle__titulo">
                            <i class="bi bi-shield-fill"></i> Seguridad
                        </div>
                        ${seguridad ? `
                                                    <div class="card-detalle__cuerpo">
                                                        <p class="mb-1"><strong>Estado:</strong> ${etiquetaEstado(seguridad.estado, seguridad.es_justificada)}</p>
                                                        <p class="mb-1"><small>Registrado por: ${seguridad.usuario || 'N/D'}</small></p>
                                                        <p class="mb-0"><small>Última actualización: ${seguridad.updated_at || 'N/D'}</small></p>
                                                    </div>
                                                ` : '<div class="card-detalle__cuerpo text-muted">Sin registro</div>'}
                    </div>
                </div>
            </div>
        `;

                if (final) {
                    html += `
                <div class="alert alert-${final.bloqueado ? 'dark' : 'info'} small mb-0">
                    <i class="bi bi-${final.bloqueado ? 'lock-fill' : 'check-circle'}"></i>
                    <strong>Ya conciliado:</strong> ${final.estado_final} (${final.origen_adoptado})
                    <br>
                    <small>Conciliado el ${final.conciliado_en}</small>
                    ${final.bloqueado ? '<br><small class="text-warning">🔒 Bloqueado. Solo Admin puede desbloquear.</small>' : ''}
                </div>
            `;
                }

                document.getElementById('detalleCeldaBody').innerHTML = html;

                let footerHtml =
                    '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';

                if (puede && !final?.bloqueado) {
                    footerHtml = `
                <button type="button" class="btn btn-outline-cabosync-primary"
                        onclick="adoptarDia(${empleadoId}, '${fecha}', 'jefe_obra')"
                        ${!jefe ? 'disabled' : ''}>
                    <i class="bi bi-person-badge"></i> Adoptar Jefe
                </button>
                <button type="button" class="btn btn-outline-cabosync-primary"
                        onclick="adoptarDia(${empleadoId}, '${fecha}', 'seguridad')"
                        ${!seguridad ? 'disabled' : ''}>
                    <i class="bi bi-shield-fill"></i> Adoptar Seguridad
                </button>
                <button type="button" class="btn btn-warning"
                        onclick="abrirJustificacionConciliacion(${empleadoId}, '${fecha}', '${emp.nombre_completo.replace(/'/g, "\\'")}')">
                    <i class="bi bi-exclamation-triangle"></i> Justificar
                </button>
            `;
                }

                if (puede && final?.bloqueado && {{ auth()->user()->esAdministrador() ? 'true' : 'false' }}) {
                    footerHtml = `
                <button type="button" class="btn btn-danger"
                        onclick="desbloquearConciliacion(${empleadoId}, '${fecha}')">
                    <i class="bi bi-unlock"></i> Desbloquear
                </button>
            ` + footerHtml;
                }

                document.getElementById('detalleCeldaFooter').innerHTML = footerHtml;

                new bootstrap.Modal(document.getElementById('modalDetalleCelda')).show();

            } catch (error) {
                console.error(error);
                CaboSyncAlert.error('Error al cargar el detalle');
            }
        }

        function etiquetaEstado(estado, justificada) {
            if (estado === 'presente') return '<span class="badge bg-success">Presente</span>';
            if (estado === 'falta' && justificada)
                return '<span class="badge bg-warning text-dark">Falta Justificada</span>';
            if (estado === 'falta') return '<span class="badge bg-danger">Falta</span>';
            return estado;
        }

        // ============================================
        // ADOPTAR DÍA
        // ============================================
        async function adoptarDia(empleadoId, fecha, adoptar) {
            const confirmado = await CaboSyncAlert.confirm(
                '¿Confirmar conciliación?',
                adoptar === 'jefe_obra' ?
                'Se adoptará la versión del Jefe de Obra para este día.' :
                'Se adoptará la versión de Seguridad para este día.', {
                    confirmText: 'Sí, conciliar'
                }
            );

            if (!confirmado) return;

            try {
                const {
                    data
                } = await axios.post("{{ route('conciliacion.conciliarDia') }}", {
                    empleado_id: empleadoId,
                    fecha: fecha,
                    adoptar: adoptar,
                });

                if (data.success) {
                    CaboSyncAlert.success('Día conciliado');
                    bootstrap.Modal.getInstance(document.getElementById('modalDetalleCelda')).hide();
                    await cargarMatriz();
                }
            } catch (error) {
                console.error(error);
                CaboSyncAlert.error(error?.response?.data?.error || 'Error al conciliar');
            }
        }

        // ============================================
        // JUSTIFICAR
        // ============================================
        function abrirJustificacionConciliacion(empleadoId, fecha, nombre) {
            document.getElementById('formJustificarConciliacion').reset();
            document.getElementById('justificarConcEmpleadoId').value = empleadoId;
            document.getElementById('justificarConcFecha').value = fecha;
            document.getElementById('justificarConcNombre').textContent = nombre;

            bootstrap.Modal.getInstance(document.getElementById('modalDetalleCelda')).hide();
            new bootstrap.Modal(document.getElementById('modalJustificarConciliacion')).show();
        }

        async function guardarJustificacionConciliacion(e) {
            e.preventDefault();

            const form = document.getElementById('formJustificarConciliacion');
            const formData = new FormData(form);
            const btn = document.getElementById('btnGuardarJustificacionConc');

            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';

            try {
                const {
                    data
                } = await axios.post("{{ route('conciliacion.conciliarDia') }}", formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalJustificarConciliacion')).hide();
                    CaboSyncAlert.success('Falta justificada y conciliada');
                    await cargarMatriz();
                }
            } catch (error) {
                console.error(error);
                CaboSyncAlert.error(error?.response?.data?.error || 'Error al justificar');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Guardar Justificación';
            }
        }

        // ============================================
        // ACCIONES MASIVAS
        // ============================================
        async function marcarTodoJefe() {
            await conciliarMasivo('jefe_obra');
        }

        async function marcarTodoSeguridad() {
            await conciliarMasivo('seguridad');
        }

        async function conciliarMasivo(adoptar) {
            const confirmado = await CaboSyncAlert.confirm(
                '¿Conciliar toda la semana?',
                adoptar === 'jefe_obra' ?
                'Se adoptará la versión del Jefe de Obra en TODOS los días con registro. Los días ya conciliados y bloqueados serán omitidos.' :
                'Se adoptará la versión de Seguridad en TODOS los días con registro. Los días ya conciliados y bloqueados serán omitidos.', {
                    confirmText: 'Sí, conciliar todo'
                }
            );

            if (!confirmado) return;

            try {
                const {
                    data
                } = await axios.post("{{ route('conciliacion.masiva') }}", {
                    week: filtrosConciliacion.week,
                    adoptar: adoptar,
                    empresa_id: filtrosConciliacion.empresa_id || null,
                    obra_id: filtrosConciliacion.obra_id || null,
                });

                if (data.success) {
                    CaboSyncAlert.success(`Se conciliaron ${data.conciliados} días. Omitidos: ${data.omitidos}.`);
                    await cargarMatriz();
                }
            } catch (error) {
                console.error(error);
                CaboSyncAlert.error(error?.response?.data?.error || 'Error al conciliar');
            }
        }

        // ============================================
        // DESBLOQUEAR (solo Admin)
        // ============================================
        async function desbloquearConciliacion(empleadoId, fecha) {
            const confirmado = await CaboSyncAlert.confirmDelete(
                '¿Desbloquear conciliación?',
                'Esta acción permitirá modificar la conciliación de este día. Solo visible para Admin.',
            );

            if (!confirmado) return;

            try {
                const {
                    data
                } = await axios.post("{{ route('conciliacion.desbloquear') }}", {
                    empleado_id: empleadoId,
                    fecha: fecha,
                });

                if (data.success) {
                    CaboSyncAlert.success('Registro desbloqueado');
                    bootstrap.Modal.getInstance(document.getElementById('modalDetalleCelda')).hide();
                    await cargarMatriz();
                }
            } catch (error) {
                console.error(error);
                CaboSyncAlert.error(error?.response?.data?.error || 'Error al desbloquear');
            }
        }

        // ============================================
        // LISTA DE JEFES (permisos)
        // ============================================
        async function cargarListaJefes() {
            const contenedor = document.getElementById('listaJefesPermisos');
            contenedor.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border text-cabosync-primary" role="status"></div>
        </div>
    `;

            try {
                const {
                    data
                } = await axios.get("{{ route('conciliacion.jefes') }}", {
                    skipPreloader: true
                });

                if (!data.success || !data.jefes.length) {
                    contenedor.innerHTML =
                        '<p class="text-muted text-center py-3">No hay Jefes de Obra registrados.</p>';
                    return;
                }

                let html = '<div class="list-group">';
                data.jefes.forEach(jefe => {
                    html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${jefe.nombre}</strong><br>
                        <small class="text-muted">${jefe.email}</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox"
                               ${jefe.puede_conciliar ? 'checked' : ''}
                               onchange="togglePermisoJefe(${jefe.id}, this.checked)">
                    </div>
                </div>
            `;
                });
                html += '</div>';
                contenedor.innerHTML = html;

            } catch (error) {
                console.error(error);
                contenedor.innerHTML = '<p class="text-danger text-center py-3">Error al cargar jefes.</p>';
            }
        }

        async function togglePermisoJefe(userId, activo) {
            try {
                const {
                    data
                } = await axios.post("{{ route('conciliacion.togglePermisoJefe') }}", {
                    user_id: userId,
                    activo: activo ? 1 : 0,
                });

                if (data.success) {
                    CaboSyncAlert.success('Permiso actualizado');
                }
            } catch (error) {
                console.error(error);
                CaboSyncAlert.error(error?.response?.data?.error || 'Error al actualizar permiso');
            }
        }

        // ============================================
        // PAGINACIÓN
        // ============================================
        function renderizarPaginacion(pag) {
            const texto = `Mostrando ${pag.from ?? 0}-${pag.to ?? 0} de ${pag.total} empleados`;
            document.getElementById('paginacionTopTexto').textContent = texto;
            document.getElementById('paginacionBottomTexto').textContent = texto;

            const buildLinks = () => {
                let html = '';
                html += `<li class="page-item ${pag.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${pag.current_page - 1}">&laquo;</a>
                 </li>`;

                const inicio = Math.max(1, pag.current_page - 3);
                const fin = Math.min(pag.last_page, inicio + 6);

                for (let i = inicio; i <= fin; i++) {
                    html += `<li class="page-item ${i === pag.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                     </li>`;
                }

                html += `<li class="page-item ${pag.current_page === pag.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${pag.current_page + 1}">&raquo;</a>
                 </li>`;
                return html;
            };

            const html = buildLinks();
            document.getElementById('paginacionTopLinks').innerHTML = html;
            document.getElementById('paginacionBottomLinks').innerHTML = html;

            document.querySelectorAll('#paginacionTopLinks .page-link, #paginacionBottomLinks .page-link').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = parseInt(this.dataset.page);
                    if (!page || page < 1 || page > pag.last_page) return;
                    filtrosConciliacion.page = page;
                    cargarMatriz();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            });
        }

        // ============================================
        // FORMATEAR FECHA
        // ============================================
        function formatearFechaTitulo(fecha) {
            const d = new Date(fecha + 'T00:00:00');
            const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre',
                'Octubre', 'Noviembre', 'Diciembre'
            ];
            return `${dias[d.getDay()]} ${d.getDate()} de ${meses[d.getMonth()]}`;
        }
    </script>
@endpush