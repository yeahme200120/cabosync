{{-- ============================================
     CARD DE EMPLEADO (REUTILIZABLE)
     Estructura:
     - .empleado-card__top: foto + info
     - .empleado-card__acciones: botones
     ============================================ --}}
<div class="empleado-card" data-empleado-id="{{ $empleado->id }}">

    {{-- Fila superior: foto + info --}}
    <div class="empleado-card__top">
        <img src="{{ $empleado->foto_url }}"
             alt="{{ $empleado->nombre_completo }}"
             class="empleado-card__foto"
             onerror="this.src='{{ asset('img/avatar-default.png') }}'">

        <div class="empleado-card__info">
            <h6 class="empleado-card__nombre">{{ $empleado->nombre_completo }}</h6>
            <p class="empleado-card__curp">{{ $empleado->curp_dni ?? 'Sin CURP' }}</p>

            <div class="empleado-card__badges">
                <span class="badge bg-cabosync-primary">{{ $empleado->puesto_cargo }}</span>
                @if($empleado->obra)
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-building"></i> {{ $empleado->obra->nombre }}
                    </span>
                @endif
            </div>

            <div class="empleado-card__estatus">
                <span class="empleado-card__dot {{ $empleado->estatus === 'activo' ? 'empleado-card__dot--activo' : 'empleado-card__dot--inactivo' }}"></span>
                <span class="{{ $empleado->estatus === 'activo' ? 'text-success' : 'text-muted' }}">
                    {{ $empleado->estatus === 'activo' ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    <div class="empleado-card__acciones">
        <button class="btn btn-cabosync-primary btn-editar-empleado"
                data-id="{{ $empleado->id }}"
                title="Editar">
            <i class="bi bi-pencil"></i>
            <span class="btn-text">Editar</span>
        </button>
        <button class="btn btn-danger btn-desactivar-empleado"
                data-id="{{ $empleado->id }}"
                data-nombre="{{ $empleado->nombre_completo }}"
                title="Desactivar">
            <i class="bi bi-trash"></i>
            <span class="btn-text">Eliminar</span>
        </button>
    </div>
</div>