@extends('layouts.app')

@section('title', 'Mis Consentimientos')

@push('styles')
    @vite(['resources/css/legal.css'])
@endpush

@section('content')
    <div class="container-fluid py-4">

        <div class="mb-4">
            <h2 class="mb-0 text-cabosync-primary">
                <i class="bi bi-shield-check"></i> Mis Consentimientos
            </h2>
            <small class="text-muted">
                Historial de aceptaciones de Términos y Aviso de Privacidad
            </small>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon kpi-icon--primary">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-cabosync-primary">Términos y Condiciones</h6>
                                <small class="text-muted">
                                    Versión vigente:
                                    <span class="badge bg-cabosync-primary">v{{ $versionActualTerminos }}</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="kpi-icon kpi-icon--success">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-cabosync-primary">Aviso de Privacidad</h6>
                                <small class="text-muted">
                                    Versión vigente:
                                    <span class="badge bg-cabosync-primary">v{{ $versionActualAviso }}</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0">
                    <i class="bi bi-clock-history text-cabosync-primary"></i>
                    Historial completo
                </h6>
            </div>
            <div class="card-body p-0">
                @if ($consentimientos->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.4;"></i>
                        <p class="text-muted mt-2 mb-0">No tienes consentimientos registrados.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Versión</th>
                                    <th>Estado</th>
                                    <th>Fecha de aceptación</th>
                                    <th>IP</th>
                                    <th>Dispositivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($consentimientos as $c)
                                    <tr>
                                        <td>
                                            <span class="badge bg-cabosync-primary">
                                                {{ ucfirst(str_replace('_', ' ', $c->tipo)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <code>v{{ $c->version }}</code>
                                        </td>
                                        <td>
                                            @if ($c->aceptado && !$c->revocado_en)
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Aceptado
                                                </span>
                                            @elseif ($c->revocado_en)
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-x-circle"></i> Revocado
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Pendiente</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            {{ optional($c->aceptado_en)->format('d/m/Y H:i') ?? '-' }}
                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                {{ optional($c->aceptado_en)->diffForHumans() }}
                                            </div>
                                        </td>
                                        <td class="small text-muted">
                                            {{ $c->ip ?? '-' }}
                                        </td>
                                        <td class="small text-muted text-truncate" style="max-width: 200px;"
                                            title="{{ $c->user_agent }}">
                                            {{ \Illuminate\Support\Str::limit($c->user_agent, 40) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <a href="{{ route('legal.terminos') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-box-arrow-up-right"></i> Ver Términos vigentes
            </a>
            <a href="{{ route('legal.aviso') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-box-arrow-up-right"></i> Ver Aviso vigente
            </a>
        </div>

    </div>
@endsection