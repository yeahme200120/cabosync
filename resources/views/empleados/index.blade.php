@extends('layouts.app')

@section('title', 'Empleados')

@push('styles')
@endpush

@section('content')
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-people-fill"></i> Gestión de Empleados
                </h2>
                <small class="text-muted">
                    <span id="contadorEmpleados">{{ $empleados->total() }}</span> empleados registrados
                </small>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-cabosync-secondary" data-bs-toggle="modal" data-bs-target="#modalImportar">
                    <i class="bi bi-upload"></i> Importar
                </button>
                <button class="btn btn-cabosync-primary d-none d-md-inline-flex" data-bs-toggle="modal"
                    data-bs-target="#modalEmpleado" onclick="resetFormEmpleado()">
                    <i class="bi bi-plus-lg"></i> Registrar Empleado
                </button>
            </div>
        </div>

        {{-- FILTROS --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('empleados.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">Buscar</label>
                        <input type="text" name="busqueda" value="{{ request('busqueda') }}"
                            class="form-control form-control-sm" placeholder="Buscar por nombre, apellido o CURP...">
                    </div>

                    @if (auth()->user()->esAdministrador() && $empresas->count() > 0)
                        <div class="col-md-2">
                            <label class="form-label small fw-bold mb-1">Empresa</label>
                            <select name="empresa_id" class="form-select form-select-sm">
                                <option value="">Todas</option>
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
                        <label class="form-label small fw-bold mb-1">Obra</label>
                        <select name="obra_id" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            @foreach ($obras as $obra)
                                <option value="{{ $obra->id }}" {{ request('obra_id') == $obra->id ? 'selected' : '' }}>
                                    {{ $obra->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Puesto</label>
                        <select name="rol_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach ($rolesOperativos as $rol)
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

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-cabosync-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="{{ route('empleados.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- GRID O EMPTY STATE --}}
        @if ($empleados->isEmpty())
            <div class="empty-state" id="emptyState">
                <i class="bi bi-people"></i>
                <h4>No hay empleados registrados</h4>
                <p class="text-muted">
                    @if (request()->hasAny(['busqueda', 'empresa_id', 'obra_id', 'rol_id', 'estatus']))
                        No se encontraron resultados con esos filtros.
                    @else
                        Comienza registrando tu primer empleado.
                    @endif
                </p>
                <button class="btn btn-cabosync-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalEmpleado"
                    onclick="resetFormEmpleado()">
                    <i class="bi bi-plus-lg"></i> Registrar primer empleado
                </button>
            </div>
        @else
            <div class="empleados-grid" id="gridEmpleados">
                @foreach ($empleados as $emp)
                    @include('empleados._card', ['empleado' => $emp])
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $empleados->firstItem() ?? 0 }} a {{ $empleados->lastItem() ?? 0 }}
                    de <span id="totalRegistros">{{ $empleados->total() }}</span> registros
                </small>
                {{ $empleados->links() }}
            </div>
        @endif

        {{-- FAB --}}
        <button class="fab-cabosync" data-bs-toggle="modal" data-bs-target="#modalEmpleado" onclick="resetFormEmpleado()"
            title="Registrar Empleado">
            <i class="bi bi-plus-lg"></i>
        </button>

        {{-- MODAL CREAR / EDITAR --}}
        <div class="modal fade" id="modalEmpleado" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form id="formEmpleado" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="empleado_id" id="empleadoId" value="">

                        <div class="modal-header bg-cabosync-primary text-white">
                            <h5 class="modal-title" id="modalEmpleadoTitulo">
                                <i class="bi bi-person-plus-fill"></i> Registrar Nuevo Empleado
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4 text-center">
                                    <label class="form-label fw-bold small text-uppercase d-block">Foto</label>
                                    <div class="position-relative d-inline-block">
                                        <img id="previewFoto" src="{{ asset('img/avatar-default.png') }}" alt="Preview"
                                            class="rounded-circle border shadow-sm"
                                            style="width: 140px; height: 140px; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-cabosync-primary position-absolute"
                                            style="bottom: 5px; right: 5px; border-radius: 50%;"
                                            onclick="document.getElementById('inputFoto').click()">
                                            <i class="bi bi-camera-fill"></i>
                                        </button>
                                    </div>
                                    <input type="file" id="inputFoto" name="foto" accept="image/*"
                                        class="d-none">
                                    <div id="btnQuitarFotoContainer" class="mt-2 d-none">
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="quitarFoto()">
                                            <i class="bi bi-x-lg"></i> Quitar
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-info-circle"></i> Máx 2MB
                                    </small>
                                </div>

                                <div class="col-md-8">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-uppercase">Nombre <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="nombre" id="nombre" class="form-control"
                                                required maxlength="100" placeholder="Ej. Juan">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-uppercase">Apellido <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="apellido" id="apellido" class="form-control"
                                                required maxlength="100" placeholder="Ej. Pérez López">
                                        </div>

                                        <div class="col-md-6 d-none">
                                            <label class="form-label fw-bold small text-uppercase">CURP / DNI</label>
                                            <input type="text" name="curp_dni" id="curp_dni" class="form-control"
                                                maxlength="50" placeholder="Opcional">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-uppercase">Puesto <span
                                                    class="text-danger">*</span></label>
                                            <select name="rol_id" id="rol_id" class="form-select" required
                                                onchange="actualizarPuestoCargo(this)">
                                                <option value="">Selecciona un puesto</option>
                                                @foreach ($rolesOperativos as $rol)
                                                    <option value="{{ $rol->id }}"
                                                        data-nombre="{{ $rol->nombre }}">
                                                        {{ $rol->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="puesto_cargo" id="puesto_cargo">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-uppercase">Empresa <span
                                                    class="text-danger">*</span></label>
                                            <select name="empresa_id" id="empresa_id" class="form-select" required>
                                                <option value="">Selecciona una empresa</option>
                                                @foreach ($empresas as $emp)
                                                    <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-uppercase">Obra</label>
                                            <select name="obra_id" id="obra_id" class="form-select">
                                                <option value="">Sin obra asignada</option>
                                                @foreach ($obras as $obra)
                                                    <option value="{{ $obra->id }}">{{ $obra->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6 d-none" id="campoEstatus">
                                            <label class="form-label fw-bold small text-uppercase">Estatus</label>
                                            <select name="estatus" id="estatus" class="form-select">
                                                <option value="activo">Activo</option>
                                                <option value="inactivo">Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-cabosync-primary" id="btnGuardar">
                                <i class="bi bi-check-lg"></i> <span id="btnGuardarTexto">Registrar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL CROPPER --}}
        <div class="modal fade" id="modalCropper" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-cabosync-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-crop"></i> Ajustar Foto
                        </h5>
                        <button type="button" class="btn-close btn-close-white" onclick="cancelarRecorte()"></button>
                    </div>

                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle"></i>
                            Arrastra la imagen y usa la rueda del mouse o los botones para hacer zoom.
                        </p>

                        <div class="cropper-wrapper">
                            <cropper-canvas background id="cropperCanvas" style="height: 450px; width: 100%;">
                                <cropper-image id="cropperImage" rotatable scalable translatable></cropper-image>
                                <cropper-shade hidden></cropper-shade>
                                <cropper-handle action="select" plain></cropper-handle>
                                <cropper-selection id="cropperSelection" initial-coverage="0.9" aspect-ratio="1" movable
                                    resizable>
                                    <cropper-grid role="grid" bordered covered></cropper-grid>
                                    <cropper-crosshair centered></cropper-crosshair>
                                    <cropper-handle action="move"
                                        theme-color="rgba(255, 255, 255, 0.35)"></cropper-handle>
                                    <cropper-handle action="n-resize"></cropper-handle>
                                    <cropper-handle action="e-resize"></cropper-handle>
                                    <cropper-handle action="s-resize"></cropper-handle>
                                    <cropper-handle action="w-resize"></cropper-handle>
                                    <cropper-handle action="ne-resize"></cropper-handle>
                                    <cropper-handle action="nw-resize"></cropper-handle>
                                    <cropper-handle action="se-resize"></cropper-handle>
                                    <cropper-handle action="sw-resize"></cropper-handle>
                                </cropper-selection>
                            </cropper-canvas>
                        </div>

                        <div class="cropper-controls">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperZoom(0.1)">
                                <i class="bi bi-zoom-in"></i> Acercar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperZoom(-0.1)">
                                <i class="bi bi-zoom-out"></i> Alejar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperRotate()">
                                <i class="bi bi-arrow-repeat"></i> Rotar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperReset()">
                                <i class="bi bi-arrow-clockwise"></i> Resetear
                            </button>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="cancelarRecorte()">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-cabosync-primary" onclick="aplicarRecorte()">
                            <i class="bi bi-check-lg"></i> Aplicar Recorte
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL IMPORTAR (4 PASOS) --}}
        <div class="modal fade" id="modalImportar" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-cabosync-secondary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-upload"></i> Importar Empleados
                            <span class="badge bg-light text-dark ms-2" id="importPasoBadge">Paso 1 de 4</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white"
                            onclick="cerrarModalImportar()"></button>
                    </div>

                    <div class="modal-body" id="modalImportarBody">

                        {{-- ============================================
                     PASO 1: SELECCIONAR FORMATO
                     ============================================ --}}
                        <div id="importPaso1">
                            <div class="text-center mb-4">
                                <h5 class="text-cabosync-primary mb-1">¿En qué formato quieres importar?</h5>
                                <small class="text-muted">Selecciona un formato y descarga la plantilla</small>
                            </div>

                            <div class="row g-3 mb-4">
                                {{-- CSV --}}
                                <div class="col-md-4">
                                    <div class="import-formato-card" data-formato="csv">
                                        <i class="bi bi-filetype-csv"></i>
                                        <h6>CSV</h6>
                                        <small>Archivo separado por comas</small>
                                    </div>
                                </div>

                                {{-- Excel --}}
                                <div class="col-md-4">
                                    <div class="import-formato-card" data-formato="xlsx">
                                        <i class="bi bi-file-earmark-spreadsheet"></i>
                                        <h6>Excel</h6>
                                        <small>Archivo .xlsx</small>
                                    </div>
                                </div>

                                {{-- SQL --}}
                                <div class="col-md-4">
                                    <div class="import-formato-card" data-formato="sql">
                                        <i class="bi bi-database"></i>
                                        <h6>SQL</h6>
                                        <small>Archivo .sql con INSERTs</small>
                                    </div>
                                </div>
                            </div>
                            {{-- Botones de descarga de plantillas --}}
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
                                                class="btn btn-sm btn-outline-cabosync-primary btn-descargar-plantilla"
                                                data-tipo="csv">
                                                <i class="bi bi-filetype-csv"></i> Descargar CSV
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-cabosync-primary btn-descargar-plantilla"
                                                data-tipo="excel">
                                                <i class="bi bi-file-earmark-spreadsheet"></i> Descargar Excel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info small mb-0">
                                <i class="bi bi-lightbulb-fill"></i>
                                <strong>Recomendación:</strong> Descarga la plantilla de ejemplo, llénala con tus datos y
                                súbela.
                                Los campos <strong>NOMBRE</strong>, <strong>APELLIDO</strong>, <strong>PUESTO_CARGO</strong>
                                y
                                <strong>EMPRESA_RFC</strong> son obligatorios.
                            </div>
                        </div>

                        {{-- ============================================
                     PASO 2: PREVISUALIZAR
                     ============================================ --}}
                        <div id="importPaso2" class="d-none">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div>
                                    <h5 class="text-cabosync-primary mb-1" id="importNombreArchivo">-</h5>
                                    <small class="text-muted">
                                        <span id="importTotalFilas">0</span> filas detectadas
                                    </small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="volverPaso1()">
                                    <i class="bi bi-arrow-left"></i> Volver
                                </button>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex gap-2 mb-3 flex-wrap">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-cabosync-primary btn-descargar-plantilla"
                                        data-tipo="csv">
                                        <i class="bi bi-filetype-csv"></i> Descargar CSV
                                    </button>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-cabosync-primary btn-descargar-plantilla"
                                        data-tipo="excel">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> Descargar Excel
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle" id="importTablaPreview">
                                    <thead class="table-light sticky-top">
                                        <tr id="importPreviewHead"></tr>
                                    </thead>
                                    <tbody id="importPreviewBody"></tbody>
                                </table>
                            </div>

                            <div class="text-muted small mt-2">
                                <i class="bi bi-info-circle"></i>
                                Mostrando las primeras 10 filas de <span id="importTotalFilas2">0</span>.
                            </div>

                            <div id="importSqlPreview" class="d-none mt-3">
                                <div class="alert alert-info small mb-3">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <strong>Modo SQL:</strong> Los campos que no estén en el archivo SQL
                                    (empresa, obra, estatus) se asignarán automáticamente con valores por defecto
                                    según tu usuario.
                                </div>

                                <label class="form-label small fw-bold">Previsualización del SQL (solo lectura)</label>
                                <textarea id="importSqlText" class="form-control font-monospace" rows="12" readonly></textarea>
                            </div>
                        </div>

                        {{-- ============================================
                     PASO 3: PROGRESO
                     ============================================ --}}
                        <div id="importPaso3" class="d-none text-center py-4">
                            <div class="mb-4">
                                <img src="{{ asset('img/logo-cabosync.png') }}" alt="CaboSync" style="height: 80px;"
                                    class="mb-3">
                            </div>

                            <h5 class="text-cabosync-primary mb-3">Importando empleados...</h5>

                            <div class="progress mb-3" style="height: 25px;">
                                <div id="importProgressBar"
                                    class="progress-bar progress-bar-striped progress-bar-animated bg-cabosync-primary"
                                    role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0"
                                    aria-valuemax="100">
                                    <span id="importProgressPct">0%</span>
                                </div>
                            </div>

                            <p class="text-muted mb-4" id="importProgresoMensaje">
                                Preparando...
                            </p>

                            <div class="row g-3 justify-content-center">
                                <div class="col-md-2">
                                    <div class="import-contador import-contador--ok">
                                        <div class="import-contador__num" id="contadorCargados">0</div>
                                        <div class="import-contador__label">✅ Cargados</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="import-contador import-contador--warn">
                                        <div class="import-contador__num" id="contadorActualizados">0</div>
                                        <div class="import-contador__label">⚠️ Actualizados</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="import-contador import-contador--info">
                                        <div class="import-contador__num" id="contadorOmitidos">0</div>
                                        <div class="import-contador__label">⏭️ Omitidos</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="import-contador import-contador--error">
                                        <div class="import-contador__num" id="contadorErrores">0</div>
                                        <div class="import-contador__label">❌ Errores</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================
                     PASO 4: RESULTADO
                     ============================================ --}}
                        <div id="importPaso4" class="d-none">
                            <div class="text-center mb-4">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                                <h4 class="mt-2 text-cabosync-primary">¡Importación completada!</h4>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="import-contador import-contador--ok">
                                        <div class="import-contador__num" id="resumenCargados">0</div>
                                        <div class="import-contador__label">✅ Cargados</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="import-contador import-contador--warn">
                                        <div class="import-contador__num" id="resumenActualizados">0</div>
                                        <div class="import-contador__label">⚠️ Actualizados</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="import-contador import-contador--info">
                                        <div class="import-contador__num" id="resumenOmitidos">0</div>
                                        <div class="import-contador__label">⏭️ Omitidos</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="import-contador import-contador--error">
                                        <div class="import-contador__num" id="resumenErrores">0</div>
                                        <div class="import-contador__label">❌ Errores</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tabs de detalle --}}
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabCargados"
                                        type="button">
                                        ✅ Cargados <span class="badge bg-success" id="badgeCargados">0</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabActualizados"
                                        type="button">
                                        ⚠️ Actualizados <span class="badge bg-warning text-dark"
                                            id="badgeActualizados">0</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabOmitidos"
                                        type="button">
                                        ⏭️ Omitidos <span class="badge bg-info text-dark" id="badgeOmitidos">0</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabErrores"
                                        type="button">
                                        ❌ Errores <span class="badge bg-danger" id="badgeErrores">0</span>
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="tabCargados">
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nombre</th>
                                                    <th>Apellido</th>
                                                    <th>ID</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaCargados"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tabActualizados">
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nombre</th>
                                                    <th>Apellido</th>
                                                    <th>Cambios</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaActualizados"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tabOmitidos">
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nombre</th>
                                                    <th>Apellido</th>
                                                    <th>Motivo</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaOmitidos"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tabErrores">
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>Fila</th>
                                                    <th>Campo</th>
                                                    <th>Motivo</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaErrores"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        {{-- Botones según el paso --}}
                        <div id="importFooterPaso1" class="d-flex justify-content-end w-100">
                            <button type="button" class="btn btn-secondary" onclick="cerrarModalImportar()">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </button>
                        </div>

                        <div id="importFooterPaso2" class="d-none d-flex justify-content-between w-100">
                            <button type="button" class="btn btn-secondary" onclick="volverPaso1()">
                                <i class="bi bi-arrow-left"></i> Volver
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" onclick="cerrarModalImportar()">
                                    Cancelar
                                </button>
                                <button type="button" class="btn btn-cabosync-primary" onclick="iniciarImportacion()">
                                    <i class="bi bi-upload"></i> Importar <span id="importCantidadBtn"></span>
                                </button>
                            </div>
                        </div>

                        <div id="importFooterPaso3" class="d-none justify-content-end w-100">
                            <button type="button" class="btn btn-secondary" disabled>
                                <i class="bi bi-hourglass-split"></i> Procesando...
                            </button>
                        </div>

                        <div id="importFooterPaso4" class="d-none d-flex justify-content-between w-100">
                            <button type="button" class="btn btn-outline-secondary"
                                onclick="volverAlPaso1DesdeResultado()">
                                <i class="bi bi-arrow-clockwise"></i> Importar otro
                            </button>
                            <button type="button" class="btn btn-cabosync-primary" onclick="cerrarYRecargar()">
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
        // ============================================
        // CONFIGURACIÓN
        // ============================================
        const EMPLEADOS_CONFIG = {
            rutas: {
                index: "{{ route('empleados.index') }}",
                store: "{{ route('empleados.store') }}",
                card: "{{ url('empleados') }}",
            },
            usuario: {
                esContratista: {{ auth()->user()->esContratista() ? 'true' : 'false' }},
                empresaId: {{ auth()->user()->empresa_id ?? 'null' }},
            }
        };

        // ============================================
        // VARIABLES GLOBALES DEL CROPPER
        // ============================================
        let archivoFotoRecortado = null;

        // ============================================
        // FOTO: ABRIR CROPPER AL SELECCIONAR
        // ============================================
        document.getElementById('inputFoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                CaboSyncAlert.error('La imagen no debe superar 2MB');
                this.value = '';
                return;
            }

            if (!file.type.startsWith('image/')) {
                CaboSyncAlert.error('El archivo debe ser una imagen');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (ev) => {
                abrirModalCropper(ev.target.result);
            };
            reader.readAsDataURL(file);
        });

        // ============================================
        // ABRIR MODAL CROPPER
        // ============================================
        function abrirModalCropper(srcImagen) {
            const modalEl = document.getElementById('modalCropper');
            const modal = new bootstrap.Modal(modalEl);

            modalEl.addEventListener('shown.bs.modal', function onShown() {
                modalEl.removeEventListener('shown.bs.modal', onShown);

                // Esperar a que los Web Components estén definidos
                if (window.customElements && customElements.get('cropper-canvas')) {
                    const $image = document.getElementById('cropperImage');
                    const $selection = document.getElementById('cropperSelection');

                    // Resetear selección
                    if ($selection) $selection.$reset();

                    // Cargar nueva imagen
                    if ($image) {
                        $image.$ready(() => {
                            $image.$center('contain');
                        });
                        $image.src = srcImagen;
                    }
                } else {
                    console.error('Cropper.js v2 no está cargado correctamente');
                    CaboSyncAlert.error('Error al cargar el editor de imágenes');
                }
            });

            modal.show();
        }

        // ============================================
        // CONTROLES DEL CROPPER
        // ============================================
        function cropperZoom(ratio) {
            const $selection = document.getElementById('cropperSelection');
            if (!$selection) return;

            // En v2, el zoom se hace sobre el área de selección
            if (ratio > 0) {
                $selection.$zoom(0.1);
            } else {
                $selection.$zoom(-0.1);
            }
        }

        function cropperRotate() {
            const $image = document.getElementById('cropperImage');
            if (!$image) return;
            $image.$rotate('90deg');
        }

        function cropperReset() {
            const $image = document.getElementById('cropperImage');
            const $selection = document.getElementById('cropperSelection');
            if ($image) $image.$resetTransform();
            if ($image) $image.$center('contain');
            if ($selection) $selection.$reset();
        }

        // ============================================
        // CANCELAR RECORTE
        // ============================================
        function cancelarRecorte() {
            const modalEl = document.getElementById('modalCropper');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            document.getElementById('inputFoto').value = '';
        }

        // ============================================
        // APLICAR RECORTE
        // ============================================
        async function aplicarRecorte() {
            const $selection = document.getElementById('cropperSelection');
            if (!$selection) {
                CaboSyncAlert.error('Error: No se puede procesar la imagen');
                return;
            }

            try {
                // En v2, $toCanvas() devuelve una promesa con el canvas recortado
                const canvas = await $selection.$toCanvas({
                    width: 400,
                    height: 400,
                });

                canvas.toBlob((blob) => {
                    if (!blob) {
                        CaboSyncAlert.error('Error al procesar la imagen');
                        return;
                    }

                    const nombreArchivo = 'foto_' + Date.now() + '.jpg';
                    archivoFotoRecortado = new File([blob], nombreArchivo, {
                        type: 'image/jpeg'
                    });

                    // Actualizar preview
                    const urlRecortada = canvas.toDataURL('image/jpeg', 0.9);
                    document.getElementById('previewFoto').src = urlRecortada;
                    document.getElementById('btnQuitarFotoContainer').classList.remove('d-none');

                    // Cerrar modal
                    const modalEl = document.getElementById('modalCropper');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    CaboSyncAlert.success('Foto recortada correctamente');
                }, 'image/jpeg', 0.9);
            } catch (error) {
                console.error('Error al recortar:', error);
                CaboSyncAlert.error('Error al procesar el recorte');
            }
        }

        // ============================================
        // QUITAR FOTO
        // ============================================
        function quitarFoto() {
            document.getElementById('inputFoto').value = '';
            document.getElementById('previewFoto').src = '{{ asset('img/avatar-default.png') }}';
            document.getElementById('btnQuitarFotoContainer').classList.add('d-none');
            archivoFotoRecortado = null;
        }

        // ============================================
        // ACTUALIZAR PUESTO AL CAMBIAR ROL
        // ============================================
        function actualizarPuestoCargo(select) {
            const option = select.options[select.selectedIndex];
            document.getElementById('puesto_cargo').value = option.dataset.nombre || '';
        }

        // ============================================
        // RESET FORM PARA CREAR
        // ============================================
        function resetFormEmpleado() {
            const form = document.getElementById('formEmpleado');
            form.reset();
            document.getElementById('empleadoId').value = '';
            document.getElementById('modalEmpleadoTitulo').innerHTML =
                '<i class="bi bi-person-plus-fill"></i> Registrar Nuevo Empleado';
            document.getElementById('btnGuardarTexto').textContent = 'Registrar';
            document.getElementById('campoEstatus').classList.add('d-none');
            document.getElementById('previewFoto').src = '{{ asset('img/avatar-default.png') }}';
            document.getElementById('btnQuitarFotoContainer').classList.add('d-none');
            document.getElementById('puesto_cargo').value = '';
            archivoFotoRecortado = null;

            if (EMPLEADOS_CONFIG.usuario.esContratista && EMPLEADOS_CONFIG.usuario.empresaId) {
                document.getElementById('empresa_id').value = EMPLEADOS_CONFIG.usuario.empresaId;
            }
        }

        // ============================================
        // EDITAR EMPLEADO
        // ============================================
        async function editarEmpleado(id) {
            try {
                const {
                    data
                } = await axios.get(`${EMPLEADOS_CONFIG.rutas.card}/${id}`);

                archivoFotoRecortado = null;

                document.getElementById('empleadoId').value = data.id;
                document.getElementById('nombre').value = data.nombre;
                document.getElementById('apellido').value = data.apellido;
                document.getElementById('curp_dni').value = data.curp_dni || '';
                document.getElementById('empresa_id').value = data.empresa_id;
                document.getElementById('obra_id').value = data.obra_id || '';
                document.getElementById('rol_id').value = data.rol_id || '';
                document.getElementById('puesto_cargo').value = data.puesto_cargo;
                document.getElementById('estatus').value = data.estatus;

                document.getElementById('modalEmpleadoTitulo').innerHTML =
                    '<i class="bi bi-pencil-square"></i> Editar Empleado';
                document.getElementById('btnGuardarTexto').textContent = 'Actualizar';
                document.getElementById('campoEstatus').classList.remove('d-none');

                document.getElementById('previewFoto').src = data.foto_url || '{{ asset('img/avatar-default.png') }}';
                document.getElementById('btnQuitarFotoContainer').classList.toggle('d-none', !data.foto);

                new bootstrap.Modal(document.getElementById('modalEmpleado')).show();
            } catch (error) {
                // El interceptor de axios ya muestra el error
            }
        }

        // ============================================
        // GUARDAR (CREAR O EDITAR)
        // ============================================
        document.getElementById('formEmpleado').addEventListener('submit', async function(e) {
            e.preventDefault();

            const id = document.getElementById('empleadoId').value;
            const esEdicion = !!id;

            const btnGuardar = document.getElementById('btnGuardar');
            const btnTexto = document.getElementById('btnGuardarTexto');
            btnGuardar.disabled = true;
            btnTexto.textContent = 'Guardando...';

            const formData = new FormData(this);

            // Reemplazar foto con la versión recortada
            if (archivoFotoRecortado) {
                formData.delete('foto');
                formData.append('foto', archivoFotoRecortado);
            }

            if (esEdicion) {
                formData.append('_method', 'PUT');
            }

            const url = esEdicion ?
                `${EMPLEADOS_CONFIG.rutas.card}/${id}` :
                EMPLEADOS_CONFIG.rutas.store;

            try {
                const {
                    data
                } = await axios.post(url, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                bootstrap.Modal.getInstance(document.getElementById('modalEmpleado')).hide();

                if (esEdicion) {
                    await actualizarCard(id);
                    CaboSyncAlert.success(data.mensaje);
                } else {
                    CaboSyncAlert.success('Empleado registrado. Redirigiendo...');
                    setTimeout(() => {
                        window.location.href = EMPLEADOS_CONFIG.rutas.index + '?page=1';
                    }, 1000);
                }
            } catch (error) {
                if (error.response && error.response.data && error.response.data.errors) {
                    const errores = Object.values(error.response.data.errors)[0];
                    if (errores && errores[0]) {
                        CaboSyncAlert.error(errores[0]);
                    }
                }
            } finally {
                btnGuardar.disabled = false;
                btnTexto.textContent = esEdicion ? 'Actualizar' : 'Registrar';
            }
        });

        // ============================================
        // CONFIRMAR Y DESACTIVAR EMPLEADO
        // ============================================
        document.addEventListener('click', async function(e) {
            const btnEditar = e.target.closest('.btn-editar-empleado');
            if (btnEditar) {
                e.preventDefault();
                editarEmpleado(btnEditar.dataset.id);
                return;
            }

            const btnDesactivar = e.target.closest('.btn-desactivar-empleado');
            if (btnDesactivar) {
                e.preventDefault();
                const id = btnDesactivar.dataset.id;
                const nombre = btnDesactivar.dataset.nombre;

                const confirmado = await CaboSyncAlert.confirm(
                    '¿Desactivar empleado?',
                    `Estás a punto de desactivar a ${nombre}. El empleado quedará inactivo pero sus registros se conservarán.`, {
                        confirmText: 'Sí, desactivar',
                        cancelText: 'Cancelar',
                        confirmButtonColor: '#dc3545',
                    }
                );

                if (!confirmado) return;

                try {
                    const {
                        data
                    } = await axios.delete(`${EMPLEADOS_CONFIG.rutas.card}/${id}`);
                    CaboSyncAlert.success(data.mensaje);

                    const filtroEstatus = "{{ request('estatus', '') }}";
                    if (filtroEstatus === '' || filtroEstatus === 'activo') {
                        document.querySelector(`.empleado-card[data-empleado-id="${id}"]`)?.remove();

                        const contador = document.getElementById('contadorEmpleados');
                        const total = document.getElementById('totalRegistros');
                        if (contador) contador.textContent = Math.max(0, parseInt(contador.textContent) - 1);
                        if (total) total.textContent = Math.max(0, parseInt(total.textContent) - 1);
                    } else {
                        await actualizarCard(id);
                    }
                } catch (error) {
                    // Interceptor maneja el error
                }
                return;
            }
        });

        // ============================================
        // ACTUALIZAR CARD EN VIVO
        // ============================================
        async function actualizarCard(id) {
            try {
                const {
                    data: html
                } = await axios.get(`${EMPLEADOS_CONFIG.rutas.card}/${id}/card`, {
                    headers: {
                        'Accept': 'text/html'
                    }
                });

                const cardActual = document.querySelector(`.empleado-card[data-empleado-id="${id}"]`);
                if (cardActual) {
                    const temp = document.createElement('div');
                    temp.innerHTML = html.trim();
                    const nuevaCard = temp.firstElementChild;
                    cardActual.replaceWith(nuevaCard);
                }
            } catch (error) {
                console.error('Error al actualizar card:', error);
            }
        }
        // ============================================
        // IMPORTAR EMPLEADOS - MODAL CON 4 PASOS
        // ============================================
        let importFormatoSeleccionado = null;
        let importArchivoSeleccionado = null;
        let importFilasPreview = [];

        function abrirModalImportar() {
            reiniciarModalImportar();
            new bootstrap.Modal(document.getElementById('modalImportar')).show();
        }

        function reiniciarModalImportar() {
            // 1. Resetear variables
            importFormatoSeleccionado = null;
            importArchivoSeleccionado = null;
            importFilasPreview = [];

            // 2. Resetear UI
            document.querySelectorAll('.import-formato-card').forEach(el => el.classList.remove('active'));
            mostrarPasoImportar(1);

            // 3. ✅ Limpiar CUALQUIER residuo del modal anterior
            const modalEl = document.getElementById('modalImportar');
            if (modalEl) {
                // Quitar estilos inline de visibilidad
                modalEl.style.display = 'none';
                modalEl.style.removeProperty('display');
                modalEl.style.removeProperty('padding-right');

                // Quitar clases de Bootstrap
                modalEl.classList.remove('show');
                modalEl.classList.remove('fade');

                // Resetear atributos
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.removeAttribute('aria-modal');
                modalEl.removeAttribute('role');
            }

            // 4. ✅ Eliminar backdrops residuales
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

            // 5. ✅ Limpiar el body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        }

        function cerrarModalImportar() {
            cerrarModalImportarTotal();
        }

        function cerrarYRecargar() {
            cerrarModalImportar();
            window.location.reload();
        }

        function mostrarPasoImportar(paso) {
            // Ocultar todos los pasos
            ['importPaso1', 'importPaso2', 'importPaso3', 'importPaso4'].forEach(id => {
                document.getElementById(id)?.classList.add('d-none');
            });

            // Mostrar el paso actual
            document.getElementById('importPaso' + paso)?.classList.remove('d-none');

            // Actualizar badge
            document.getElementById('importPasoBadge').textContent = `Paso ${paso} de 4`;

            // Mostrar/ocultar footers
            ['importFooterPaso1', 'importFooterPaso2', 'importFooterPaso3', 'importFooterPaso4'].forEach(id => {
                document.getElementById(id)?.classList.add('d-none');
                document.getElementById(id)?.classList.remove('d-flex');
            });
            document.getElementById('importFooterPaso' + paso)?.classList.remove('d-none');
            document.getElementById('importFooterPaso' + paso)?.classList.add('d-flex');
        }

        function volverPaso1() {
            importArchivoSeleccionado = null;
            importFilasPreview = [];
            mostrarPasoImportar(1);
        }

        // ============================================
        // SELECCIÓN DE FORMATO
        // ============================================
        document.querySelectorAll('.import-formato-card').forEach(card => {
            card.addEventListener('click', async () => {
                document.querySelectorAll('.import-formato-card').forEach(el => el.classList.remove(
                    'active'));
                card.classList.add('active');

                importFormatoSeleccionado = card.dataset.formato;

                // Abrir selector de archivo
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = importFormatoSeleccionado === 'sql' ?
                    '.sql' :
                    importFormatoSeleccionado === 'csv' ?
                    '.csv,.txt' :
                    '.xlsx,.xls';

                input.onchange = async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;

                    importArchivoSeleccionado = file;

                    if (importFormatoSeleccionado === 'sql') {
                        await previsualizarSQL(file);
                    } else {
                        await previsualizarCSVExcel(file);
                    }
                };

                input.click();
            });
        });

        // ============================================
        // PREVISUALIZAR CSV/EXCEL
        // ============================================
        // ============================================
        // PREVISUALIZAR CSV / EXCEL
        // ============================================
        async function previsualizarCSVExcel(file) {
            try {
                const extension = file.name.split('.').pop().toLowerCase();
                let filas = [];
                let encabezados = [];

                if (extension === 'xlsx' || extension === 'xls') {
                    // ==========================================
                    // EXCEL: usar SheetJS para parsear
                    // ==========================================
                    const arrayBuffer = await file.arrayBuffer();
                    const workbook = XLSX.read(arrayBuffer, {
                        type: 'array'
                    });

                    // Tomar la primera hoja
                    const primeraHoja = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[primeraHoja];

                    // Convertir a JSON (array de objetos)
                    const datos = XLSX.utils.sheet_to_json(worksheet, {
                        header: 1, // Array de arrays
                        defval: '', // Valores vacíos por defecto
                        blankrows: false, // Ignorar filas vacías
                        raw: false, // Convertir todo a strings
                    });

                    if (datos.length < 2) {
                        CaboSyncAlert.error('El archivo está vacío o no tiene datos');
                        return;
                    }

                    // Primera fila = encabezados
                    encabezados = datos[0].map(h => String(h).trim());

                    // Resto = filas de datos
                    for (let i = 1; i < datos.length; i++) {
                        const fila = {};
                        encabezados.forEach((enc, idx) => {
                            fila[enc] = datos[i][idx] !== undefined ? String(datos[i][idx]).trim() : '';
                        });
                        filas.push(fila);
                    }

                } else {
                    // ==========================================
                    // CSV: lectura manual
                    // ==========================================
                    const texto = await file.text();
                    const lineas = texto.split(/\r?\n/).filter(l => l.trim());

                    if (lineas.length < 2) {
                        CaboSyncAlert.error('El archivo está vacío o no tiene datos');
                        return;
                    }

                    const separador = lineas[0].includes(';') ? ';' : ',';
                    encabezados = lineas[0].split(separador).map(h =>
                        h.trim().replace(/^["']|["']$/g, '')
                    );

                    for (let i = 1; i < lineas.length; i++) {
                        const valores = lineas[i].split(separador).map(v =>
                            v.trim().replace(/^["']|["']$/g, '')
                        );
                        if (valores.length === encabezados.length) {
                            const fila = {};
                            encabezados.forEach((enc, idx) => {
                                fila[enc] = valores[idx];
                            });
                            filas.push(fila);
                        }
                    }
                }

                // Guardar en variable global
                importFilasPreview = filas;

                // Actualizar info
                document.getElementById('importNombreArchivo').textContent = file.name;
                document.getElementById('importTotalFilas').textContent = filas.length;
                document.getElementById('importTotalFilas2').textContent = filas.length;
                document.getElementById('importCantidadBtn').textContent = `${filas.length} registros`;

                // ==========================================
                // GENERAR TABLA PREVIEW
                // ==========================================
                const thead = document.getElementById('importPreviewHead');
                const tbody = document.getElementById('importPreviewBody');

                thead.innerHTML = '';
                // Columna "#"
                const thNum = document.createElement('th');
                thNum.textContent = '#';
                thead.appendChild(thNum);

                encabezados.forEach(enc => {
                    const th = document.createElement('th');
                    th.textContent = enc;
                    thead.appendChild(th);
                });

                tbody.innerHTML = '';
                filas.slice(0, 10).forEach((fila, index) => {
                    const tr = document.createElement('tr');

                    // Número de fila
                    const tdNum = document.createElement('td');
                    tdNum.innerHTML = `<strong>${index + 2}</strong>`;
                    tr.appendChild(tdNum);

                    // Datos
                    encabezados.forEach(enc => {
                        const td = document.createElement('td');
                        td.className = 'import-table-truncate';
                        td.textContent = fila[enc] || '';
                        td.title = fila[enc] || ''; // Tooltip con el valor completo
                        tr.appendChild(td);
                    });

                    tbody.appendChild(tr);
                });

                // Ocultar SQL preview
                document.getElementById('importSqlPreview')?.classList.add('d-none');

                // Mostrar la tabla (por si estaba oculta de una carga SQL previa)
                document.getElementById('importTablaPreview')?.classList.remove('d-none');

                // Ir al paso 2
                mostrarPasoImportar(2);

            } catch (error) {
                console.error('Error al leer archivo:', error);
                CaboSyncAlert.error('Error al leer el archivo: ' + error.message);
            }
        }
        // ============================================
        // PREVISUALIZAR SQL
        // ============================================
        // ============================================
        // PREVISUALIZAR SQL
        // ============================================
        // ============================================
        // PREVISUALIZAR SQL
        // ============================================
        async function previsualizarSQL(file) {
            try {
                const texto = await file.text();

                // Contar INSERTs y filas
                const insertMatches = texto.match(/INSERT\s+INTO\s+`?empleados`?/gi) || [];
                const totalInserts = insertMatches.length;

                // Contar filas dentro de los VALUES
                let totalFilas = 0;
                const regexValues = /VALUES\s*([\s\S]*?);/gi;
                let match;
                while ((match = regexValues.exec(texto)) !== null) {
                    const bloque = match[1];
                    const filas = bloque.match(/\([^)]*\)/g) || [];
                    totalFilas += filas.length;
                }

                // Mostrar preview limitado (primeras 60 líneas)
                const todasLasLineas = texto.split('\n');
                const preview = todasLasLineas.slice(0, 60).join('\n');
                const hayMas = todasLasLineas.length > 60;

                document.getElementById('importSqlText').value =
                    preview + (hayMas ? `\n\n... (${todasLasLineas.length - 60} líneas más que se procesarán)` : '');
                document.getElementById('importSqlPreview').classList.remove('d-none');

                document.getElementById('importNombreArchivo').textContent = file.name;
                document.getElementById('importTotalFilas').textContent = totalFilas || totalInserts;
                document.getElementById('importTotalFilas2').textContent =
                    `${totalFilas} filas en ${totalInserts} INSERTs`;
                document.getElementById('importCantidadBtn').textContent = `${totalFilas} registros`;

                // Ocultar tabla preview
                document.getElementById('importTablaPreview')?.classList.add('d-none');

                // Info útil
                if (totalFilas === 0) {
                    CaboSyncAlert.warning('No se detectaron INSERTs de empleados en el archivo');
                }

                mostrarPasoImportar(2);

            } catch (error) {
                console.error(error);
                CaboSyncAlert.error('Error al leer el archivo SQL');
            }
        }
        // ============================================
        // INICIAR IMPORTACIÓN
        // ============================================
        async function iniciarImportacion() {
            if (!importArchivoSeleccionado) {
                CaboSyncAlert.error('No hay archivo seleccionado');
                return;
            }

            // Ir al paso 3
            mostrarPasoImportar(3);

            // Preparar datos
            const formData = new FormData();
            if (importFormatoSeleccionado === 'sql') {
                formData.append('archivo_sql', importArchivoSeleccionado);
            } else {
                formData.append('archivo', importArchivoSeleccionado);
            }

            // Simular progreso (para UX)
            const totalFilas = importFormatoSeleccionado === 'sql' ?
                0 :
                importFilasPreview.length;

            simularProgreso(totalFilas);

            // Enviar al servidor
            const url = importFormatoSeleccionado === 'sql' ?
                "{{ route('empleados.importarSQL') }}" :
                "{{ route('empleados.importar') }}";

            try {
                const {
                    data
                } = await axios.post(url, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                detenerSimulacion();

                if (data.success) {
                    mostrarResultadoImportar(data);
                } else {
                    CaboSyncAlert.error(data.error || 'Error al importar');
                    mostrarPasoImportar(2);
                }

            } catch (error) {
                detenerSimulacion();
                mostrarPasoImportar(2);
                // El interceptor maneja el error
            }
        }

        // ============================================
        // SIMULAR PROGRESO EN VIVO
        // ============================================
        let simulacionInterval = null;
        let simulacionIndex = 0;

        function simularProgreso(total) {
            simulacionIndex = 0;
            const barra = document.getElementById('importProgressBar');
            const pct = document.getElementById('importProgressPct');
            const mensaje = document.getElementById('importProgresoMensaje');

            // Contadores en vivo
            let cargados = 0;
            let actualizados = 0;
            let omitidos = 0;
            let errores = 0;

            const nombresSimulados = importFilasPreview.map(f =>
                `${f.nombre || f.NOMBRE || ''} ${f.apellido || f.APELLIDO || ''}`.trim()
            ).filter(n => n);

            simulacionInterval = setInterval(() => {
                if (total > 0 && simulacionIndex >= total) return;

                simulacionIndex++;

                // Actualizar barra
                const porcentaje = total > 0 ?
                    Math.min(95, Math.round((simulacionIndex / total) * 100)) :
                    Math.min(95, simulacionIndex * 3);

                barra.style.width = porcentaje + '%';
                barra.setAttribute('aria-valuenow', porcentaje);
                pct.textContent = porcentaje + '%';

                // Actualizar mensaje
                const nombreActual = nombresSimulados[simulacionIndex - 1] || 'procesando...';
                if (total > 0) {
                    mensaje.innerHTML =
                        `Insertando registro <strong>${simulacionIndex}</strong> de <strong>${total}</strong> — <em>${nombreActual}</em>`;
                } else {
                    mensaje.innerHTML = `Procesando SQL... registro ${simulacionIndex}`;
                }

                // Actualizar contadores (simulación aleatoria realista)
                if (Math.random() > 0.1) cargados++;
                else if (Math.random() > 0.5) actualizados++;
                else omitidos++;

                document.getElementById('contadorCargados').textContent = cargados;
                document.getElementById('contadorActualizados').textContent = actualizados;
                document.getElementById('contadorOmitidos').textContent = omitidos;
                document.getElementById('contadorErrores').textContent = errores;

            }, 80); // cada 80ms
        }

        function detenerSimulacion() {
            if (simulacionInterval) {
                clearInterval(simulacionInterval);
                simulacionInterval = null;
            }
        }

        // ============================================
        // MOSTRAR RESULTADO
        // ============================================
        function mostrarResultadoImportar(data) {
            // Rellenar resumen
            document.getElementById('resumenCargados').textContent = data.resumen.cargados;
            document.getElementById('resumenActualizados').textContent = data.resumen.actualizados;
            document.getElementById('resumenOmitidos').textContent = data.resumen.omitidos;
            document.getElementById('resumenErrores').textContent = data.resumen.errores;

            // Badges
            document.getElementById('badgeCargados').textContent = data.resumen.cargados;
            document.getElementById('badgeActualizados').textContent = data.resumen.actualizados;
            document.getElementById('badgeOmitidos').textContent = data.resumen.omitidos;
            document.getElementById('badgeErrores').textContent = data.resumen.errores;

            // Tabla cargados
            const tablaCargados = document.getElementById('tablaCargados');
            tablaCargados.innerHTML = '';
            if (data.cargados.length === 0) {
                tablaCargados.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Sin registros</td></tr>';
            } else {
                data.cargados.forEach(c => {
                    tablaCargados.innerHTML += `
                <tr>
                    <td>${c.fila}</td>
                    <td>${c.nombre}</td>
                    <td>${c.apellido}</td>
                    <td><span class="badge bg-success">#${c.id}</span></td>
                </tr>
            `;
                });
            }

            // Tabla actualizados
            const tablaActualizados = document.getElementById('tablaActualizados');
            tablaActualizados.innerHTML = '';
            if (data.actualizados.length === 0) {
                tablaActualizados.innerHTML =
                    '<tr><td colspan="4" class="text-center text-muted py-3">Sin registros</td></tr>';
            } else {
                data.actualizados.forEach(a => {
                    let cambiosHtml = '';
                    Object.entries(a.cambios).forEach(([campo, diff]) => {
                        cambiosHtml +=
                            `<div class="mb-1"><strong>${campo}:</strong> <span class="cambio-campo__antes">${diff.antes ?? 'vacío'}</span> → <span class="cambio-campo__despues">${diff.despues ?? 'vacío'}</span></div>`;
                    });
                    tablaActualizados.innerHTML += `
                <tr>
                    <td>${a.fila}</td>
                    <td>${a.nombre}</td>
                    <td>${a.apellido}</td>
                    <td>${cambiosHtml}</td>
                </tr>
            `;
                });
            }

            // Tabla omitidos
            const tablaOmitidos = document.getElementById('tablaOmitidos');
            tablaOmitidos.innerHTML = '';
            if (data.omitidos.length === 0) {
                tablaOmitidos.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Sin registros</td></tr>';
            } else {
                data.omitidos.forEach(o => {
                    tablaOmitidos.innerHTML += `
                <tr>
                    <td>${o.fila}</td>
                    <td>${o.nombre}</td>
                    <td>${o.apellido}</td>
                    <td><span class="text-muted">${o.motivo}</span></td>
                </tr>
            `;
                });
            }

            // Tabla errores
            const tablaErrores = document.getElementById('tablaErrores');
            tablaErrores.innerHTML = '';
            if (data.errores.length === 0) {
                tablaErrores.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Sin errores 🎉</td></tr>';
            } else {
                data.errores.forEach(e => {
                    tablaErrores.innerHTML += `
                <tr>
                    <td>${e.fila}</td>
                    <td><span class="badge bg-danger">${e.campo}</span></td>
                    <td>${e.motivo}</td>
                </tr>
            `;
                });
            }

            // Ir al paso 4
            mostrarPasoImportar(4);

            // Toast resumen
            if (data.resumen.cargados > 0 || data.resumen.actualizados > 0) {
                CaboSyncAlert.success(
                    `Importación completada: ${data.resumen.cargados} cargados, ${data.resumen.actualizados} actualizados`
                );
            }
        }
        // ============================================
        // DESCARGAR PLANTILLAS
        // ============================================
        // ============================================
        // DESCARGAR PLANTILLAS (con cierre inmediato del modal)
        // ============================================
        // ============================================
        // DESCARGAR PLANTILLAS (versión robusta)
        // ============================================
        // ============================================
        // DESCARGAR PLANTILLAS (vía iframe oculto)
        // ============================================
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-descargar-plantilla');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const tipo = btn.dataset.tipo;
            const url = tipo === 'csv' ?
                "{{ route('empleados.plantilla') }}" :
                "{{ route('empleados.plantillaExcel') }}";

            // 1. Cerrar el modal INMEDIATAMENTE
            cerrarModalImportarTotal();

            // 2. Crear iframe oculto para descargar
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            document.body.appendChild(iframe);

            // 3. Eliminar iframe después de 5 segundos
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 5000);

            // 4. Toast de confirmación
            setTimeout(() => {
                CaboSyncAlert.success('Plantilla descargada');
            }, 300);
        });
        // ============================================
        // CIERRE FORZADO DEL MODAL + LIMPIEZA TOTAL
        // ============================================
        function cerrarModalImportarTotal() {
            const modalEl = document.getElementById('modalImportar');
            if (!modalEl) return;

            // 1. Ocultar con Bootstrap
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }

            // 2. Forzar limpieza después de la animación de Bootstrap
            setTimeout(() => {
                // ✅ OCULTAR EL MODAL (esto es lo correcto)
                modalEl.style.display = 'none';

                // ✅ Eliminar TODAS las clases de Bootstrap Modal
                modalEl.classList.remove('show');
                modalEl.classList.remove('fade');

                // ✅ Atributos de accesibilidad
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.removeAttribute('aria-modal');
                modalEl.removeAttribute('role');

                // ✅ Eliminar backdrops residuales
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

                // ✅ Limpiar body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');

            }, 350);
        }
        // ============================================
        // VOLVER AL PASO 1 DESDE EL RESULTADO (PASO 4)
        // ============================================
        function volverAlPaso1DesdeResultado() {
            // 1. Resetear variables
            importFormatoSeleccionado = null;
            importArchivoSeleccionado = null;
            importFilasPreview = [];

            // 2. Resetear UI interna
            document.querySelectorAll('.import-formato-card').forEach(el => el.classList.remove('active'));

            // 3. ✅ Resetear el scroll del modal al top
            const modalBody = document.getElementById('modalImportarBody');
            if (modalBody) {
                modalBody.scrollTop = 0;
            }

            // 4. ✅ Volver al Paso 1 SIN cerrar el modal
            mostrarPasoImportar(1);
        }
    </script>
@endpush
