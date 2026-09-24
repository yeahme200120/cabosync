<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - CaboSync</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <style>
        /* ============================================
           LAYOUT GENERAL
           ============================================ */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, #f4f6f9 0%, #e8edf3 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1100px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
            align-items: stretch;
        }

        /* ============================================
           CARD BASE (ambas cards comparten estilo)
           ============================================ */
        .login-card,
        .branding-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(30, 81, 128, 0.08),
                        0 2px 8px rgba(30, 81, 128, 0.04);
            padding: 3rem 2.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover,
        .branding-card:hover {
            box-shadow: 0 14px 48px rgba(30, 81, 128, 0.12),
                        0 4px 12px rgba(30, 81, 128, 0.06);
        }

        /* ============================================
           CARD IZQUIERDA - FORMULARIO
           ============================================ */
        .login-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 1.25rem;
        }
        .login-logo img {
            display: inline-block;
            max-height: 90px;
            height: auto;
            width: auto;
        }

        .login-title {
            text-align: center;
            font-weight: 800;
            color: #1E5180;
            font-size: 1.5rem;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }
        .login-title .accent {
            color: #F28C28;
        }

        .login-welcome {
            text-align: center;
            color: #6c757d;
            font-size: 1rem;
            letter-spacing: 2px;
            margin-bottom: 2.5rem;
            font-weight: 500;
        }

        /* ============================================
           FORMULARIO
           ============================================ */
        .form-label-cabosync {
            font-size: 0.75rem;
            font-weight: 700;
            color: #1E5180;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .form-control-cabosync {
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.85rem 1rem;
            font-size: 1rem;
            background-color: #f8f9fb;
            transition: all 0.2s ease;
        }
        .form-control-cabosync:focus {
            border-color: #1E5180;
            background-color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(30, 81, 128, 0.12);
        }

        .btn-cabosync-login {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 2.5px;
            width: 100%;
            margin-top: 1.5rem;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .btn-cabosync-login:hover {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            color: #F28C28;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }
        .btn-cabosync-login:active {
            transform: translateY(0);
        }

        .login-footer-link {
            text-align: center;
            margin-top: 1.75rem;
            font-size: 0.85rem;
        }
        .login-footer-link a {
            color: #6c757d;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .login-footer-link a:hover {
            color: #F28C28;
        }

        /* ============================================
           CARD DERECHA - BRANDING
           ============================================ */
        .branding-card {
            background: linear-gradient(135deg, #1E5180 0%, #163f63 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* Patrón decorativo sutil */
        .branding-card::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(242, 140, 40, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .branding-card::after {
            content: "";
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .branding-content {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 100%;
        }

        .branding-card h1 {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: 4px;
            margin-bottom: 0.75rem;
            color: #ffffff;
        }
        .branding-card h1 .accent {
            color: #F28C28;
        }

        .branding-card > .branding-content > p {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 2.5rem;
            font-weight: 400;
        }

        /* Separador decorativo */
        .branding-divider {
            width: 60px;
            height: 3px;
            background-color: #F28C28;
            border-radius: 3px;
            margin: 0 auto 2.5rem auto;
        }

        /* ============================================
           FEATURES
           ============================================ */
        .branding-card .features {
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
            text-align: left;
            max-width: 320px;
            margin: 0 auto;
            width: 100%;
        }

        .branding-card .features .feature {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.95rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        .branding-card .features .feature:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }

        .branding-card .features .feature i {
            color: #F28C28;
            font-size: 1.4rem;
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(242, 140, 40, 0.15);
            border-radius: 8px;
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 900px) {
            body {
                padding: 1.5rem 1rem;
                align-items: flex-start;
            }

            .login-wrapper {
                grid-template-columns: 1fr;
                gap: 0;
                max-width: 460px;
            }

            /* ⛔ OCULTAR LA CARD DE BRANDING EN MÓVIL */
            .branding-card {
                display: none;
            }

            .login-card {
                padding: 2.5rem 1.75rem;
            }

            .login-logo img {
                max-height: 80px;
            }

            .login-title {
                font-size: 1.35rem;
            }

            .login-welcome {
                font-size: 0.95rem;
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 400px) {
            .login-card {
                padding: 2rem 1.25rem;
            }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">

        {{-- ==========================================
             CARD IZQUIERDA: FORMULARIO DE LOGIN
             ========================================== --}}
        <div class="login-card">

            <div class="login-logo">
                <img src="{{ asset('img/logo-cabosync.png') }}" alt="CaboSync">
            </div>

            <h1 class="login-title">
                CABO<span class="accent">SYNC</span>
            </h1>

            <p class="login-welcome">BIENVENIDO</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label-cabosync">USUARIO</label>
                    <input type="email" name="email" id="email"
                        class="form-control form-control-cabosync"
                        value="{{ old('email') }}" required autofocus
                        placeholder="correo@ejemplo.com">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label-cabosync">CONTRASEÑA</label>
                    <input type="password" name="password" id="password"
                        class="form-control form-control-cabosync"
                        required placeholder="••••••••">
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small text-muted" for="remember">
                        Recordarme
                    </label>
                </div>

                <button type="submit" class="btn-cabosync-login">
                    ACCESAR
                </button>
            </form>

            <div class="login-footer-link">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>

        </div>

        {{-- ==========================================
             CARD DERECHA: BRANDING (oculta en móvil)
             ========================================== --}}
        <div class="branding-card">
            <div class="branding-content">

                <h1>CABO<span class="accent">SYNC</span></h1>

                <p>Sistema de Gestión de Asistencia y Personal</p>

                <div class="branding-divider"></div>

                <div class="features">
                    <div class="feature">
                        <i class="bi bi-clipboard-check-fill"></i>
                        <span>Pase de lista en 1 clic</span>
                    </div>
                    <div class="feature">
                        <i class="bi bi-shuffle"></i>
                        <span>Conciliación automática de discrepancias</span>
                    </div>
                    <div class="feature">
                        <i class="bi bi-clock-history"></i>
                        <span>Control de horas extras y penalizaciones</span>
                    </div>
                    <div class="feature">
                        <i class="bi bi-shield-check"></i>
                        <span>Cumplimiento LFPDPPP México 2025</span>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>