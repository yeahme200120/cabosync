<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - CaboSync</title>

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

    <div class="recover-card">
        <div class="recover-logo">
            <img src="{{ asset('img/logo-cabosync.png') }}" alt="CaboSync">
        </div>

        <h1 class="recover-title">CABO<span class="accent">SYNC</span></h1>

        <p class="recover-subtitle">
            <i class="bi bi-envelope-fill"></i>
            Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña
        </p>

        @if (session('status'))
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label-cabosync">Correo Electrónico</label>
                <input type="email" name="email" id="email"
                    class="form-control form-control-cabosync"
                    value="{{ old('email') }}" required autofocus
                    placeholder="correo@ejemplo.com">
            </div>

            <button type="submit" class="btn-cabosync-login">
                ENVIAR ENLACE
            </button>
        </form>

        <div class="back-link">
            <a href="{{ route('login') }}">
                <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>