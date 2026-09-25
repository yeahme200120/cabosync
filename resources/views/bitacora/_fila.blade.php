@php
    $colorTipo = match($registro->tipo_accion) {
        'insert' => 'success',
        'update' => 'warning',
        'delete' => 'danger',
        'login'  => 'info',
        'logout' => 'secondary',
        'view'   => 'light',
        default  => 'secondary',
    };
    $textTipo = in_array($colorTipo, ['light', 'warning']) ? 'text-dark' : '';
@endphp

<tr class="fila-bitacora {{ $registro->es_publico ? 'fila-bitacora--publica' : '' }}"
    data-bitacora-id="{{ $registro->id }}"
    style="cursor: pointer;">

    <td>
        <div class="small">{{ optional($registro->created_at)->format('d/m/Y') }}</div>
        <div class="text-muted" style="font-size: 0.72rem;">
            {{ optional($registro->created_at)->format('H:i:s') }}
        </div>
    </td>

    <td>
        @if ($registro->usuario)
            <div class="small fw-semibold">{{ $registro->usuario->nombre }}</div>
            <div class="text-muted" style="font-size: 0.72rem;">{{ $registro->usuario->email }}</div>
        @else
            <span class="badge bg-warning text-dark">
                <i class="bi bi-globe"></i> Público
            </span>
        @endif
    </td>

    <td>
        <span class="badge bg-{{ $colorTipo }} {{ $textTipo }}">
            {{ $registro->accion }}
        </span>
        @if ($registro->es_publico)
            <span class="badge bg-warning text-dark ms-1" title="Acción pública">
                <i class="bi bi-globe"></i>
            </span>
        @endif
    </td>

    <td class="small">
        {{ \Illuminate\Support\Str::limit($registro->descripcion, 120) }}
    </td>

    <td class="small text-muted">
        @if ($registro->modelo_afectado)
            {{ class_basename($registro->modelo_afectado) }}
            @if ($registro->modelo_id)
                <span class="text-muted">#{{ $registro->modelo_id }}</span>
            @endif
        @else
            -
        @endif
    </td>

    <td class="small text-muted">
        @if ($registro->direccion_ip)
            <div><i class="bi bi-hdd-network"></i> {{ $registro->direccion_ip }}</div>
        @endif
        @if ($registro->latitud && $registro->longitud)
            <a href="https://www.google.com/maps?q={{ $registro->latitud }},{{ $registro->longitud }}"
               target="_blank"
               class="text-decoration-none"
               onclick="event.stopPropagation();"
               title="Ver en Google Maps">
                <i class="bi bi-geo-alt-fill text-cabosync-secondary"></i>
                Ver mapa
            </a>
        @endif
    </td>

    <td class="text-end">
        <i class="bi bi-chevron-right text-muted"></i>
    </td>
</tr>