<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - CaboSync</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

    <div class="reset-card">
        <div class="reset-logo">
            <img src="{{ asset('img/logo-cabosync.png') }}" alt="CaboSync">
        </div>

        <h1 class="reset-title">CABO<span class="accent">SYNC</span></h1>

        <p class="reset-subtitle">
            <i class="bi bi-shield-lock-fill"></i>
            Ingresa tu nueva contraseña
        </p>

        @if ($errors->any())
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-3">
                <label for="email" class="form-label-cabosync">Correo Electrónico</label>
                <input type="email" name="email" id="email"
                    class="form-control form-control-cabosync"
                    value="{{ $email ?? old('email') }}" required readonly>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label-cabosync">Nueva Contraseña</label>
                <input type="password" name="password" id="password"
                    class="form-control form-control-cabosync"
                    required autofocus placeholder="••••••••">
                <div class="password-hint">
                    <i class="bi bi-info-circle"></i>
                    Mínimo 8 caracteres, 1 mayúscula y 1 número
                </div>
            </div>

            <div class="mb-3">
                <label for="password-confirm" class="form-label-cabosync">Confirmar Contraseña</label>
                <input type="password" name="password_confirmation" id="password-confirm"
                    class="form-control form-control-cabosync"
                    required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-cabosync-login">
                ACTUALIZAR CONTRASEÑA
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>