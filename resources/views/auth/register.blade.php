<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - CaboSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .register-logo { text-align: center; margin-bottom: 1rem; }
        .register-logo img { width: 100px; height: 100px; }
        .register-subtitle {
            text-align: center;
            color: var(--cabosync-text-muted);
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

    <div class="recover-card" style="max-width: 520px;">
        <div class="register-logo">
            <img src="{{ asset('img/logo-cabosync.png') }}" alt="CaboSync">
        </div>

        <h1 class="recover-title">CABO<span class="accent">SYNC</span></h1>

        <p class="register-subtitle">
            <i class="bi bi-person-plus-fill"></i>
            Crear nueva cuenta
        </p>

        @if($errors->any())
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label-cabosync">Nombre</label>
                <input id="name" type="text"
                    class="form-control form-control-cabosync @error('name') is-invalid @enderror"
                    name="name" value="{{ old('name') }}" required autofocus>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label-cabosync">Correo Electrónico</label>
                <input id="email" type="email"
                    class="form-control form-control-cabosync @error('email') is-invalid @enderror"
                    name="email" value="{{ old('email') }}" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label-cabosync">Contraseña</label>
                <input id="password" type="password"
                    class="form-control form-control-cabosync @error('password') is-invalid @enderror"
                    name="password" required>
            </div>

            <div class="mb-3">
                <label for="password-confirm" class="form-label-cabosync">Confirmar Contraseña</label>
                <input id="password-confirm" type="password"
                    class="form-control form-control-cabosync"
                    name="password_confirmation" required>
            </div>

            <button type="submit" class="btn-cabosync-login">
                REGISTRARME
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>