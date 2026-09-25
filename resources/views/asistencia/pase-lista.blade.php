@extends('layouts.app')

@section('title', 'Pase de Lista')

@push('styles')
    @vite(['resources/css/pase_lista.css'])
@endpush

@section('content')
    <div class="container-fluid py-4">

        {{-- ============================================
         HEADER
         ============================================ --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-clipboard-check"></i> Pase de Lista
                </h2>
                <small class="text-muted">
                    Semana del <span id="semanaInicio">-</span> al <span id="semanaFin">-</span>
                    &nbsp;|&nbsp; Hoy: <strong id="hoyTexto">-</strong>
                </small>
            </div>

            <div class="d-flex gap-2 align-items-center flex-wrap">
                @if (auth()->user()->esAdministrador() || auth()->user()->esContratista())
                    {{-- Contratista y Admin: pueden alternar --}}
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="origenRegistro" id="origenJefe" value="jefe_obra"
                            checked>
                        <label class="btn btn-outline-cabosync-primary" for="origenJefe">
                            <i class="bi bi-person-badge"></i> Jefe de Obra
                        </label>

                        <input type="radio" class="btn-check" name="origenRegistro" id="origenSeguridad"
                            value="seguridad">
                        <label class="btn btn-outline-cabosync-primary" for="origenSeguridad">
                            <i class="bi bi-shield-fill"></i> Seguridad
                        </label>
                    </div>
                @elseif (auth()->user()->esJefeObra())
                    <span class="badge bg-cabosync-primary py-2 px-3">
                        <i class="bi bi-person-badge"></i> Vista: Jefe de Obra
                    </span>
                @elseif (auth()->user()->esSeguridad())
                    <span class="badge bg-cabosync-primary py-2 px-3">
                        <i class="bi bi-shield-fill"></i> Vista: Seguridad
                    </span>
                @endif
            </div>
        </div>

        {{-- ============================================
         FILTROS
         ============================================ --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    @if (auth()->user()->esAdministrador() || auth()->user()->esContratista() && $empresas->count() > 0)
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Empresa</label>
                            <select id="filtroEmpresa" class="form-select form-select-sm">
                                <option value="">Todas</option>
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
                        <label class="form-label small fw-bold mb-1">Puesto</label>
                        <select id="filtroRol" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach ($rolesOperativos as $rol)
                                <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Semana</label>
                        <input type="week" id="filtroWeek" class="form-control form-control-sm"
                            value="{{ $weekInput }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================
         LEYENDA
         ============================================ --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex gap-3 flex-wrap small">
                        <span><span class="badge bg-success">&nbsp;</span> Presente</span>
                        <span><span class="badge bg-danger">&nbsp;</span> Falta</span>
                        <span><span class="badge bg-warning text-dark">&nbsp;</span> Justificada</span>
                        <span><span class="badge bg-secondary">&nbsp;</span> Sin marcar</span>
                        <span><span class="badge bg-dark">&nbsp;</span> Bloqueado horas extra</span>
                    </div>
                    <div class="small text-muted">
                        <strong>Total:</strong> <span id="resumenTotal">0</span> empleados
                        | <span class="text-success" id="resumenPresentes">0</span> presentes
                        | <span class="text-danger" id="resumenFaltas">0</span> faltas
                        | <span class="text-warning" id="resumenJustificadas">0</span> justificadas
                        | <span class="text-dark" id="resumenPenalizaciones">0</span> días penalizados
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================
         PAGINACIÓN SUPERIOR
         ============================================ --}}
        <div id="paginacionTop" class="paginacion-pase d-none">
            <div class="small text-muted" id="paginacionTopTexto"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginacionTopLinks"></ul>
            </nav>
        </div>

        {{-- ============================================
         CONTENEDOR PRINCIPAL
         ============================================ --}}
        <div id="paseListaContenedor">
            <div id="paseListaLoading" class="text-center py-5">
                <div class="spinner-border text-cabosync-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-muted mt-2 mb-0">Cargando pase de lista...</p>
            </div>

            <div id="paseListaEmpty" class="empty-state d-none">
                <i class="bi bi-people"></i>
                <h4>No hay empleados registrados</h4>
                <p class="text-muted">Ajusta los filtros o registra empleados primero.</p>
            </div>

            <div id="paseListaContenido" class="d-none">
                <div class="scroll-hint-text">
                    <i class="bi bi-arrows-move"></i> Desliza horizontalmente para ver todos los días
                </div>
                <div class="tabla-pase-wrapper">
                    <table class="tabla-pase" id="tablaPaseLista">
                        <thead>
                            <tr class="text-center">
                                <th class="col-empleado text-center">Empleado</th>
                                <th class="col-puesto">Puesto</th>
                                {{-- Días L M M J V S se llenan dinámicamente --}}
                                <th class="col-horas-extra">Of</th>
                                <th class="col-acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPaseLista" class="text-center"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ============================================
         PAGINACIÓN INFERIOR
         ============================================ --}}
        <div id="paginacionBottom" class="paginacion-pase d-none mt-3">
            <div class="small text-muted" id="paginacionBottomTexto"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginacionBottomLinks"></ul>
            </nav>
        </div>

        {{-- ============================================
         BARRA FIJA DE RESUMEN
         ============================================ --}}
        <div id="paseListaFooter" class="d-none position-fixed bottom-0 start-0 end-0 bg-white border-top shadow-lg p-3"
            style="z-index: 1020;">
            <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small">
                    <i class="bi bi-info-circle text-cabosync-primary"></i>
                    Clic en celda para alternar Presente ↔ Falta. Las horas extra se guardan automáticamente.
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-success" id="footerPresentes">0 Presentes</span>
                    <span class="badge bg-danger" id="footerFaltas">0 Faltas</span>
                    <span class="badge bg-warning text-dark" id="footerJustificadas">0 Justificadas</span>
                    <span class="badge bg-dark" id="footerPenalizaciones">0 días penalizados</span>
                </div>
            </div>
        </div>

    </div>

    {{-- ============================================
     MODAL JUSTIFICAR FALTA
     ============================================ --}}
    <div class="modal fade" id="modalJustificar" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formJustificar" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="justificarEmpleadoId" name="empleado_id">
                    <input type="hidden" id="justificarFecha" name="fecha">
                    <input type="hidden" id="justificarOrigen" name="origen_registro">

                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle-fill"></i> Justificar Falta
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-info small mb-3">
                            <i class="bi bi-person-fill"></i>
                            <strong id="justificarNombreEmpleado">-</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">
                                Selecciona la fecha a justificar <span class="text-danger">*</span>
                            </label>
                            <div id="justificarFechasLista" class="d-flex flex-column gap-2"></div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Motivo <span
                                    class="text-danger">*</span></label>
                            <select name="motivo" id="justificarMotivo" class="form-select" required disabled>
                                <option value="">Primero selecciona una fecha</option>
                                <option value="permiso">Permiso</option>
                                <option value="enfermedad">Enfermedad</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Descripción (opcional)</label>
                            <textarea name="descripcion" id="justificarDescripcion" class="form-control" rows="3"
                                placeholder="Detalles adicionales..." disabled></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Evidencia (opcional)</label>
                            <input type="file" name="evidencia" id="justificarEvidencia" class="form-control"
                                accept="image/jpeg,image/png,application/pdf" disabled>
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> JPG, PNG o PDF. Máx 5MB.
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning" id="btnGuardarJustificacion" disabled>
                            <i class="bi bi-check-lg"></i> Guardar Justificación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        let paseListaData = null;

        // ============================================
        // ROLES DEL USUARIO
        // ============================================
        const USER_ES_ADMIN = {{ auth()->user()->esAdministrador() ? 'true' : 'false' }};
        const USER_ES_CONTRATISTA = {{ auth()->user()->esContratista() ? 'true' : 'false' }};
        const USER_ES_JEFE_OBRA = {{ auth()->user()->esJefeObra() ? 'true' : 'false' }};
        const USER_ES_SEGURIDAD = {{ auth()->user()->esSeguridad() ? 'true' : 'false' }};

        // ¿Puede justificar? Solo Admin, Contratista y Jefe de Obra
        const PUEDE_JUSTIFICAR = USER_ES_ADMIN || USER_ES_CONTRATISTA || USER_ES_JEFE_OBRA;

        // Origen por defecto según rol
        const ORIGEN_INICIAL = USER_ES_SEGURIDAD ? 'seguridad' : 'jefe_obra';

        let filtrosActuales = {
            empresa_id: '',
            obra_id: '',
            rol_id: '',
            week: '',
            origen_registro: ORIGEN_INICIAL,
            page: 1,
        };

        // ============================================
        // INICIALIZACIÓN
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            cargarPaseLista();

            document.getElementById('filtroEmpresa')?.addEventListener('change', () => {
                filtrosActuales.page = 1;
                cargarPaseLista();
            });
            document.getElementById('filtroObra')?.addEventListener('change', () => {
                filtrosActuales.page = 1;
                cargarPaseLista();
            });
            document.getElementById('filtroRol')?.addEventListener('change', () => {
                filtrosActuales.page = 1;
                cargarPaseLista();
            });
            document.getElementById('filtroWeek')?.addEventListener('change', () => {
                filtrosActuales.page = 1;
                cargarPaseLista();
            });

            document.querySelectorAll('input[name="origenRegistro"]').forEach(input => {
                input.addEventListener('change', function() {
                    filtrosActuales.origen_registro = this.value;
                    filtrosActuales.page = 1;
                    cargarPaseLista();
                });
            });

            document.getElementById('formJustificar')?.addEventListener('submit', guardarJustificacion);
        });

        // ============================================
        // CARGAR PASE DE LISTA
        // ============================================
        async function cargarPaseLista() {
            document.getElementById('paseListaLoading').classList.remove('d-none');
            document.getElementById('paseListaContenido').classList.add('d-none');
            document.getElementById('paseListaEmpty').classList.add('d-none');
            document.getElementById('paseListaFooter').classList.add('d-none');
            document.getElementById('paginacionTop').classList.add('d-none');
            document.getElementById('paginacionBottom').classList.add('d-none');

            filtrosActuales.empresa_id = document.getElementById('filtroEmpresa')?.value || '';
            filtrosActuales.obra_id = document.getElementById('filtroObra')?.value || '';
            filtrosActuales.rol_id = document.getElementById('filtroRol')?.value || '';
            filtrosActuales.week = document.getElementById('filtroWeek')?.value || '';

            try {
                const params = new URLSearchParams(filtrosActuales);
                const {
                    data
                } = await axios.get(`{{ route('asistencia.datos') }}?${params}`);

                if (!data.success) {
                    CaboSyncAlert.error(data.error || 'Error al cargar datos');
                    return;
                }

                paseListaData = data;

                document.getElementById('semanaInicio').textContent = data.semana.inicio;
                document.getElementById('semanaFin').textContent = data.semana.fin;
                document.getElementById('hoyTexto').textContent = formatearFechaTitulo(data.hoy);

                renderizarTabla(data);
                setTimeout(actualizarHintScroll, 150);

                document.getElementById('paseListaLoading').classList.add('d-none');

                if (data.total_empleados === 0) {
                    document.getElementById('paseListaEmpty').classList.remove('d-none');
                } else {
                    document.getElementById('paseListaContenido').classList.remove('d-none');
                    document.getElementById('paseListaFooter').classList.remove('d-none');
                    renderizarPaginacion(data.paginacion);
                    document.getElementById('paginacionTop').classList.remove('d-none');
                    document.getElementById('paginacionBottom').classList.remove('d-none');
                }

                actualizarResumen();

            } catch (error) {
                console.error(error);
                document.getElementById('paseListaLoading').classList.add('d-none');
            }
        }

        // ============================================
        // RENDERIZAR TABLA
        // ============================================
        function renderizarTabla(data) {
            const theadTr = document.querySelector('#tablaPaseLista thead tr');
            const tbody = document.getElementById('tbodyPaseLista');

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

            theadTr.innerHTML += '<th class="col-horas-extra">Of</th>';
            theadTr.innerHTML += '<th class="col-acciones">Acciones</th>';

            tbody.innerHTML = '';

            data.empleados.forEach(emp => {
                tbody.appendChild(construirFilaEmpleado(emp, data));
            });

            actualizarBotonesJustificar();
        }

        // ============================================
        // HELPER: Celda de día (L M M J V S)
        // ============================================
        function renderizarCeldaDia(emp, fecha, hoy) {
            const asistencia = emp.asistencias[fecha];
            const esHoy = fecha === hoy;

            let clase = 'estado-vacio';
            let icono = '○';
            let indicador = '';

            if (asistencia) {
                if (asistencia.estado === 'presente') {
                    clase = 'estado-presente';
                    icono = '✓';
                } else if (asistencia.estado === 'falta') {
                    if (asistencia.es_justificada) {
                        clase = 'estado-justificada';
                        icono = '⚠';
                        if (asistencia.evidencia_url) {
                            indicador = '<span class="indicador-evidencia"><i class="bi bi-paperclip"></i></span>';
                        }
                    } else {
                        clase = 'estado-falta';
                        icono = '✗';
                    }
                }
            }

            return `
        <td class="celda-dia ${clase} ${esHoy ? 'celda-hoy' : ''}"
            data-empleado-id="${emp.id}"
            data-fecha="${fecha}"
            data-estado="${asistencia ? asistencia.estado : ''}"
            data-justificada="${asistencia ? asistencia.es_justificada : false}"
            title="${formatearFechaTitulo(fecha)}">
            ${icono}${indicador}
        </td>
    `;
        }

        // ============================================
        // HELPER: Celda "Of" (horas extra)
        // ============================================
        function renderizarCeldaHorasExtra(emp, data) {
            const valorActual = emp.horas_extra_hoy || 0;
            const bloqueado = emp.bloqueado_horas_extras;
            const esDomingo = (new Date(data.hoy + 'T00:00:00')).getDay() === 0;

            let disabled = false;
            let tooltip = '';
            let valorMostrar = valorActual;

            if (esDomingo) {
                disabled = true;
                tooltip = 'Hoy es domingo, no se capturan horas extra';
                valorMostrar = '—';
            } else if (bloqueado) {
                disabled = true;
                tooltip = `🔒 Bloqueado por falta injustificada (${emp.dias_penalizacion} días)`;
                valorMostrar = '—';
            } else if (!data.hoy_en_semana) {
                disabled = true;
                tooltip = 'Solo puedes capturar horas extra del día de HOY';
                valorMostrar = '—';
            } else if (!data.puede_editar_hoy) {
                disabled = true;
                tooltip = 'No tienes permisos para capturar hoy';
                valorMostrar = '—';
            }

            const inputHtml = disabled ?
                `<span class="horas-extra-bloqueado" title="${tooltip}">${valorMostrar}</span>` :
                `<input type="number"
                  class="form-control form-control-sm input-horas-extra"
                  data-empleado-id="${emp.id}"
                  value="${valorActual || ''}"
                  min="0" max="12" step="0.5"
                  placeholder="0"
                  title="Horas extra de hoy (${data.hoy})">`;

            return `<td class="celda-horas-extra ${bloqueado ? 'bloqueado' : ''}">${inputHtml}</td>`;
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

                // Anterior
                html += `
            <li class="page-item ${pag.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pag.current_page - 1}">&laquo;</a>
            </li>
        `;

                // Páginas (máx 7 visibles alrededor de la actual)
                const inicio = Math.max(1, pag.current_page - 3);
                const fin = Math.min(pag.last_page, inicio + 6);

                for (let i = inicio; i <= fin; i++) {
                    html += `
                <li class="page-item ${i === pag.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
                }

                // Siguiente
                html += `
            <li class="page-item ${pag.current_page === pag.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pag.current_page + 1}">&raquo;</a>
            </li>
        `;

                return html;
            };

            const html = buildLinks();
            document.getElementById('paginacionTopLinks').innerHTML = html;
            document.getElementById('paginacionBottomLinks').innerHTML = html;

            // Listener global (delegación)
            document.querySelectorAll('#paginacionTopLinks .page-link, #paginacionBottomLinks .page-link').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = parseInt(this.dataset.page);
                    if (!page || page < 1 || page > pag.last_page) return;
                    filtrosActuales.page = page;
                    cargarPaseLista();
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

        // ============================================
        // CLICK EN CELDA DE DÍA (1-Click UX)
        // ============================================
        document.addEventListener('click', async function(e) {
            const celda = e.target.closest('.celda-dia');
            if (!celda) return;

            const empleadoId = celda.dataset.empleadoId;
            const fecha = celda.dataset.fecha;
            const estadoActual = celda.dataset.estado;
            const esJustificada = celda.dataset.justificada === 'true';
            const estaBloqueada = celda.dataset.bloqueada === '1';

            // =========================================================
            // VALIDACIÓN 1: día bloqueado por conciliación
            // =========================================================
            if (estaBloqueada && !USER_ES_ADMIN) {
                CaboSyncAlert.warning('🔒 Este día ya fue conciliado. Solo el Admin puede modificarlo.');
                return;
            }

            // =========================================================
            // VALIDACIÓN 2: día no editable según rol
            // =========================================================
            const hoy = new Date().toISOString().split('T')[0];

            if (!USER_ES_ADMIN && !USER_ES_CONTRATISTA) {
                // Jefe de Obra, Seguridad → solo HOY
                if (fecha !== hoy) {
                    CaboSyncAlert.warning('Solo puedes editar el día de HOY.');
                    return;
                }
            }

            // =========================================================
            // VALIDACIÓN 3: día en el futuro
            // =========================================================
            if (fecha > hoy && !USER_ES_ADMIN) {
                CaboSyncAlert.warning('No puedes editar días futuros.');
                return;
            }

            // =========================================================
            // CASO 1: Quitar justificación
            // =========================================================
            if (estadoActual === 'falta' && esJustificada) {
                const confirmado = await CaboSyncAlert.confirmDelete(
                    '¿Eliminar justificación?',
                    'Se eliminará la justificación de esta falta. El empleado quedará marcado como Falta Injustificada y se aplicará la penalización (1+1 = 2 días).',
                );

                if (!confirmado) return;

                celda.classList.add('cargando');
                try {
                    await axios.post("{{ route('asistencia.quitarJustificacion') }}", {
                        empleado_id: empleadoId,
                        fecha: fecha,
                        origen_registro: filtrosActuales.origen_registro,
                    });
                    await recargarFilaEmpleado(empleadoId);
                    CaboSyncAlert.success('Justificación eliminada');
                } catch (error) {
                    console.error(error);
                    CaboSyncAlert.error(error?.response?.data?.error || 'Error al eliminar justificación');
                } finally {
                    celda.classList.remove('cargando');
                }
                return;
            }

            // =========================================================
            // CASO 2: Alternar Presente ↔ Falta
            // =========================================================
            let nuevoEstado;
            if (!estadoActual || estadoActual === '') {
                nuevoEstado = 'presente';
            } else if (estadoActual === 'presente') {
                nuevoEstado = 'falta';
            } else if (estadoActual === 'falta') {
                nuevoEstado = 'presente';
            } else {
                nuevoEstado = 'presente';
            }

            celda.classList.add('cargando');

            try {
                const payload = {
                    empleado_id: empleadoId,
                    fecha: fecha,
                    estado: nuevoEstado,
                    origen_registro: filtrosActuales.origen_registro,
                };

                console.log('[DEBUG] Payload a enviar:', payload);

                const {
                    data
                } = await axios.post(
                    "{{ route('asistencia.guardar') }}",
                    payload, {
                        skipPreloader: true
                    }
                );

                if (data.success) {
                    actualizarCelda(celda, {
                        estado: nuevoEstado,
                        es_justificada: false,
                        evidencia_url: null,
                    });

                    await recargarFilaEmpleado(empleadoId);

                    const msg = data.justificacion_eliminada ?
                        `Marcado como ${nuevoEstado} (justificación eliminada)` :
                        `Marcado como ${nuevoEstado}`;
                    CaboSyncAlert.success(msg);
                }

                actualizarResumen();

            } catch (error) {
                console.error(error);
                const msg = error?.response?.data?.error ||
                    error?.response?.data?.message ||
                    'Error al guardar';
                CaboSyncAlert.error(msg);
            } finally {
                celda.classList.remove('cargando');
            }
        });
        // =========================================================
        // RECARGAR UNA FILA COMPLETA DESDE EL BACKEND (endpoint ligero)
        // =========================================================
        async function recargarFilaEmpleado(empleadoId) {
            try {
                const params = new URLSearchParams({
                    week: filtrosActuales.week,
                    origen_registro: filtrosActuales.origen_registro,
                });

                const {
                    data
                } = await axios.get(
                    `/asistencia/empleado/${empleadoId}/estado?${params}`, {
                        skipPreloader: true
                    }
                );

                if (!data.success) return;

                const empActualizado = data.empleado;

                // Actualizar el objeto en memoria para que el resumen funcione
                if (paseListaData?.empleados) {
                    const idx = paseListaData.empleados.findIndex(e => e.id == empleadoId);
                    if (idx !== -1) {
                        paseListaData.empleados[idx] = {
                            ...paseListaData.empleados[idx],
                            ...empActualizado,
                        };
                    }
                }

                // Reemplazar SOLO la fila afectada en el DOM
                const filaVieja = document.querySelector(`.fila-empleado[data-empleado-id="${empleadoId}"]`);
                if (!filaVieja) return;

                const nuevaFila = construirFilaEmpleado(empActualizado, data);
                filaVieja.replaceWith(nuevaFila);

                // Recalcular botones y resumen
                actualizarBotonesJustificar();
                actualizarResumen();
                setTimeout(actualizarHintScroll, 50);

            } catch (error) {
                console.error('Error al recargar fila:', error);
            }
        }

        // =========================================================
        // CONSTRUIR UNA FILA DE EMPLEADO
        // =========================================================
        function construirFilaEmpleado(emp, data) {
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
                html += renderizarCeldaDia(emp, fecha, data.hoy);
            });

            html += renderizarCeldaHorasExtra(emp, data);

            html += `
                <td class="celda-acciones">
                    ${PUEDE_JUSTIFICAR ? `
                            <button type="button" class="btn btn-sm btn-outline-warning btn-justificar d-none"
                                    data-empleado-id="${emp.id}"
                                    data-nombre="${emp.nombre_completo}"
                                    onclick="abrirModalJustificar(${emp.id}, '${emp.nombre_completo.replace(/'/g, "\\'")}')">
                                <i class="bi bi-exclamation-triangle"></i> Justificar
                            </button>
                        ` : ''}
                    ${emp.bloqueado_horas_extras
                        ? `<span class="badge bg-dark ms-1" title="Bloqueado por falta injustificada (${emp.dias_penalizacion} días)">🔒 ${emp.dias_penalizacion}d</span>`
                        : ''}
                </td>
            `;

            tr.innerHTML = html;
            return tr;
        }

        // ============================================
        // GUARDAR HORAS EXTRA (blur / enter)
        // ============================================
        document.addEventListener('blur', async function(e) {
            if (!e.target.classList.contains('input-horas-extra')) return;
            await guardarHorasExtra(e.target);
        }, true);

        document.addEventListener('keydown', async function(e) {
            if (e.key === 'Enter' && e.target.classList.contains('input-horas-extra')) {
                e.preventDefault();
                e.target.blur();
            }
        });

        async function guardarHorasExtra(input) {
            const empleadoId = input.dataset.empleadoId;
            const valor = parseFloat(input.value) || 0;

            input.classList.add('cargando');

            try {
                const {
                    data
                } = await axios.post(
                    "{{ route('asistencia.horasExtra') }}", {
                        empleado_id: empleadoId,
                        horas: valor,
                        origen_registro: filtrosActuales.origen_registro,
                    }, {
                        skipPreloader: true
                    }
                );

                if (data.success) {
                    input.value = data.horas > 0 ? data.horas : '';
                    CaboSyncAlert.success(`Horas extra: ${data.horas}h`);
                }
            } catch (error) {
                console.error(error);
                const msg = error?.response?.data?.error || 'Error al guardar horas extra';
                CaboSyncAlert.error(msg);
                input.value = ''; // revertir
            } finally {
                input.classList.remove('cargando');
            }
        }

        // ============================================
        // ACTUALIZAR CELDA
        // ============================================
        function actualizarCelda(celda, asistencia) {
            celda.classList.remove('estado-vacio', 'estado-presente', 'estado-falta', 'estado-justificada');

            if (!asistencia || !asistencia.estado) {
                celda.classList.add('estado-vacio');
                celda.innerHTML = '○';
                celda.dataset.estado = '';
                celda.dataset.justificada = 'false';
            } else if (asistencia.estado === 'presente') {
                celda.classList.add('estado-presente');
                celda.innerHTML = '✓';
                celda.dataset.estado = 'presente';
                celda.dataset.justificada = 'false';
            } else if (asistencia.estado === 'falta') {
                if (asistencia.es_justificada) {
                    celda.classList.add('estado-justificada');
                    celda.innerHTML = '⚠' + (asistencia.evidencia_url ?
                        '<span class="indicador-evidencia"><i class="bi bi-paperclip"></i></span>' :
                        '');
                    celda.dataset.estado = 'falta';
                    celda.dataset.justificada = 'true';
                } else {
                    celda.classList.add('estado-falta');
                    celda.innerHTML = '✗';
                    celda.dataset.estado = 'falta';
                    celda.dataset.justificada = 'false';
                }
            }
        }

        // ============================================
        // ACTUALIZAR RESUMEN
        // ============================================
        function actualizarResumen() {
            const celdas = document.querySelectorAll('.celda-dia');
            let presentes = 0,
                faltas = 0,
                justificadas = 0;

            celdas.forEach(c => {
                if (c.dataset.estado === 'presente') presentes++;
                else if (c.dataset.estado === 'falta') {
                    if (c.dataset.justificada === 'true') justificadas++;
                    else faltas++;
                }
            });

            // Total de empleados desde paginación
            const total = paseListaData?.paginacion?.total ?? 0;

            // Días penalizados: 2 por cada falta injustificada
            let diasPenalizados = 0;
            document.querySelectorAll('.fila-empleado').forEach(fila => {
                const badgeBloq = fila.querySelector('.badge.bg-dark');
                if (badgeBloq) {
                    const match = badgeBloq.textContent.match(/(\d+)d/);
                    if (match) diasPenalizados += parseInt(match[1]);
                }
            });

            document.getElementById('resumenTotal').textContent = total;
            document.getElementById('resumenPresentes').textContent = presentes;
            document.getElementById('resumenFaltas').textContent = faltas;
            document.getElementById('resumenJustificadas').textContent = justificadas;
            document.getElementById('resumenPenalizaciones').textContent = diasPenalizados;

            document.getElementById('footerPresentes').textContent = `${presentes} Presentes`;
            document.getElementById('footerFaltas').textContent = `${faltas} Faltas`;
            document.getElementById('footerJustificadas').textContent = `${justificadas} Justificadas`;
            document.getElementById('footerPenalizaciones').textContent = `${diasPenalizados} días penalizados`;
        }

        // ============================================
        // MOSTRAR/OCULTAR BOTÓN JUSTIFICAR
        // ============================================
        function actualizarBotonesJustificar() {
            document.querySelectorAll('.fila-empleado').forEach(fila => {
                const tieneFalta = fila.querySelector(
                    '.celda-dia[data-estado="falta"]:not([data-justificada="true"])');
                const btnJustificar = fila.querySelector('.btn-justificar');
                if (btnJustificar) {
                    btnJustificar.classList.toggle('d-none', !tieneFalta);
                }
            });
        }

        // ============================================
        // ABRIR MODAL JUSTIFICAR
        // ============================================
        function abrirModalJustificar(empleadoId, nombreEmpleado) {
            const fila = document.querySelector(`.fila-empleado[data-empleado-id="${empleadoId}"]`);
            if (!fila) return;

            const celdasFalta = fila.querySelectorAll('.celda-dia[data-estado="falta"]:not([data-justificada="true"])');

            if (celdasFalta.length === 0) {
                CaboSyncAlert.warning('No hay faltas sin justificar para este empleado');
                return;
            }

            document.getElementById('formJustificar').reset();
            document.getElementById('justificarEmpleadoId').value = empleadoId;
            document.getElementById('justificarNombreEmpleado').textContent = nombreEmpleado;
            document.getElementById('justificarOrigen').value = filtrosActuales.origen_registro;
            document.getElementById('justificarFecha').value = '';

            document.getElementById('justificarMotivo').disabled = true;
            document.getElementById('justificarMotivo').value = '';
            document.getElementById('justificarDescripcion').disabled = true;
            document.getElementById('justificarDescripcion').value = '';
            document.getElementById('justificarEvidencia').disabled = true;
            document.getElementById('justificarEvidencia').value = '';
            document.getElementById('btnGuardarJustificacion').disabled = true;

            const contenedorFechas = document.getElementById('justificarFechasLista');
            contenedorFechas.innerHTML = '';

            celdasFalta.forEach(celda => {
                const fecha = celda.dataset.fecha;
                const nombreFecha = formatearFechaTitulo(fecha);

                const botonFecha = document.createElement('button');
                botonFecha.type = 'button';
                botonFecha.className = 'btn btn-outline-warning text-start d-flex align-items-center gap-2';
                botonFecha.innerHTML = `<i class="bi bi-circle"></i><span>${nombreFecha}</span>`;

                botonFecha.addEventListener('click', () => {
                    contenedorFechas.querySelectorAll('button').forEach(b => {
                        b.classList.remove('active', 'btn-warning');
                        b.classList.add('btn-outline-warning');
                        b.querySelector('i').className = 'bi bi-circle';
                    });
                    botonFecha.classList.remove('btn-outline-warning');
                    botonFecha.classList.add('btn-warning', 'active');
                    botonFecha.querySelector('i').className = 'bi bi-check-circle-fill';

                    document.getElementById('justificarFecha').value = fecha;
                    document.getElementById('justificarMotivo').disabled = false;
                    document.getElementById('justificarMotivo').value = '';
                    document.getElementById('justificarDescripcion').disabled = false;
                    document.getElementById('justificarEvidencia').disabled = false;
                    document.getElementById('btnGuardarJustificacion').disabled = false;
                });

                contenedorFechas.appendChild(botonFecha);
            });

            new bootstrap.Modal(document.getElementById('modalJustificar')).show();
        }

        // ============================================
        // GUARDAR JUSTIFICACIÓN
        // ============================================
        async function guardarJustificacion(e) {
            e.preventDefault();

            const form = document.getElementById('formJustificar');
            const formData = new FormData(form);
            const btnGuardar = document.getElementById('btnGuardarJustificacion');

            const empleadoId = formData.get('empleado_id');
            const fecha = formData.get('fecha');

            if (!fecha) {
                CaboSyncAlert.warning('Selecciona una fecha');
                return;
            }

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';

            try {
                const {
                    data
                } = await axios.post("{{ route('asistencia.justificar') }}", formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalJustificar')).hide();

                    const celda = document.querySelector(
                        `.celda-dia[data-empleado-id="${empleadoId}"][data-fecha="${fecha}"]`);
                    if (celda) {
                        actualizarCelda(celda, {
                            estado: 'falta',
                            es_justificada: true,
                            evidencia_url: data.evidencia_url,
                        });
                    }

                    actualizarResumen();
                    actualizarBotonesJustificar();
                    await recargarFilaEmpleado(empleadoId);
                    CaboSyncAlert.success('Falta justificada correctamente');
                }
            } catch (error) {
                console.error(error);
                CaboSyncAlert.error(error?.response?.data?.error || 'Error al justificar');
            } finally {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="bi bi-check-lg"></i> Guardar Justificación';
            }
        }

        // ============================================================
        // DETECTAR SI HAY SCROLL HORIZONTAL (para hint visual)
        // ============================================================
        function actualizarHintScroll() {
            const wrapper = document.querySelector('.tabla-pase-wrapper');
            const contenedor = document.getElementById('paseListaContenido');
            if (!wrapper || !contenedor) return;

            const tieneScroll = wrapper.scrollWidth > wrapper.clientWidth + 5;
            contenedor.classList.toggle('scroll-hint', tieneScroll);
        }

        window.addEventListener('load', () => setTimeout(actualizarHintScroll, 150));
        window.addEventListener('resize', actualizarHintScroll);
    </script>
@endpush
