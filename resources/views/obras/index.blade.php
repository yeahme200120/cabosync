@extends('layouts.app')

@section('title', 'Obras')

@push('styles')
    @vite(['resources/css/obras.css'])
@endpush

@section('content')
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-hammer"></i> Gestión de Obras
                </h2>
                <small class="text-muted">
                    <span id="contadorObras">{{ $obras->total() }}</span> obras registradas
                </small>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-cabosync-primary d-none d-md-inline-flex" data-bs-toggle="modal"
                    data-bs-target="#modalObra" onclick="resetFormObra()">
                    <i class="bi bi-plus-lg"></i> Registrar Obra
                </button>
            </div>
        </div>

        {{-- FILTROS --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('obras.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">Buscar</label>
                        <input type="text" name="busqueda" value="{{ request('busqueda') }}"
                            class="form-control form-control-sm" placeholder="Nombre, código o ubicación...">
                    </div>

                    @if (auth()->user()->esAdministrador() && $empresas->count() > 0)
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Empresa</label>
                            <select name="empresa_id" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach ($empresas as $emp)
                                    <option value="{{ $emp->id }}" {{ request('empresa_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Estatus</label>
                        <select name="estatus" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="activa" {{ request('estatus') == 'activa' ? 'selected' : '' }}>Activas</option>
                            <option value="pausada" {{ request('estatus') == 'pausada' ? 'selected' : '' }}>Pausadas</option>
                            <option value="terminada" {{ request('estatus') == 'terminada' ? 'selected' : '' }}>Terminadas</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-cabosync-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="{{ route('obras.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- GRID O EMPTY STATE --}}
        @if ($obras->isEmpty())
            <div class="empty-state" id="emptyState">
                <i class="bi bi-hammer"></i>
                <h4>No hay obras registradas</h4>
                <p class="text-muted">
                    @if (request()->hasAny(['busqueda', 'empresa_id', 'estatus']))
                        No se encontraron resultados con esos filtros.
                    @else
                        Comienza registrando tu primera obra.
                    @endif
                </p>
                <button class="btn btn-cabosync-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalObra"
                    onclick="resetFormObra()">
                    <i class="bi bi-plus-lg"></i> Registrar primera obra
                </button>
            </div>
        @else
            <div class="obras-grid" id="gridObras">
                @foreach ($obras as $obra)
                    @include('obras._card', ['obra' => $obra])
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $obras->firstItem() ?? 0 }} a {{ $obras->lastItem() ?? 0 }}
                    de <span id="totalRegistros">{{ $obras->total() }}</span> registros
                </small>
                {{ $obras->links() }}
            </div>
        @endif

        {{-- FAB --}}
        <button class="fab-cabosync" data-bs-toggle="modal" data-bs-target="#modalObra" onclick="resetFormObra()"
            title="Registrar Obra">
            <i class="bi bi-plus-lg"></i>
        </button>

        {{-- MODAL CREAR / EDITAR OBRA --}}
        <div class="modal fade" id="modalObra" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form id="formObra">
                        @csrf
                        <input type="hidden" name="obra_id" id="obraId" value="">

                        <div class="modal-header bg-cabosync-primary text-white">
                            <h5 class="modal-title" id="modalObraTitulo">
                                <i class="bi bi-hammer"></i> Registrar Nueva Obra
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">
                                        Empresa <span class="text-danger">*</span>
                                    </label>
                                    <select name="empresa_id" id="empresa_id" class="form-select" required>
                                        <option value="">Selecciona una empresa</option>
                                        @foreach ($empresas as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">
                                        Nombre de la obra <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="nombre" id="nombre" class="form-control" required
                                        maxlength="255" placeholder="Ej. Lote Cielo 08">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Código</label>
                                    <input type="text" name="codigo" id="codigo" class="form-control" maxlength="50"
                                        placeholder="Ej. LC-08">
                                    <small class="text-muted">Opcional, debe ser único</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Ubicación</label>
                                    <input type="text" name="ubicacion" id="ubicacion" class="form-control" maxlength="255"
                                        placeholder="Ej. Los Cabos, BCS">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Fecha de inicio</label>
                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Fecha de fin</label>
                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                </div>

                                <div class="col-md-6" id="campoEstatusObra" style="display: none;">
                                    <label class="form-label fw-bold small text-uppercase">Estatus</label>
                                    <select name="estatus" id="estatus" class="form-select">
                                        <option value="activa">Activa</option>
                                        <option value="pausada">Pausada</option>
                                        <option value="terminada">Terminada</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-cabosync-primary" id="btnGuardarObra">
                                <i class="bi bi-check-lg"></i> <span id="btnGuardarObraTexto">Registrar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        window.OBRAS_CONFIG = {
            rutas: {
                index: "{{ route('obras.index') }}",
                store: "{{ route('obras.store') }}",
                base:  "{{ url('obras') }}",
            },
            usuario: {
                esAdmin:       {{ auth()->user()->esAdministrador() ? 'true' : 'false' }},
                esContratista: {{ auth()->user()->esContratista() ? 'true' : 'false' }},
                empresaId:     {{ auth()->user()->empresa_id ?? 'null' }},
            }
        };
    </script>

    @vite(['resources/js/obras.js'])
@endpush