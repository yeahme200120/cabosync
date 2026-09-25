<ul class="nav flex-column">

    {{-- DASHBOARD --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>

    {{-- EMPLEADOS --}}
    @if (auth()->user()->tienePermiso('empleados.crear') || auth()->user()->tienePermiso('empleados.expediente'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('empleados.*') ? 'active' : '' }}"
                href="{{ route('empleados.index') }}">
                <i class="bi bi-people-fill"></i> Empleados
            </a>
        </li>
    @endif

    {{-- PASE DE LISTA --}}
    @if (auth()->user()->tienePermiso('asistencia.tomar'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('asistencia.*') ? 'active' : '' }}"
                href="{{ route('asistencia.index') }}">
                <i class="bi bi-clipboard-check"></i> Pase de Lista
            </a>
        </li>
    @endif

    {{-- CONCILIACIÓN --}}
    @php
        $puedeVerConciliacion = false;
        if (auth()->check()) {
            $u = auth()->user();
            // Admin, Contratista siempre pueden ver
            if ($u->esAdministrador() || $u->esContratista()) {
                $puedeVerConciliacion = true;
            }
            // Jefe de Obra con puede_conciliar activado
            elseif ($u->esJefeObra() && $u->puede_conciliar) {
                $puedeVerConciliacion = true;
            }
        }
    @endphp

    @if ($puedeVerConciliacion)
        <li class="nav-item">
            <a href="{{ route('conciliacion.index') }}"
                class="nav-link {{ request()->routeIs('conciliacion.*') ? 'active' : '' }}">
                <i class="bi bi-shuffle"></i> Conciliación
            </a>
        </li>
    @endif

    {{-- HORAS EXTRAS --}}
    @if (auth()->user()->tienePermiso('horas_extras.ver'))
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="bi bi-clock-history"></i> Horas Extras
            </a>
        </li>
    @endif

    {{-- REPORTES --}}
    @if (auth()->user()->tienePermiso('reportes.ver'))
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="bi bi-file-earmark-bar-graph"></i> Reportes
            </a>
        </li>
    @endif

    {{-- EMPRESAS --}}
    @if (auth()->user()->tienePermiso('empresas.ver'))
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="bi bi-building"></i> Empresas
            </a>
        </li>
    @endif

    {{-- SEPARADOR --}}
    <li class="nav-item mt-3">
        <small class="text-muted px-3 text-uppercase fw-bold" style="font-size: 0.7rem;">Sistema</small>
    </li>

    {{-- USUARIOS (solo admin) --}}
    @if (auth()->user()->tienePermiso('usuarios.ver'))
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="bi bi-person-badge"></i> Usuarios
            </a>
        </li>
    @endif

    {{-- BITÁCORA --}}
    @if (auth()->user()->tienePermiso('bitacora.ver'))
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="bi bi-journal-text"></i> Bitácora
            </a>
        </li>
    @endif

    {{-- CONFIGURACIÓN --}}
    @if (auth()->user()->tienePermiso('config.editar'))
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="bi bi-gear-fill"></i> Configuración
            </a>
        </li>
    @endif

    {{-- TÉRMINOS Y PRIVACIDAD (siempre visible) --}}
    <li class="nav-item mt-3">
        <small class="text-muted px-3 text-uppercase fw-bold" style="font-size: 0.7rem;">Legal</small>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#modalAvisoPrivacidad">
            <i class="bi bi-shield-check"></i> Aviso de Privacidad
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos">
            <i class="bi bi-file-earmark-text"></i> Términos y Condiciones
        </a>
    </li>

    {{-- PIE --}}
    <li class="nav-item mt-4 px-3">
        <small class="text-muted">
            <i class="bi bi-c-circle"></i> {{ date('Y') }} ID SOFTWARE HOUSE
        </small>
    </li>

</ul>
