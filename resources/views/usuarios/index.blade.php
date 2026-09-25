@extends('layouts.app')

@section('title', 'Usuarios')

@push('styles')
    @vite(['resources/css/usuarios.css'])
@endpush

@section('content')
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-person-badge"></i> Gestión de Usuarios
                </h2>
                <small class="text-muted">
                    <span id="contadorUsuarios">{{ $usuarios->total() }}</span> usuarios registrados
                </small>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-cabosync-secondary" data-bs-toggle="modal" data-bs-target="#modalImportarUsuarios">
                    <i class="bi bi-upload"></i> Importar
                </button>
                <button class="btn btn-cabosync-primary d-none d-md-inline-flex" data-bs-toggle="modal"
                    data-bs-target="#modalUsuario" onclick="resetFormUsuario()">
                    <i class="bi bi-plus-lg"></i> Registrar Usuario
                </button>
            </div>
        </div>

        {{-- FILTROS --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('usuarios.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Buscar</label>
                        <input type="text" name="busqueda" value="{{ request('busqueda') }}"
                            class="form-control form-control-sm" placeholder="Nombre o email...">
                    </div>

                    @if (auth()->user()->esAdministrador() && $empresas->count() > 0)
                        <div class="col-md-2">
                            <label class="form-label small fw-bold mb-1">Empresa</label>
                            <select name="empresa_id" class="form-select form-select-sm">
                                <option value="" disabled>Todas</option>
                                @foreach ($empresas as $emp)
                                    <option value="{{ $emp->id }}"
                                        {{ request('empresa_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Rol</label>
                        <select name="rol_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach ($rolesPermitidos as $rol)
                                <option value="{{ $rol->id }}" {{ request('rol_id') == $rol->id ? 'selected' : '' }}>
                                    {{ $rol->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Estatus</label>
                        <select name="estatus" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="activo" {{ request('estatus') == 'activo' ? 'selected' : '' }}>Activos</option>
                            <option value="inactivo" {{ request('estatus') == 'inactivo' ? 'selected' : '' }}>Inactivos
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-cabosync-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="{{ route('usuarios.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- GRID O EMPTY STATE --}}
        @if ($usuarios->isEmpty())
            <div class="empty-state" id="emptyState">
                <i class="bi bi-person-badge"></i>
                <h4>No hay usuarios registrados</h4>
                <p class="text-muted">
                    @if (request()->hasAny(['busqueda', 'empresa_id', 'rol_id', 'estatus']))
                        No se encontraron resultados con esos filtros.
                    @else
                        Comienza registrando tu primer usuario.
                    @endif
                </p>
                <button class="btn btn-cabosync-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalUsuario"
                    onclick="resetFormUsuario()">
                    <i class="bi bi-plus-lg"></i> Registrar primer usuario
                </button>
            </div>
        @else
            <div class="usuarios-grid" id="gridUsuarios">
                @foreach ($usuarios as $usuario)
                    @include('usuarios._card', ['usuario' => $usuario])
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $usuarios->firstItem() ?? 0 }} a {{ $usuarios->lastItem() ?? 0 }}
                    de <span id="totalRegistros">{{ $usuarios->total() }}</span> registros
                </small>
                {{ $usuarios->links() }}
            </div>
        @endif

        {{-- FAB --}}
        <button class="fab-cabosync" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="resetFormUsuario()"
            title="Registrar Usuario">
            <i class="bi bi-plus-lg"></i>
        </button>

        {{-- MODAL CREAR / EDITAR USUARIO --}}
        <div class="modal fade" id="modalUsuario" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form id="formUsuario">
                        @csrf
                        <input type="hidden" name="usuario_id" id="usuarioId" value="">

                        <div class="modal-header bg-cabosync-primary text-white">
                            <h5 class="modal-title" id="modalUsuarioTitulo">
                                <i class="bi bi-person-plus-fill"></i> Registrar Nuevo Usuario
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">
                                        Nombre completo <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="nombre" id="nombre" class="form-control" required
                                        maxlength="255" placeholder="Ej. Juan Pérez García">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" name="email" id="email" class="form-control" required
                                        maxlength="255" placeholder="usuario@empresa.com">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">
                                        Rol <span class="text-danger">*</span>
                                    </label>
                                    <select name="rol_id" id="rol_id" class="form-select" required>
                                        <option value="">Selecciona un rol</option>
                                        @foreach ($rolesPermitidos as $rol)
                                            <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

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

                                <div class="col-md-6 d-none" id="campoEstatusUsuario">
                                    <label class="form-label fw-bold small text-uppercase">Estatus</label>
                                    <select name="estatus" id="estatus" class="form-select">
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="avisoPasswordTemporal" style="display: none;">
                                    <div class="alert alert-info small mb-0">
                                        <i class="bi bi-info-circle-fill"></i>
                                        Se generará una <strong>contraseña temporal</strong> automáticamente y se enviará al
                                        correo del usuario.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-cabosync-primary" id="btnGuardarUsuario">
                                <i class="bi bi-check-lg"></i> <span id="btnGuardarUsuarioTexto">Registrar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL RESULTADO CREAR (muestra contraseña temporal) --}}
        <div class="modal fade" id="modalPasswordTemporal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-check-circle-fill"></i> Usuario creado
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">El usuario ha sido creado exitosamente. Comparte estas credenciales:</p>

                        <div class="alert alert-info mb-3">
                            <p class="mb-1"><strong>Email:</strong> <span id="resultEmail">-</span></p>
                            <p class="mb-0"><strong>Contraseña temporal:</strong>
                                <code id="resultPassword" class="bg-white px-2 py-1 rounded border">-</code>
                            </p>
                        </div>

                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-info-circle"></i>
                            Se envió un correo con las credenciales. El usuario deberá cambiar la contraseña al iniciar
                            sesión.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cabosync-primary"
                            data-bs-dismiss="modal">Entendido</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL IMPORTAR USUARIOS (4 PASOS) --}}
        <div class="modal fade" id="modalImportarUsuarios" tabindex="-1" data-bs-backdrop="static"
            data-bs-keyboard="false">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-cabosync-secondary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-upload"></i> Importar Usuarios
                            <span class="badge bg-light text-dark ms-2" id="importUsuarioPasoBadge">Paso 1 de 4</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        {{-- ============================================
                             PASO 1: SELECCIONAR FORMATO + ROLES DISPONIBLES
                             ============================================ --}}
                        <div id="importUsuarioPaso1">
                            <div class="text-center mb-4">
                                <h5 class="text-cabosync-primary mb-1">¿En qué formato quieres importar?</h5>
                                <small class="text-muted">Selecciona un formato y descarga la plantilla</small>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="import-formato-card" data-formato="csv">
                                        <i class="bi bi-filetype-csv"></i>
                                        <h6>CSV</h6>
                                        <small>Archivo separado por comas</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="import-formato-card" data-formato="xlsx">
                                        <i class="bi bi-file-earmark-spreadsheet"></i>
                                        <h6>Excel</h6>
                                        <small>Archivo .xlsx</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="import-formato-card" data-formato="sql">
                                        <i class="bi bi-database"></i>
                                        <h6>SQL</h6>
                                        <small>Archivo .sql con INSERTs</small>
                                    </div>
                                </div>
                            </div>

                            {{-- CATÁLOGO DE ROLES DISPONIBLES --}}
                            <div class="card border-0 bg-light mb-3">
                                <div class="card-body py-3">
                                    <h6 class="mb-2">
                                        <i class="bi bi-info-circle-fill text-cabosync-primary"></i>
                                        Roles disponibles para asignar
                                    </h6>
                                    <p class="small text-muted mb-2">
                                        En el campo <code>ROL_CODIGO</code> debes usar uno de estos códigos:
                                    </p>
                                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 40%;">Código</th>
                                                    <th>Nombre</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach (\App\Models\Rol::where('tipo', 'sistema')->orderBy('nombre')->get() as $rol)
                                                    @if (!(auth()->user()->esContratista() && $rol->codigo === 'admin'))
                                                        <tr>
                                                            <td><code>{{ $rol->codigo }}</code></td>
                                                            <td>{{ $rol->nombre }}</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 bg-light mb-3">
                                <div class="card-body py-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="bi bi-download text-cabosync-secondary"></i>
                                                Descarga la plantilla de ejemplo
                                            </h6>
                                            <small class="text-muted">Llénala con tus datos y súbela</small>
                                        </div>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-cabosync-primary btn-descargar-plantilla-usuario"
                                                data-tipo="csv">
                                                <i class="bi bi-filetype-csv"></i> Descargar CSV
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-cabosync-primary btn-descargar-plantilla-usuario"
                                                data-tipo="excel">
                                                <i class="bi bi-file-earmark-spreadsheet"></i> Descargar Excel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info small mb-0">
                                <i class="bi bi-lightbulb-fill"></i>
                                <strong>Recomendación:</strong> Descarga la plantilla, llénala con tus datos y súbela.
                                Los campos <strong>NOMBRE</strong>, <strong>EMAIL</strong> y <strong>ROL_CODIGO</strong> son
                                obligatorios. La <strong>empresa</strong> se asigna automáticamente:
                                @if (auth()->user()->esContratista())
                                    a tu empresa (<strong>{{ auth()->user()->empresa?->nombre }}</strong>).
                                @else
                                    a la primera empresa activa del sistema.
                                @endif
                                La contraseña se genera automáticamente y se envía por correo.
                            </div>
                        </div>

                        {{-- ============================================
                             PASO 2: PREVISUALIZAR
                             ============================================ --}}
                        <div id="importUsuarioPaso2" class="d-none">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div>
                                    <h5 class="text-cabosync-primary mb-1" id="importUsuarioNombreArchivo">-</h5>
                                    <small class="text-muted">
                                        <span id="importUsuarioTotalFilas">0</span> filas detectadas
                                    </small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    onclick="volverPaso1Usuario()">
                                    <i class="bi bi-arrow-left"></i> Volver
                                </button>
                            </div>

                            {{-- Preview tabla (CSV/Excel) --}}
                            <div id="importUsuarioTablaPreview" class="table-responsive"
                                style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light sticky-top">
                                        <tr id="importUsuarioPreviewHead"></tr>
                                    </thead>
                                    <tbody id="importUsuarioPreviewBody"></tbody>
                                </table>
                            </div>

                            {{-- Preview SQL --}}
                            <div id="importUsuarioSqlPreview" class="d-none mt-3">
                                <div class="alert alert-info small mb-3">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <strong>Modo SQL:</strong> Los campos que no estén en el archivo SQL
                                    (empresa, estatus) se asignarán automáticamente con valores por defecto.
                                </div>

                                <label class="form-label small fw-bold">Previsualización del SQL (primeras 50
                                    líneas)</label>
                                <textarea id="importUsuarioSqlText" class="form-control font-monospace" rows="12" readonly></textarea>
                            </div>

                            <div class="text-muted small mt-2">
                                <i class="bi bi-info-circle"></i>
                                Mostrando las primeras 10 filas de <span id="importUsuarioTotalFilas2">0</span>.
                            </div>
                        </div>

                        {{-- ============================================
                             PASO 3: PROGRESO
                             ============================================ --}}
                        <div id="importUsuarioPaso3" class="d-none text-center py-4">
                            <div class="mb-4">
                                <img src="{{ asset('img/logo-cabosync.png') }}" alt="CaboSync" style="height: 80px;"
                                    class="mb-3">
                            </div>

                            <h5 class="text-cabosync-primary mb-3">Importando usuarios...</h5>

                            <div class="progress mb-3" style="height: 25px;">
                                <div id="importUsuarioProgressBar"
                                    class="progress-bar progress-bar-striped progress-bar-animated bg-cabosync-primary"
                                    role="progressbar" style="width: 0%;">0%</div>
                            </div>

                            <p class="text-muted mb-4" id="importUsuarioProgresoMensaje">Preparando...</p>

                            <div class="row g-3 justify-content-center">
                                <div class="col-md-2">
                                    <div class="import-contador import-contador--ok">
                                        <div class="import-contador__num" id="contadorUsuariosCargados">0</div>
                                        <div class="import-contador__label">✅ Cargados</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="import-contador import-contador--warn">
                                        <div class="import-contador__num" id="contadorUsuariosActualizados">0</div>
                                        <div class="import-contador__label">⚠️ Actualizados</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="import-contador import-contador--info">
                                        <div class="import-contador__num" id="contadorUsuariosOmitidos">0</div>
                                        <div class="import-contador__label">⏭️ Omitidos</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="import-contador import-contador--error">
                                        <div class="import-contador__num" id="contadorUsuariosErrores">0</div>
                                        <div class="import-contador__label">❌ Errores</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================
                             PASO 4: RESULTADO
                             ============================================ --}}
                        <div id="importUsuarioPaso4" class="d-none">
                            <div class="text-center mb-4">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                                <h4 class="mt-2 text-cabosync-primary">¡Importación completada!</h4>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="import-contador import-contador--ok">
                                        <div class="import-contador__num" id="resumenUsuariosCargados">0</div>
                                        <div class="import-contador__label">✅ Cargados</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="import-contador import-contador--warn">
                                        <div class="import-contador__num" id="resumenUsuariosActualizados">0</div>
                                        <div class="import-contador__label">⚠️ Actualizados</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="import-contador import-contador--info">
                                        <div class="import-contador__num" id="resumenUsuariosOmitidos">0</div>
                                        <div class="import-contador__label">⏭️ Omitidos</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="import-contador import-contador--error">
                                        <div class="import-contador__num" id="resumenUsuariosErrores">0</div>
                                        <div class="import-contador__label">❌ Errores</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tabs de detalle --}}
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab"
                                        data-bs-target="#tabUsuariosCargados" type="button">
                                        ✅ Cargados <span class="badge bg-success" id="badgeUsuariosCargados">0</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab"
                                        data-bs-target="#tabUsuariosActualizados" type="button">
                                        ⚠️ Actualizados <span class="badge bg-warning text-dark"
                                            id="badgeUsuariosActualizados">0</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabUsuariosOmitidos"
                                        type="button">
                                        ⏭️ Omitidos <span class="badge bg-info text-dark"
                                            id="badgeUsuariosOmitidos">0</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabUsuariosErrores"
                                        type="button">
                                        ❌ Errores <span class="badge bg-danger" id="badgeUsuariosErrores">0</span>
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="tabUsuariosCargados">
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nombre</th>
                                                    <th>Email</th>
                                                    <th>ID</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaUsuariosCargados"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tabUsuariosActualizados">
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nombre</th>
                                                    <th>Email</th>
                                                    <th>Cambios</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaUsuariosActualizados"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tabUsuariosOmitidos">
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nombre</th>
                                                    <th>Email</th>
                                                    <th>Motivo</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaUsuariosOmitidos"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tabUsuariosErrores">
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>Fila</th>
                                                    <th>Campo</th>
                                                    <th>Motivo</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaUsuariosErrores"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <div id="importUsuarioFooterPaso1" class="d-flex justify-content-end w-100">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </button>
                        </div>

                        <div id="importUsuarioFooterPaso2" class="d-none justify-content-between w-100">
                            <button type="button" class="btn btn-secondary" onclick="volverPaso1Usuario()">
                                <i class="bi bi-arrow-left"></i> Volver
                            </button>
                            <button type="button" class="btn btn-cabosync-secondary"
                                onclick="iniciarImportacionUsuario()">
                                <i class="bi bi-upload"></i> Importar <span id="importUsuarioCantidadBtn"></span>
                            </button>
                        </div>

                        <div id="importUsuarioFooterPaso3" class="d-none justify-content-end w-100">
                            <button type="button" class="btn btn-secondary" disabled>
                                <i class="bi bi-hourglass-split"></i> Procesando...
                            </button>
                        </div>

                        <div id="importUsuarioFooterPaso4" class="d-none justify-content-between w-100">
                            <button type="button" class="btn btn-outline-secondary"
                                onclick="volverAlPaso1DesdeResultadoUsuario()">
                                <i class="bi bi-arrow-clockwise"></i> Importar otro
                            </button>
                            <button type="button" class="btn btn-cabosync-primary" onclick="window.location.reload()">
                                <i class="bi bi-check-lg"></i> Ver grid actualizado
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        window.USUARIOS_CONFIG = {
            rutas: {
                index: "{{ route('usuarios.index') }}",
                store: "{{ route('usuarios.store') }}",
                base: "{{ url('usuarios') }}",
            },
            usuario: {
                esAdmin: {{ auth()->user()->esAdministrador() ? 'true' : 'false' }},
                esContratista: {{ auth()->user()->esContratista() ? 'true' : 'false' }},
                empresaId: {{ auth()->user()->empresa_id ?? 'null' }},
            }
        };
    </script>

    @vite(['resources/js/usuarios.js'])
@endpush
