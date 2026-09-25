@php
    $authUser = auth()->user();
    $esAdmin = $authUser->esAdministrador();
    $puedeEditar = $esAdmin || ($authUser->esContratista() && $obra->empresa_id === $authUser->empresa_id);
    $puedeEliminar = $puedeEditar;
    $puedePausar = $puedeEditar && $obra->estatus === 'activa';
    $puedeActivar = $puedeEditar && $obra->estatus === 'pausada';
    $puedeTerminar = $puedeEditar && $obra->estatus !== 'terminada';
@endphp

<div class="obra-card" data-obra-id="{{ $obra->id }}">
    <div class="obra-card__top">
        <div class="obra-card__icon obra-card__icon--{{ $obra->estatus }}">
            <i class="bi bi-hammer"></i>
        </div>

        <div class="obra-card__info">
            <h6 class="obra-card__nombre">{{ $obra->nombre }}</h6>

            @if ($obra->codigo)
                <p class="obra-card__codigo">
                    <i class="bi bi-hash"></i> {{ $obra->codigo }}
                </p>
            @endif

            @if ($obra->ubicacion)
                <p class="obra-card__ubicacion">
                    <i class="bi bi-geo-alt"></i> {{ $obra->ubicacion }}
                </p>
            @endif

            <div class="obra-card__badges">
                <span class="badge bg-cabosync-primary">
                    <i class="bi bi-building"></i> {{ $obra->empresa?->nombre ?? 'Sin empresa' }}
                </span>
                <span class="badge bg-{{ $obra->badgeEstatus() }}">
                    {{ $obra->textoEstatus() }}
                </span>
            </div>

            @if ($obra->fecha_inicio || $obra->fecha_fin)
                <div class="obra-card__fechas">
                    @if ($obra->fecha_inicio)
                        <small><i class="bi bi-calendar-check"></i> {{ $obra->fecha_inicio->format('d/m/Y') }}</small>
                    @endif
                    @if ($obra->fecha_fin)
                        <small><i class="bi bi-calendar-x"></i> {{ $obra->fecha_fin->format('d/m/Y') }}</small>
                    @endif
                </div>
            @endif

            @if ($obra->creadoPor)
                <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">
                    <i class="bi bi-person-plus"></i> Creada por {{ $obra->creadoPor->nombre }}
                </small>
            @endif
        </div>
    </div>

    <div class="obra-card__acciones">
        @if ($puedeEditar)
            <button class="btn btn-cabosync-primary btn-editar-obra"
                    data-id="{{ $obra->id }}"
                    title="Editar">
                <i class="bi bi-pencil"></i>
                <span class="btn-text">Editar</span>
            </button>
        @endif

        @if ($puedePausar)
            <button class="btn btn-warning btn-pausar-obra"
                    data-id="{{ $obra->id }}"
                    data-nombre="{{ $obra->nombre }}"
                    title="Pausar">
                <i class="bi bi-pause-circle"></i>
                <span class="btn-text">Pausar</span>
            </button>
        @endif

        @if ($puedeActivar)
            <button class="btn btn-success btn-activar-obra"
                    data-id="{{ $obra->id }}"
                    data-nombre="{{ $obra->nombre }}"
                    title="Activar">
                <i class="bi bi-play-circle"></i>
                <span class="btn-text">Activar</span>
            </button>
        @endif

        @if ($puedeTerminar)
            <button class="btn btn-outline-secondary btn-terminar-obra"
                    data-id="{{ $obra->id }}"
                    data-nombre="{{ $obra->nombre }}"
                    title="Marcar como terminada">
                <i class="bi bi-check2-square"></i>
                <span class="btn-text">Terminar</span>
            </button>
        @endif

        @if ($puedeEliminar)
            <button class="btn btn-danger btn-eliminar-obra"
                    data-id="{{ $obra->id }}"
                    data-nombre="{{ $obra->nombre }}"
                    title="Eliminar permanentemente">
                <i class="bi bi-trash"></i>
                <span class="btn-text">Eliminar</span>
            </button>
        @endif
    </div>
</div>