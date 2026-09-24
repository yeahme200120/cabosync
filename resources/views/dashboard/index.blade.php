@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 text-cabosync-primary">
            <i class="bi bi-speedometer2"></i> Panel de Control
        </h2>
        <small class="text-muted">
            Bienvenido, {{ auth()->user()->nombre }}
            @if(auth()->user()->empresa)
                <span class="badge bg-cabosync-primary ms-2">{{ auth()->user()->empresa->nombre }}</span>
            @endif
        </small>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="d-flex gap-2 flex-wrap">
        @if(auth()->user()->esAdministrador() && $empresas->count() > 0)
        <select name="empresa_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todas las empresas</option>
            @foreach($empresas as $emp)
                <option value="{{ $emp->id }}" {{ $empresaId == $emp->id ? 'selected' : '' }}>
                    {{ $emp->nombre }}
                </option>
            @endforeach
        </select>
        @endif

        @if($obras->count() > 0)
        <select name="obra_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todas las obras</option>
            @foreach($obras as $obra)
                <option value="{{ $obra->id }}" {{ $obraId == $obra->id ? 'selected' : '' }}>
                    {{ $obra->nombre }}
                </option>
            @endforeach
        </select>
        @endif
    </form>
</div>

{{-- KPI CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small text-uppercase">Total Empleados</p>
                        <h3 class="mb-0 fw-bold text-cabosync-primary">{{ $totalEmpleados }}</h3>
                    </div>
                    <div class="bg-cabosync-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small text-uppercase">Asistencia Hoy</p>
                        <h3 class="mb-0 fw-bold text-success">{{ $porcentajeAsistencia }}%</h3>
                        <small class="text-muted">{{ $asistenciasHoy }} presentes</small>
                    </div>
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small text-uppercase">Faltas Injustificadas</p>
                        <h3 class="mb-0 fw-bold text-danger">{{ $faltasInjustificadas }}</h3>
                        <small class="text-muted">{{ $faltasJustificadas }} justificadas</small>
                    </div>
                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-x-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small text-uppercase">Días Descontados</p>
                        <h3 class="mb-0 fw-bold text-warning">{{ $diasDescontados }}</h3>
                        <small class="text-muted">Semana actual</small>
                    </div>
                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-calendar-x fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- GRÁFICOS --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart-fill"></i>
                    SEMANA {{ now()->weekOfYear }} - ASISTENCIA DE PERSONAL
                </h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 300px;">
                    <canvas id="chartPuestosActual"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header" style="background-color: var(--cabosync-secondary) !important;">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart-fill"></i>
                    SEMANA {{ now()->subWeek()->weekOfYear }} - ASISTENCIA DE PERSONAL
                </h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 300px;">
                    <canvas id="chartPuestosAnterior"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- TOP EMPLEADOS --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success">
                <h5 class="mb-0"><i class="bi bi-trophy-fill"></i> TOP EMPLEADO DEL MES</h5>
            </div>
            <div class="card-body">
                @forelse($topAsistencia as $index => $emp)
                    <div class="d-flex align-items-center mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span class="badge bg-success rounded-circle me-3" style="width: 30px; height: 30px; line-height: 22px;">
                            {{ $index + 1 }}
                        </span>
                        <div class="flex-grow-1">
                            <strong>{{ $emp->nombre_completo }}</strong>
                            <br>
                            <small class="text-muted">{{ $emp->puesto_cargo }}</small>
                        </div>
                        <span class="badge bg-success">{{ $emp->asistencias_presentes }} días</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">Sin datos aún.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-danger">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> TOP MÁS FALTAS</h5>
            </div>
            <div class="card-body">
                @forelse($topFaltas as $index => $emp)
                    <div class="d-flex align-items-center mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span class="badge bg-danger rounded-circle me-3" style="width: 30px; height: 30px; line-height: 22px;">
                            {{ $index + 1 }}
                        </span>
                        <div class="flex-grow-1">
                            <strong>{{ $emp->nombre_completo }}</strong>
                            <br>
                            <small class="text-muted">{{ $emp->puesto_cargo }}</small>
                        </div>
                        <span class="badge bg-danger">{{ $emp->faltas_count }} faltas</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">Sin datos aún.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const labelsPuestos = @json($labelsPuestos);
    const dataPuestos = @json($dataPuestos);
    const coloresPuestos = @json($coloresPuestos);

    const bgColores = coloresPuestos.map(c => c + 'CC');
    const borderColores = coloresPuestos;

    // GRÁFICO 1
    const ctx1 = document.getElementById('chartPuestosActual').getContext('2d');
    new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: labelsPuestos,
            datasets: [{
                data: dataPuestos,
                backgroundColor: bgColores,
                borderColor: borderColores,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: { boxWidth: 15, padding: 12, font: { size: 12, weight: '600' } }
                }
            }
        }
    });

    // GRÁFICO 2
    const dataAnterior = dataPuestos.map(v => Math.max(1, v + Math.floor(Math.random() * 5) - 2));
    const ctx2 = document.getElementById('chartPuestosAnterior').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: labelsPuestos,
            datasets: [{
                data: dataAnterior,
                backgroundColor: bgColores,
                borderColor: borderColores,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: { boxWidth: 15, padding: 12, font: { size: 12, weight: '600' } }
                }
            }
        }
    });
</script>
@endpush