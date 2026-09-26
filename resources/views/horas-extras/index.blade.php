@extends('layouts.app')

@section('title', 'Horas Extras')

@push('styles')
    @vite(['resources/css/horas-extras.css'])
@endpush

@section('content')
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-clock-history"></i> Horas Extras
                </h2>
                <small class="text-muted">Aprobación de horas extras por semana</small>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--primary">
                    <div class="kpi-card__icon"><i class="bi bi-list-check"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value" id="kpiTotal">{{ $kpis['total'] }}</div>
                        <div class="kpi-card__label">Registros</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--info">
                    <div class="kpi-card__icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value" id="kpiSolicitadas">{{ $kpis['solicitadas'] }}</div>
                        <div class="kpi-card__label">Horas solicitadas</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--success">
                    <div class="kpi-card__icon"><i class="bi bi-check-circle"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value" id="kpiAprobadas">{{ $kpis['aprobadas'] }}</div>
                        <div class="kpi-card__label">Horas aprobadas</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--warning">
                    <div class="kpi-card__icon"><i class="bi bi-hourglass"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value" id="kpiPendientes">{{ $kpis['pendientes'] }}</div>
                        <div class="kpi-card__label">Pendientes</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card kpi-card--danger">
                    <div class="kpi-card__icon"><i class="bi bi-x-circle"></i></div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value" id="kpiRechazadas">{{ $kpis['rechazadas'] }}</div>
                        <div class="kpi-card__label">Rechazadas</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTROS --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label small fw-bold">Empresa</label>
                        <select id="empresa_id" class="form-select form-select-sm">
                            @if (auth()->user()->esAdministrador())
                                <option value="">Todas las empresas</option>
                            @endif
                            @foreach ($empresas as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label small fw-bold">Obra</label>
                        <select id="obra_id" class="form-select form-select-sm">
                            <option value="">Todas las obras</option>
                            @foreach ($obras as $obra)
                                <option value="{{ $obra->id }}" data-empresa="{{ $obra->empresa_id }}">
                                    {{ $obra->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label small fw-bold">Semana</label>
                        <input type="week" id="week" class="form-control form-control-sm" value="{{ $weekInput }}">
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label small fw-bold">Estado</label>
                        <select id="estado" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendientes</option>
                            <option value="aprobado">Aprobadas</option>
                            <option value="rechazado">Rechazadas</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2 flex-wrap justify-content-between">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarFiltros()">
                            <i class="bi bi-arrow-clockwise"></i> Limpiar
                        </button>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-success" onclick="aprobarSeleccionados()">
                                <i class="bi bi-check-circle"></i> Aprobar seleccionados
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="rechazarSeleccionados()">
                                <i class="bi bi-x-circle"></i> Rechazar seleccionados
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLA POR DÍA --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div id="tablaLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>

                <div id="tablaVacia" class="text-center py-5 d-none">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.4;"></i>
                    <p class="text-muted mt-2 mb-0">No hay horas extras registradas para esa semana.</p>
                </div>

                <div id="tablaContenido" class="table-responsive d-none">
                    <table class="table table-hover align-middle mb-0 horas-extras-tabla">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="checkAll">
                                </th>
                                <th style="min-width: 220px;">Empleado</th>
                                <th class="text-center" style="width: 80px;">L</th>
                                <th class="text-center" style="width: 80px;">M</th>
                                <th class="text-center" style="width: 80px;">M</th>
                                <th class="text-center" style="width: 80px;">J</th>
                                <th class="text-center" style="width: 80px;">V</th>
                                <th class="text-center" style="width: 80px;">S</th>
                                <th class="text-center" style="width: 100px;">Total</th>
                            </tr>
                        </thead>
                        <tbody id="tablaBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.HORAS_EXTRAS_CONFIG = {
            rutas: {
                historial: "{{ route('horas-extras.historial') }}",
                base: "{{ url('horas-extras') }}",
                aprobarMasivo: "{{ route('horas-extras.aprobarMasivo') }}",
                rechazarMasivo: "{{ route('horas-extras.rechazarMasivo') }}",
            },
            usuario: {
                esAdmin: {{ auth()->user()->esAdministrador() ? 'true' : 'false' }},
            }
        };
    </script>
    @vite(['resources/js/horas-extras.js'])
@endpush