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
    @if (auth()->user()->esAdministrador() || auth()->user()->esContratista())
        <li class="nav-item">
            <a href="{{ route('horas-extras.index') }}"
                class="nav-link {{ request()->routeIs('horas-extras.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Horas Extras
            </a>
        </li>
    @endif

    {{-- REPORTES --}}
    @if (auth()->user()->tienePermiso('reportes.ver'))
        <li class="nav-item">
            <a href="{{ route('reportes.index') }}"
                class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i> Reportes
            </a>
        </li>
    @endif

    {{-- EMPRESAS --}}
    @if (auth()->user()->tienePermiso('empresas.ver'))
        <li class="nav-item">
            <a href="{{ route('empresas.index') }}"
                class="nav-link {{ request()->routeIs('empresas.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i>
                <span>Empresas</span>
            </a>
        </li>
    @endif
    {{-- EMPRESAS --}}
    @if (auth()->user()->tienePermiso('empresas.ver'))
        <li class="nav-item">
            <a href="{{ route('obras.index') }}" class="nav-link {{ request()->routeIs('obras.*') ? 'active' : '' }}">
                <i class="bi bi-hammer"></i>
                <span>Obras</span>
            </a>
        </li>
    @endif

    {{-- SEPARADOR --}}
    <li class="nav-item mt-3">
        <small class="text-muted px-3 text-uppercase fw-bold" style="font-size: 0.7rem;">Sistema</small>
    </li>

    {{-- USUARIOS --}}
    @php
        $puedeVerUsuarios = false;
        if (auth()->check()) {
            $u = auth()->user();
            if ($u->esAdministrador() || $u->esContratista()) {
                $puedeVerUsuarios = true;
            }
        }
    @endphp

    @if ($puedeVerUsuarios)
        <li class="nav-item">
            <a href="{{ route('usuarios.index') }}"
                class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> Usuarios
            </a>
        </li>
    @endif

    {{-- BITÁCORA --}}
    @if (auth()->user()->esAdministrador() || auth()->user()->esContratista())
        <a href="{{ route('bitacora.index') }}"
            class="nav-link {{ request()->routeIs('bitacora.*') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i>
            <span>Bitácora</span>
        </a>
    @endif

    {{-- CONFIGURACIÓN --}}
    @if (auth()->user()->tienePermiso('config.editar'))
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="bi bi-gear-fill"></i> Configuración
            </a>
        </li>
    @endif
    {{-- CONFIGURACIÓN LEGAL (Admin) --}}
    @if (auth()->user()->esAdministrador())
        <li class="nav-item">
            <a href="{{ route('legal.admin.index') }}"
                class="nav-link {{ request()->routeIs('legal.admin.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> Configuración Legal
            </a>
        </li>
    @endif

    {{-- TÉRMINOS Y PRIVACIDAD --}}
    <li class="nav-item mt-3">
        <small class="text-muted px-3 text-uppercase fw-bold" style="font-size: 0.7rem;">Legal</small>
    </li>
    <li class="nav-item">
        <a href="{{ route('legal_publico.aviso') }}" target="_blank" class="nav-link">
            <i class="bi bi-shield-check"></i> Aviso de Privacidad
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('legal_publico.terminos') }}" target="_blank" class="nav-link">
            <i class="bi bi-file-earmark-text"></i> Términos y Condiciones
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('legal.misConsentimientos') }}"
            class="nav-link {{ request()->routeIs('legal.misConsentimientos') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i> Mis Consentimientos
        </a>
    </li>

    {{-- PIE --}}
    <li class="nav-item mt-4 px-3">
        <small class="text-muted">
            <i class="bi bi-c-circle"></i> {{ date('Y') }} ID SOFTWARE HOUSE
        </small>
    </li>

</ul>
