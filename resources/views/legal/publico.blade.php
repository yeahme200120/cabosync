<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }} - CaboSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: system-ui, -apple-system, sans-serif; }
        .legal-header {
            background: linear-gradient(135deg, #1E5180, #2a6dad);
            color: #fff;
            padding: 2rem 1rem;
            text-align: center;
        }
        .legal-header h1 { font-size: 1.75rem; margin: 0 0 0.5rem; }
        .legal-version { opacity: 0.85; font-size: 0.9rem; }
        .legal-container {
            max-width: 900px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            padding: 2.5rem;
        }
        .legal-content { line-height: 1.7; color: #333; }
        .legal-content h3 { color: #1E5180; margin-top: 1.5rem; font-size: 1.4rem; }
        .legal-content h4 { color: #1E5180; margin-top: 1.25rem; font-size: 1.1rem; }
        .legal-content ul { padding-left: 1.5rem; }
        .legal-footer { text-align: center; padding: 2rem 1rem; color: #6c757d; font-size: 0.85rem; }
        .btn-back { background: #1E5180; color: #fff; border: none; }
        .btn-back:hover { background: #F28C28; color: #fff; }
    </style>
</head>
<body>
    <div class="legal-header">
        <i class="bi bi-shield-check" style="font-size: 3rem;"></i>
        <h1>{{ $titulo }}</h1>
        <div class="legal-version">
            <span class="badge bg-light text-dark">Versión {{ $version }}</span>
        </div>
    </div>

    <div class="legal-container">
        <div class="legal-content">
            {!! $texto !!}
        </div>

        <div class="text-center mt-4">
            <a href="{{ url('/') }}" class="btn btn-back">
                <i class="bi bi-arrow-left"></i> Volver al inicio
            </a>
        </div>
    </div>

    <div class="legal-footer">
        <p class="mb-0">
            &copy; {{ date('Y') }} <strong>ID SOFTWARE HOUSE</strong>.
            Todos los derechos reservados.
        </p>
        <p class="mb-0">Cuautla, Morelos, México.</p>
    </div>
</body>
</html>