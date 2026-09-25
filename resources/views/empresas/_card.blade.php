@php
    $authUser = auth()->user();
    $esAdmin = $authUser->esAdministrador();
    $esContratista = $authUser->esContratista();
    $esMatriz = $empresa->tipo === 'matriz';
    $puedeEditar = $esAdmin || ($esContratista && !$esMatriz);
    $puedeEliminar = $esAdmin && !$esMatriz;
    $puedeDesactivar = $puedeEditar && !$esMatriz && $empresa->estatus === 'activo';
@endphp

<div class="empresa-card" data-empresa-id="{{ $empresa->id }}">
    <div class="empresa-card__top">
        <div class="empresa-card__icon {{ $esMatriz ? 'empresa-card__icon--matriz' : 'empresa-card__icon--externa' }}">
            <i class="bi bi-{{ $esMatriz ? 'building-fill-gear' : 'building' }}"></i>
        </div>

        <div class="empresa-card__info">
            <h6 class="empresa-card__nombre">{{ $empresa->nombre }}</h6>
            <p class="empresa-card__rfc">
                <i class="bi bi-card-text"></i>
                {{ $empresa->rfc ?: 'Sin RFC' }}
            </p>

            <div class="empresa-card__badges">
                @if ($esMatriz)
                    <span class="badge bg-cabosync-primary">
                        <i class="bi bi-star-fill"></i> Matriz
                    </span>
                @else
                    <span class="badge bg-cabosync-secondary">
                        <i class="bi bi-building"></i> Externa
                    </span>
                @endif
            </div>

            <div class="empresa-card__estatus">
                <span class="empresa-card__dot {{ $empresa->estatus === 'activo' ? 'empresa-card__dot--activo' : 'empresa-card__dot--inactivo' }}"></span>
                <span class="{{ $empresa->estatus === 'activo' ? 'text-success' : 'text-muted' }}">
                    {{ $empresa->estatus === 'activo' ? 'Activa' : 'Inactiva' }}
                </span>
            </div>

            @if ($empresa->creadoPor)
                <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">
                    <i class="bi bi-person-plus"></i> Creada por {{ $empresa->creadoPor->nombre }}
                </small>
            @endif
        </div>
    </div>

    <div class="empresa-card__acciones">
        @if ($puedeEditar)
            <button class="btn btn-cabosync-primary btn-editar-empresa"
                    data-id="{{ $empresa->id }}"
                    title="Editar">
                <i class="bi bi-pencil"></i>
                <span class="btn-text">Editar</span>
            </button>
        @endif

        @if ($puedeDesactivar)
            <button class="btn btn-outline-danger btn-desactivar-empresa"
                    data-id="{{ $empresa->id }}"
                    data-nombre="{{ $empresa->nombre }}"
                    title="Desactivar">
                <i class="bi bi-pause-circle"></i>
                <span class="btn-text">Desactivar</span>
            </button>
        @endif

        @if ($puedeEliminar)
            <button class="btn btn-danger btn-eliminar-empresa"
                    data-id="{{ $empresa->id }}"
                    data-nombre="{{ $empresa->nombre }}"
                    title="Eliminar permanentemente">
                <i class="bi bi-trash"></i>
                <span class="btn-text">Eliminar</span>
            </button>
        @endif
    </div>
</div>