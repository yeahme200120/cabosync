@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    @vite(['resources/css/dashboard.css'])
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 class="mb-0 text-cabosync-primary">
            <i class="bi bi-speedometer2"></i> Panel de Control
        </h2>
        <small class="text-muted">
            Bienvenido, <strong>{{ auth()->user()->nombre }}</strong>
            @if (auth()->user()->empresa)
                <span class="badge bg-cabosync-primary ms-2">{{ auth()->user()->empresa->nombre }}</span>
            @endif
            &nbsp;|&nbsp;
            <i class="bi bi-calendar-range"></i>
            {{ $inicio->format('d/m/Y') }} — {{ $fin->format('d/m/Y') }}
        </small>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="d-flex gap-2 flex-wrap align-items-end">
        @if ($esAdmin)
            <div>
                <label class="form-label small fw-bold mb-1">Empresa</label>
                <select name="empresa_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todas las empresas</option>
                    @foreach ($empresas as $emp)
                        <option value="{{ $emp->id }}" {{ $empresaFiltroId == $emp->id ? 'selected' : '' }}>
                            {{ $emp->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        @elseif ($esContratista || $esJefe)
            <div>
                <label class="form-label small fw-bold mb-1">Empresa</label>
                <select class="form-select form-select-sm" disabled>
                    @foreach ($empresas as $emp)
                        <option value="{{ $emp->id }}" selected>{{ $emp->nombre }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($obras->count() > 0)
            <div>
                <label class="form-label small fw-bold mb-1">Obra</label>
                <select name="obra_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todas las obras</option>
                    @foreach ($obras as $obra)
                        <option value="{{ $obra->id }}" {{ $obraId == $obra->id ? 'selected' : '' }}>
                            {{ $obra->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label class="form-label small fw-bold mb-1">Rango</label>
            <select name="rango" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="mes"    {{ $rango == 'mes' ? 'selected' : '' }}>Mes actual</option>
                <option value="semana" {{ $rango == 'semana' ? 'selected' : '' }}>Semana actual</option>
                <option value="7dias"  {{ $rango == '7dias' ? 'selected' : '' }}>Últimos 7 días</option>
                <option value="30dias" {{ $rango == '30dias' ? 'selected' : '' }}>Últimos 30 días</option>
            </select>
        </div>
    </form>
</div>

{{-- ============================================
     KPI CARDS PRINCIPALES
     ============================================ --}}
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi-card-dash">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <p class="kpi-label">% Asistencia</p>
                        <h3 class="kpi-value text-cabosync-primary">{{ $porcentajeAsistencia }}%</h3>
                        <small class="kpi-sub">
                            <i class="bi bi-check2-circle text-success"></i>
                            {{ $totalAsistencias }} de {{ $totalDiasEsperados }} días
                        </small>
                    </div>
                    <div class="kpi-icon bg-gradient-primary">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
                <div class="kpi-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-cabosync-primary"
                             style="width: {{ $porcentajeAsistencia }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi-card-dash">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <p class="kpi-label">Empleados activos</p>
                        <h3 class="kpi-value text-info">{{ $totalEmpleados }}</h3>
                        <small class="kpi-sub">
                            <i class="bi bi-people-fill"></i> Registrados y activos
                        </small>
                    </div>
                    <div class="kpi-icon bg-gradient-info">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <div class="kpi-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi-card-dash">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <p class="kpi-label">Horas extras</p>
                        <h3 class="kpi-value text-warning">{{ (int) $horasExtrasAprobadas }}h</h3>
                        <small class="kpi-sub">
                            <i class="bi bi-hourglass-split text-warning"></i>
                            {{ (int) $horasExtrasPendientes }}h pendientes
                        </small>
                    </div>
                    <div class="kpi-icon bg-gradient-warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <div class="kpi-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning"
                             style="width: {{ ($horasExtrasAprobadas + $horasExtrasPendientes) > 0 ? round($horasExtrasAprobadas / ($horasExtrasAprobadas + $horasExtrasPendientes) * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi-card-dash">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <p class="kpi-label">Faltas</p>
                        <h3 class="kpi-value text-danger">{{ $faltasInjustificadas }}</h3>
                        <small class="kpi-sub">
                            <i class="bi bi-check-circle text-success"></i> {{ $faltasJustificadas }} justificadas
                            <br>
                            <strong class="text-danger">{{ $diasDescontados }}</strong> días descontados
                        </small>
                    </div>
                    <div class="kpi-icon bg-gradient-danger">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
                <div class="kpi-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-danger"
                             style="width: {{ ($faltasJustificadas + $faltasInjustificadas) > 0 ? round($faltasInjustificadas / ($faltasJustificadas + $faltasInjustificadas) * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================
     GRÁFICOS: LÍNEA + DONA PUESTOS
     ============================================ --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100 chart-card">
            <div class="card-header bg-gradient-primary text-white py-3">
                <h6 class="mb-0">
                    <i class="bi bi-graph-up-arrow"></i> Asistencia últimos 7 días
                </h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 320px;">
                    <canvas id="chartAsistenciaLinea"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100 chart-card">
            <div class="card-header bg-gradient-primary text-white py-3">
                <h6 class="mb-0">
                    <i class="bi bi-pie-chart-fill"></i> Distribución por puesto
                </h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 320px;">
                    <canvas id="chartPuestos"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================
     GRÁFICOS: FALTAS + EMPLEADOS POR EMPRESA
     ============================================ --}}
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100 chart-card">
            <div class="card-header bg-gradient-danger text-white py-3">
                <h6 class="mb-0">
                    <i class="bi bi-clipboard-x"></i> Faltas justificadas vs injustificadas
                </h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 260px;">
                    <canvas id="chartFaltas"></canvas>
                </div>
            </div>
        </div>
    </div>

    @if ($esAdmin && count($labelsEmpresas) > 0)
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100 chart-card">
                <div class="card-header bg-gradient-info text-white py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-bar-chart-fill"></i> Empleados por empresa
                    </h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 260px;">
                        <canvas id="chartEmpresas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100 chart-card">
                <div class="card-header bg-gradient-info text-white py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-building"></i> Obras activas
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 260px;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Obra</th>
                                    <th>Empresa</th>
                                    <th class="text-center">Estatus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($obrasActivas as $obra)
                                    <tr>
                                        <td class="small fw-semibold">
                                            <i class="bi bi-hammer text-cabosync-primary"></i>
                                            {{ $obra->nombre }}
                                        </td>
                                        <td class="small text-muted">{{ $obra->empresa?->nombre ?? '—' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                                                Activa
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.4;"></i>
                                            <p class="mb-0 mt-2">Sin obras activas</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- ============================================
     TOP 5 + BITÁCORA
     ============================================ --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 chart-card">
            <div class="card-header bg-gradient-success text-white py-3">
                <h6 class="mb-0">
                    <i class="bi bi-trophy-fill"></i> Top 5 con más asistencias
                </h6>
            </div>
            <div class="card-body">
                @forelse ($topAsistencia as $index => $emp)
                    <div class="d-flex align-items-center mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }} rank-row">
                        <span class="badge rank-badge rank-badge-{{ $index + 1 }}">
                            {{ $index + 1 }}
                        </span>
                        <div class="flex-grow-1 min-w-0 ms-3">
                            <strong class="d-block text-truncate">
                                {{ $emp->nombre }} {{ $emp->apellido }}
                            </strong>
                            <small class="text-muted text-truncate d-block">
                                {{ $emp->puesto_cargo }}
                            </small>
                        </div>
                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                            {{ $emp->asistencias_count }} días
                        </span>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox" style="font-size: 2.5rem; opacity: 0.4;"></i>
                        <p class="mb-0 mt-2">Sin datos en el rango seleccionado</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 chart-card">
            <div class="card-header bg-gradient-danger text-white py-3">
                <h6 class="mb-0">
                    <i class="bi bi-exclamation-triangle-fill"></i> Top 5 con más faltas
                </h6>
            </div>
            <div class="card-body">
                @forelse ($topFaltas as $index => $emp)
                    <div class="d-flex align-items-center mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }} rank-row">
                        <span class="badge rank-badge rank-badge-danger">
                            {{ $index + 1 }}
                        </span>
                        <div class="flex-grow-1 min-w-0 ms-3">
                            <strong class="d-block text-truncate">
                                {{ $emp->nombre }} {{ $emp->apellido }}
                            </strong>
                            <small class="text-muted text-truncate d-block">
                                {{ $emp->puesto_cargo }}
                            </small>
                        </div>
                        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                            {{ $emp->faltas_count }} faltas
                        </span>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox" style="font-size: 2.5rem; opacity: 0.4;"></i>
                        <p class="mb-0 mt-2">Sin datos en el rango seleccionado</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ============================================
     ÚLTIMOS MOVIMIENTOS
     ============================================ --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm chart-card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-clock-history"></i> Últimos movimientos
                </h6>
                @if (auth()->user()->esAdministrador() || auth()->user()->esContratista())
                    <a href="{{ route('bitacora.index') }}" class="btn btn-sm btn-outline-secondary">
                        Ver bitácora completa <i class="bi bi-arrow-right"></i>
                    </a>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 140px;">Fecha</th>
                                <th style="width: 200px;">Usuario</th>
                                <th style="width: 180px;">Acción</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ultimosMovimientos as $mov)
                                <tr>
                                    <td class="small">
                                        <i class="bi bi-clock text-muted"></i>
                                        {{ $mov->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="small">
                                        <i class="bi bi-person-circle text-cabosync-primary"></i>
                                        {{ $mov->usuario?->nombre ?? 'Sistema' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $mov->accion }}</span>
                                    </td>
                                    <td class="small text-muted">
                                        {{ \Illuminate\Support\Str::limit($mov->descripcion, 100) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.4;"></i>
                                        <p class="mb-0 mt-2">Sin movimientos recientes</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ============================================
    // DATOS DEL BACKEND
    // ============================================
    const dias7 = @json($dias7);
    const asistencias7 = @json($asistencias7);
    const faltas7 = @json($faltas7);

    const labelsPuestos = @json($labelsPuestos);
    const dataPuestos = @json($dataPuestos);

    const labelsFaltas = @json($labelsFaltas);
    const dataFaltas = @json($dataFaltas);

    const labelsEmpresas = @json($labelsEmpresas);
    const dataEmpleadosEmpresa = @json($dataEmpleadosEmpresa);

    const coloresPuestos = [
        '#1E5180', '#F28C28', '#4a90e2', '#28a745', '#ffc107',
        '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d',
    ];

    // ============================================
    // OPCIONES COMUNES
    // ============================================
    const chartFont = { family: 'system-ui, -apple-system, sans-serif', size: 12 };

    // ============================================
    // GRÁFICO 1: ASISTENCIA ÚLTIMOS 7 DÍAS
    // ============================================
    const ctxLinea = document.getElementById('chartAsistenciaLinea');
    if (ctxLinea) {
        new Chart(ctxLinea, {
            type: 'line',
            data: {
                labels: dias7,
                datasets: [
                    {
                        label: 'Asistencias',
                        data: asistencias7,
                        borderColor: '#28a745',
                        backgroundColor: (ctx) => {
                            const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 300);
                            gradient.addColorStop(0, 'rgba(40, 167, 69, 0.4)');
                            gradient.addColorStop(1, 'rgba(40, 167, 69, 0)');
                            return gradient;
                        },
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        borderWidth: 3,
                    },
                    {
                        label: 'Faltas',
                        data: faltas7,
                        borderColor: '#dc3545',
                        backgroundColor: (ctx) => {
                            const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 300);
                            gradient.addColorStop(0, 'rgba(220, 53, 69, 0.4)');
                            gradient.addColorStop(1, 'rgba(220, 53, 69, 0)');
                            return gradient;
                        },
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#dc3545',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        borderWidth: 3,
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart',
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 15,
                            font: { ...chartFont, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 81, 128, 0.95)',
                        padding: 12,
                        titleFont: { ...chartFont, size: 13, weight: 'bold' },
                        bodyFont: { ...chartFont, size: 12 },
                        borderColor: '#1E5180',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        usePointStyle: true,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: chartFont,
                            color: '#6c757d',
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                        }
                    },
                    x: {
                        ticks: {
                            font: { ...chartFont, weight: '600' },
                            color: '#6c757d',
                        },
                        grid: {
                            display: false,
                        }
                    }
                }
            }
        });
    }

    // ============================================
    // GRÁFICO 2: DISTRIBUCIÓN POR PUESTO
    // ============================================
    const ctxPuestos = document.getElementById('chartPuestos');
    if (ctxPuestos && labelsPuestos.length > 0) {
        const totalPuestos = dataPuestos.reduce((a, b) => a + b, 0);

        new Chart(ctxPuestos, {
            type: 'doughnut',
            data: {
                labels: labelsPuestos,
                datasets: [{
                    data: dataPuestos,
                    backgroundColor: coloresPuestos.map(c => c + 'E6'),
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 12,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1200,
                    easing: 'easeOutQuart',
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: { ...chartFont, size: 11 },
                            usePointStyle: true,
                            pointStyle: 'circle',
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 81, 128, 0.95)',
                        padding: 12,
                        titleFont: { ...chartFont, size: 13, weight: 'bold' },
                        bodyFont: { ...chartFont, size: 12 },
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const valor = context.parsed;
                                const pct = totalPuestos > 0
                                    ? ((valor / totalPuestos) * 100).toFixed(1)
                                    : 0;
                                return ` ${context.label}: ${valor} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // ============================================
    // GRÁFICO 3: FALTAS JUSTIFICADAS VS INJUSTIFICADAS
    // ============================================
    const ctxFaltas = document.getElementById('chartFaltas');
    if (ctxFaltas) {
        const totalFaltas = dataFaltas.reduce((a, b) => a + b, 0);

        new Chart(ctxFaltas, {
            type: 'doughnut',
            data: {
                labels: labelsFaltas,
                datasets: [{
                    data: dataFaltas,
                    backgroundColor: ['#ffc107E6', '#dc3545E6'],
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 12,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1200,
                    easing: 'easeOutQuart',
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: { ...chartFont, size: 11 },
                            usePointStyle: true,
                            pointStyle: 'circle',
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 81, 128, 0.95)',
                        padding: 12,
                        titleFont: { ...chartFont, size: 13, weight: 'bold' },
                        bodyFont: { ...chartFont, size: 12 },
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const valor = context.parsed;
                                const pct = totalFaltas > 0
                                    ? ((valor / totalFaltas) * 100).toFixed(1)
                                    : 0;
                                return ` ${context.label}: ${valor} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // ============================================
    // GRÁFICO 4: EMPLEADOS POR EMPRESA (BARRA)
    // ============================================
    const ctxEmpresas = document.getElementById('chartEmpresas');
    if (ctxEmpresas && labelsEmpresas.length > 0) {
        new Chart(ctxEmpresas, {
            type: 'bar',
            data: {
                labels: labelsEmpresas,
                datasets: [{
                    label: 'Empleados',
                    data: dataEmpleadosEmpresa,
                    backgroundColor: (ctx) => {
                        const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 260);
                        gradient.addColorStop(0, '#4a90e2');
                        gradient.addColorStop(1, '#1E5180');
                        return gradient;
                    },
                    borderRadius: 8,
                    borderSkipped: false,
                    hoverBackgroundColor: '#F28C28',
                    barThickness: 'flex',
                    maxBarThickness: 50,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(30, 81, 128, 0.95)',
                        padding: 12,
                        titleFont: { ...chartFont, size: 13, weight: 'bold' },
                        bodyFont: { ...chartFont, size: 12 },
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return ` ${context.parsed.y} empleados`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: chartFont,
                            color: '#6c757d',
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                        }
                    },
                    x: {
                        ticks: {
                            font: { ...chartFont, size: 11 },
                            color: '#6c757d',
                            maxRotation: 45,
                            minRotation: 0,
                        },
                        grid: {
                            display: false,
                        }
                    }
                }
            }
        });
    }
</script>
@endpush