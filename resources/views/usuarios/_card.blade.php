@php
    $authUser = auth()->user();
    $esAdmin = $authUser->esAdministrador();
    $puedeImpersonar = $esAdmin && $usuario->id !== $authUser->id;
    $puedeEliminar = $authUser->id !== $usuario->id;
    $esMiPropioUsuario = $authUser->id === $usuario->id;
    $estaEnLinea = in_array($usuario->id, $usuariosEnLinea ?? []);
@endphp

<div class="usuario-card" data-usuario-id="{{ $usuario->id }}">
    <div class="usuario-card__top">
        <div class="usuario-card__avatar">
            <i class="bi bi-person-fill"></i>
            @if ($estaEnLinea)
                <span class="usuario-card__avatar-dot" title="En línea"></span>
            @endif
        </div>

        <div class="usuario-card__info">
            <h6 class="usuario-card__nombre">{{ $usuario->nombre }}</h6>
            <p class="usuario-card__email">
                <i class="bi bi-envelope"></i> {{ $usuario->email }}
            </p>

            <div class="usuario-card__badges">
                <span class="badge bg-cabosync-primary">{{ $usuario->rol?->nombre ?? 'Sin rol' }}</span>
                @if ($usuario->empresa)
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-building"></i> {{ $usuario->empresa->nombre }}
                    </span>
                @else
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-building"></i> Sin empresa
                    </span>
                @endif
            </div>

            <div class="usuario-card__estatus">
                <span class="usuario-card__dot {{ $usuario->estatus === 'activo' ? 'usuario-card__dot--activo' : 'usuario-card__dot--inactivo' }}"></span>
                <span class="{{ $usuario->estatus === 'activo' ? 'text-success' : 'text-muted' }}">
                    {{ $usuario->estatus === 'activo' ? 'Activo' : 'Inactivo' }}
                </span>

                @if ($estaEnLinea)
                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle ms-1"
                          title="Tiene una sesión activa en el sistema">
                        <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                        En línea
                    </span>
                @endif

                @if ($usuario->puede_conciliar)
                    <span class="badge bg-info text-dark" title="Puede conciliar">
                        <i class="bi bi-shuffle"></i> Conciliador
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="usuario-card__acciones">
        <button class="btn btn-outline-info btn-ver-historial"
                data-id="{{ $usuario->id }}"
                data-nombre="{{ $usuario->nombre }}"
                title="Ver historial de acciones">
            <i class="bi bi-clock-history"></i>
            <span class="btn-text">Historial</span>
        </button>

        <button class="btn btn-cabosync-primary btn-editar-usuario"
                data-id="{{ $usuario->id }}"
                title="Editar">
            <i class="bi bi-pencil"></i>
            <span class="btn-text">Editar</span>
        </button>

        <button class="btn btn-warning btn-reset-password"
                data-id="{{ $usuario->id }}"
                data-nombre="{{ $usuario->nombre }}"
                title="Resetear contraseña">
            <i class="bi bi-key"></i>
            <span class="btn-text">Reset</span>
        </button>

        @if ($puedeImpersonar)
            <button class="btn btn-outline-secondary btn-impersonar"
                    data-id="{{ $usuario->id }}"
                    data-nombre="{{ $usuario->nombre }}"
                    title="Impersonar">
                <i class="bi bi-person-badge"></i>
                <span class="btn-text">Impersonar</span>
            </button>
        @endif

        @if ($usuario->estatus === 'activo' && !$esMiPropioUsuario)
            <button class="btn btn-outline-danger btn-desactivar-usuario"
                    data-id="{{ $usuario->id }}"
                    data-nombre="{{ $usuario->nombre }}"
                    title="Desactivar">
                <i class="bi bi-pause-circle"></i>
                <span class="btn-text">Desactivar</span>
            </button>
        @endif

        @if ($puedeEliminar)
            <button class="btn btn-danger btn-eliminar-usuario"
                    data-id="{{ $usuario->id }}"
                    data-nombre="{{ $usuario->nombre }}"
                    title="Eliminar permanentemente">
                <i class="bi bi-trash"></i>
                <span class="btn-text">Eliminar</span>
            </button>
        @endif
    </div>
</div>