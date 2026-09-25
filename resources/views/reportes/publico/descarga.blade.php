<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descargar Reporte - CaboSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f4f6f9 0%, #e8edf3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-descarga {
            max-width: 480px;
            width: 100%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(30, 81, 128, 0.15);
            overflow: hidden;
        }
        .header-descarga {
            background: #1E5180;
            color: #fff;
            padding: 24px;
            text-align: center;
        }
        .header-descarga h1 {
            margin: 0;
            font-size: 1.5rem;
            letter-spacing: 2px;
        }
        .header-descarga p {
            margin: 4px 0 0 0;
            font-size: 0.85rem;
            color: #F28C28;
        }
        .cuerpo-descarga {
            padding: 32px 24px;
        }
        .btn-descarga {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px;
            font-weight: 600;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-pdf {
            background: #dc3545;
            color: #fff;
            border: none;
        }
        .btn-pdf:hover {
            background: #bb2d3b;
            color: #fff;
            transform: translateY(-2px);
        }
        .btn-excel {
            background: #28a745;
            color: #fff;
            border: none;
        }
        .btn-excel:hover {
            background: #218838;
            color: #fff;
            transform: translateY(-2px);
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 24px;
        }
        .aviso-expira {
            background: #fff3cd;
            color: #856404;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.8rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card-descarga">
        <div class="header-descarga">
            <h1>CABOSYNC</h1>
            <p>Reporte Semanal de Asistencia</p>
        </div>

        <div class="cuerpo-descarga">
            @if($token->estaExpirado())
                <div class="alert alert-danger text-center">
                    <h5 class="mb-2">⏰ Enlace expirado</h5>
                    <p class="mb-0">Este enlace ya no es válido. Solicita uno nuevo.</p>
                </div>
            @else
                <div class="info-box">
                    <strong>Empresa:</strong> {{ $token->empresa->nombre ?? 'N/D' }}<br>
                    @if($token->obra)
                        <strong>Obra:</strong> {{ $token->obra->nombre }}<br>
                    @endif
                    <strong>Semana:</strong> {{ $token->week }}<br>
                    <strong>Descargas realizadas:</strong> {{ $token->descargas }}
                </div>

                <a href="{{ route('reportes.publico.descargar', ['token' => $token->token, 'tipo' => 'pdf']) }}"
                   class="btn-descarga btn-pdf">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                    </svg>
                    Descargar PDF
                </a>

                <a href="{{ route('reportes.publico.descargar', ['token' => $token->token, 'tipo' => 'excel']) }}"
                   class="btn-descarga btn-excel">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                    </svg>
                    Descargar Excel
                </a>

                <div class="aviso-expira mt-3">
                    ⏰ Este enlace expira el {{ $token->expira_en->format('d/m/Y H:i') }}
                </div>
            @endif
        </div>
    </div>
</body>
</html>