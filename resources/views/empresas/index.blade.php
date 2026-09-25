@extends('layouts.app')

@section('title', 'Empresas')

@push('styles')
    @vite(['resources/css/empresas.css'])
@endpush

@section('content')
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-building"></i> Gestión de Empresas
                </h2>
                <small class="text-muted">
                    <span id="contadorEmpresas">{{ $empresas->total() }}</span> empresas registradas
                </small>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-cabosync-primary d-none d-md-inline-flex" data-bs-toggle="modal"
                    data-bs-target="#modalEmpresa" onclick="resetFormEmpresa()">
                    <i class="bi bi-plus-lg"></i> Registrar Empresa
                </button>
            </div>
        </div>

        {{-- FILTROS --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('empresas.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">Buscar</label>
                        <input type="text" name="busqueda" value="{{ request('busqueda') }}"
                            class="form-control form-control-sm" placeholder="Nombre o RFC...">
                    </div>

                    @if (auth()->user()->esAdministrador())
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Tipo</label>
                            <select name="tipo" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="matriz" {{ request('tipo') == 'matriz' ? 'selected' : '' }}>Matriz</option>
                                <option value="externa" {{ request('tipo') == 'externa' ? 'selected' : '' }}>Externa</option>
                            </select>
                        </div>
                    @endif

                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Estatus</label>
                        <select name="estatus" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="activo" {{ request('estatus') == 'activo' ? 'selected' : '' }}>Activas</option>
                            <option value="inactivo" {{ request('estatus') == 'inactivo' ? 'selected' : '' }}>Inactivas</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-cabosync-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="{{ route('empresas.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- GRID O EMPTY STATE --}}
        @if ($empresas->isEmpty())
            <div class="empty-state" id="emptyState">
                <i class="bi bi-building"></i>
                <h4>No hay empresas registradas</h4>
                <p class="text-muted">
                    @if (request()->hasAny(['busqueda', 'tipo', 'estatus']))
                        No se encontraron resultados con esos filtros.
                    @else
                        Comienza registrando tu primera empresa.
                    @endif
                </p>
                <button class="btn btn-cabosync-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalEmpresa"
                    onclick="resetFormEmpresa()">
                    <i class="bi bi-plus-lg"></i> Registrar primera empresa
                </button>
            </div>
        @else
            <div class="empresas-grid" id="gridEmpresas">
                @foreach ($empresas as $empresa)
                    @include('empresas._card', ['empresa' => $empresa])
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $empresas->firstItem() ?? 0 }} a {{ $empresas->lastItem() ?? 0 }}
                    de <span id="totalRegistros">{{ $empresas->total() }}</span> registros
                </small>
                {{ $empresas->links() }}
            </div>
        @endif

        {{-- FAB --}}
        <button class="fab-cabosync" data-bs-toggle="modal" data-bs-target="#modalEmpresa" onclick="resetFormEmpresa()"
            title="Registrar Empresa">
            <i class="bi bi-plus-lg"></i>
        </button>

        {{-- MODAL CREAR / EDITAR EMPRESA --}}
        <div class="modal fade" id="modalEmpresa" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form id="formEmpresa">
                        @csrf
                        <input type="hidden" name="empresa_id" id="empresaId" value="">

                        <div class="modal-header bg-cabosync-primary text-white">
                            <h5 class="modal-title" id="modalEmpresaTitulo">
                                <i class="bi bi-building-add"></i> Registrar Nueva Empresa
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">
                                        Nombre <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="nombre" id="nombre" class="form-control" required
                                        maxlength="255" placeholder="Ej. Cabo And Home S.A. de C.V.">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">RFC</label>
                                    <input type="text" name="rfc" id="rfc" class="form-control" maxlength="50"
                                        placeholder="Ej. CAB123456ABC">
                                    <small class="text-muted">Opcional</small>
                                </div>

                                @if (auth()->user()->esAdministrador())
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-uppercase">
                                            Tipo <span class="text-danger">*</span>
                                        </label>
                                        <select name="tipo" id="tipo" class="form-select" required>
                                            <option value="externa">Externa (Contratista)</option>
                                            <option value="matriz">Matriz (ID SOFTWARE HOUSE)</option>
                                        </select>
                                        <small class="text-muted">Solo puede existir una empresa matriz.</small>
                                    </div>
                                @else
                                    <input type="hidden" name="tipo" id="tipo" value="externa">
                                @endif

                                <div class="col-md-6" id="campoEstatusEmpresa" style="display: none;">
                                    <label class="form-label fw-bold small text-uppercase">Estatus</label>
                                    <select name="estatus" id="estatus" class="form-select">
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-cabosync-primary" id="btnGuardarEmpresa">
                                <i class="bi bi-check-lg"></i> <span id="btnGuardarEmpresaTexto">Registrar</span>
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
        window.EMPRESAS_CONFIG = {
            rutas: {
                index: "{{ route('empresas.index') }}",
                store: "{{ route('empresas.store') }}",
                base:  "{{ url('empresas') }}",
            },
            usuario: {
                esAdmin:       {{ auth()->user()->esAdministrador() ? 'true' : 'false' }},
                esContratista: {{ auth()->user()->esContratista() ? 'true' : 'false' }},
                empresaId:     {{ auth()->user()->empresa_id ?? 'null' }},
            }
        };
    </script>

    @vite(['resources/js/empresas.js'])
@endpush