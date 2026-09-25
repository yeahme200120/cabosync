@extends('layouts.app')

@section('title', 'Bitácora')

@push('styles')
    @vite(['resources/css/bitacora.css'])
@endpush

@section('content')
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-clock-history"></i> Bitácora de Acciones
                </h2>
                <small class="text-muted">
                    <span id="contadorRegistros">{{ $registros->total() }}</span> acciones registradas
                    @if (request()->hasAny(['busqueda', 'usuario_id', 'tipo_accion', 'empresa_id', 'es_publico', 'fecha_desde', 'fecha_hasta']))
                        <span class="badge bg-info text-dark ms-2">
                            <i class="bi bi-funnel"></i> Filtros activos
                        </span>
                    @endif
                </small>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--primary">
                    <div class="kpi-card__icon"><i class="bi bi-list-check"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value">{{ number_format($kpis['total']) }}</div>
                        <div class="kpi-card__label">Total registros</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--info">
                    <div class="kpi-card__icon"><i class="bi bi-people-fill"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value">{{ number_format($kpis['usuarios_distintos']) }}</div>
                        <div class="kpi-card__label">Usuarios únicos</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--warning">
                    <div class="kpi-card__icon"><i class="bi bi-hdd-network-fill"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value">{{ number_format($kpis['ips_distintas']) }}</div>
                        <div class="kpi-card__label">IPs distintas</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--secondary">
                    <div class="kpi-card__icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value">{{ number_format($kpis['con_geo']) }}</div>
                        <div class="kpi-card__label">Con ubicación</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--danger">
                    <div class="kpi-card__icon"><i class="bi bi-globe"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value">{{ number_format($kpis['publicas']) }}</div>
                        <div class="kpi-card__label">Públicas</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--success">
                    <div class="kpi-card__icon"><i class="bi bi-calendar-day"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value">{{ number_format($kpis['hoy']) }}</div>
                        <div class="kpi-card__label">Hoy</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTROS --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('bitacora.index') }}" class="row g-2 align-items-end">

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label small fw-bold mb-1">Buscar</label>
                        <input type="text" name="busqueda" value="{{ request('busqueda') }}"
                            class="form-control form-control-sm" placeholder="Acción o descripción...">
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label small fw-bold mb-1">Usuario</label>
                        <select name="usuario_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach ($usuariosFiltro as $u)
                                <option value="{{ $u->id }}" {{ request('usuario_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label small fw-bold mb-1">Tipo</label>
                        <select name="tipo_accion" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="insert" {{ request('tipo_accion') == 'insert' ? 'selected' : '' }}>Insert</option>
                            <option value="update" {{ request('tipo_accion') == 'update' ? 'selected' : '' }}>Update</option>
                            <option value="delete" {{ request('tipo_accion') == 'delete' ? 'selected' : '' }}>Delete</option>
                            <option value="login" {{ request('tipo_accion') == 'login' ? 'selected' : '' }}>Login</option>
                            <option value="logout" {{ request('tipo_accion') == 'logout' ? 'selected' : '' }}>Logout</option>
                            <option value="view" {{ request('tipo_accion') == 'view' ? 'selected' : '' }}>View</option>
                            <option value="otro" {{ request('tipo_accion') == 'otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>

                    @if (auth()->user()->esAdministrador() && $empresasFiltro->count() > 0)
                        <div class="col-6 col-md-4 col-xl-2">
                            <label class="form-label small fw-bold mb-1">Empresa</label>
                            <select name="empresa_id" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach ($empresasFiltro as $e)
                                    <option value="{{ $e->id }}" {{ request('empresa_id') == $e->id ? 'selected' : '' }}>
                                        {{ $e->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label small fw-bold mb-1">Público</label>
                        <select name="es_publico" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="1" {{ request('es_publico') === '1' ? 'selected' : '' }}>Solo públicos</option>
                            <option value="0" {{ request('es_publico') === '0' ? 'selected' : '' }}>Solo autenticados</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label small fw-bold mb-1">Desde</label>
                        <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                            class="form-control form-control-sm">
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label small fw-bold mb-1">Hasta</label>
                        <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                            class="form-control form-control-sm">
                    </div>

                    <div class="col-12 col-md-4 col-xl-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-cabosync-primary flex-fill">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="{{ route('bitacora.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLA --}}
        <div class="card border-0 shadow-sm">
            <div class="table-responsive bitacora-tabla-wrap">
                <table class="table table-hover align-middle mb-0 bitacora-tabla">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width: 130px;">Fecha</th>
                            <th style="width: 200px;">Usuario</th>
                            <th style="width: 170px;">Acción</th>
                            <th>Descripción</th>
                            <th class="d-none d-lg-table-cell" style="width: 110px;">Modelo</th>
                            <th style="width: 130px;">IP / Geo</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($registros->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.4;"></i>
                                    <p class="mb-0 mt-2">No hay acciones registradas con esos filtros.</p>
                                </td>
                            </tr>
                        @else
                            @foreach ($registros as $r)
                                @include('bitacora._fila', ['registro' => $r])
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINACIÓN --}}
        @if ($registros->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $registros->firstItem() }} a {{ $registros->lastItem() }}
                    de {{ $registros->total() }} registros
                </small>
                {{ $registros->links() }}
            </div>
        @endif

        {{-- MODAL DETALLE --}}
        <div class="modal fade" id="modalDetalleBitacora" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-cabosync-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-info-circle"></i> Detalle de la acción
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div id="detalleLoading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted mt-2 mb-0">Cargando detalle...</p>
                        </div>

                        <div id="detalleContenido" class="d-none">

                            <div class="mb-3">
                                <h6 class="text-cabosync-primary mb-2">
                                    <span id="detalleAccion" class="badge bg-secondary">-</span>
                                    <span id="detalleTipoAccion" class="badge bg-info text-dark ms-1">-</span>
                                    <span id="detalleEsPublico" class="badge bg-warning text-dark ms-1 d-none">Público</span>
                                </h6>
                                <p id="detalleDescripcion" class="mb-2">-</p>
                                <small class="text-muted d-block">
                                    <i class="bi bi-clock"></i> <span id="detalleFecha">-</span>
                                    <span id="detalleFechaHumana" class="ms-2">-</span>
                                </small>
                            </div>

                            <hr>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase">Usuario</label>
                                    <p id="detalleUsuario" class="mb-0 small">-</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase">Empresa</label>
                                    <p id="detalleEmpresa" class="mb-0 small">-</p>
                                </div>
                            </div>

                            <hr>

                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="small fw-bold text-muted text-uppercase">Modelo afectado</label>
                                    <p id="detalleModelo" class="mb-0 small">-</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold text-muted text-uppercase">ID del modelo</label>
                                    <p id="detalleModeloId" class="mb-0 small">-</p>
                                </div>
                            </div>

                            <div id="detalleDiffSection" class="mb-3 d-none">
                                <hr>
                                <label class="small fw-bold text-muted text-uppercase mb-2">
                                    <i class="bi bi-arrow-left-right"></i> Cambios
                                </label>
                                <div id="detalleDiff"></div>
                            </div>

                            <hr>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="small fw-bold text-muted text-uppercase">Dirección IP</label>
                                    <p id="detalleIp" class="mb-0 small">-</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold text-muted text-uppercase">Plataforma</label>
                                    <p id="detallePlataforma" class="mb-0 small">-</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold text-muted text-uppercase">Navegador</label>
                                    <p id="detalleNavegador" class="mb-0 small">-</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase">Device ID</label>
                                    <p id="detalleDeviceId" class="mb-0 small text-break">-</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase">Geolocalización</label>
                                    <p id="detalleGeo" class="mb-0 small">-</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        window.BITACORA_CONFIG = {
            rutas: {
                index: "{{ route('bitacora.index') }}",
                base:  "{{ url('bitacora') }}",
            },
        };
    </script>

    @vite(['resources/js/bitacora.js'])
@endpush