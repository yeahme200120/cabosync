<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Semanal de Asistencia</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'dejavusans', sans-serif;
            font-size: 8pt;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            width: 100%;
            border-bottom: 3px solid #1E5180;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; padding: 0; }

        .logo-cell { width: 120px; }
        .logo-cell img { max-height: 60px; max-width: 120px; height: auto; }

        .titulo-cell { text-align: center; }
        .titulo-principal {
            font-size: 16pt;
            font-weight: bold;
            color: #1E5180;
            margin: 0;
            letter-spacing: 1px;
        }
        .subtitulo { font-size: 9pt; color: #666; margin: 2px 0 0 0; }

        .info-box {
            background: #f4f6f9;
            border-left: 3px solid #F28C28;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 8pt;
            line-height: 1.5;
        }
        .info-box table { width: 100%; }
        .info-box td { padding: 1px 0; }
        .info-label { color: #666; font-weight: bold; width: 90px; }

        .tabla { width: 100%; border-collapse: collapse; font-size: 7.5pt; }

        .tabla thead th {
            background: #1E5180;
            color: #fff;
            font-weight: bold;
            padding: 5px 3px;
            text-align: center;
            border: 1px solid #143a5c;
            font-size: 7pt;
        }

        .tabla thead th.col-empleado { text-align: left; padding-left: 8px; width: {{ $multi_empresa ? '18%' : '22%' }}; }
        .tabla thead th.col-empresa { text-align: left; padding-left: 6px; width: 12%; }
        .tabla thead th.col-puesto   { text-align: left; padding-left: 6px; width: {{ $multi_empresa ? '10%' : '13%' }}; }
        .tabla thead th.col-dia      { width: 5.5%; }

        .tabla tbody td {
            padding: 4px 3px;
            border: 1px solid #ddd;
            text-align: center;
            vertical-align: middle;
        }
        .tabla tbody td.col-empleado {
            text-align: left;
            padding-left: 8px;
            font-weight: bold;
            color: #1E5180;
        }
        .tabla tbody td.col-empresa {
            text-align: left;
            padding-left: 6px;
            font-size: 6.5pt;
            color: #666;
        }
        .tabla tbody td.col-puesto { text-align: left; padding-left: 6px; font-size: 7pt; }

        .estado-asistencia   { background: #d4edda; color: #155724; font-weight: bold; }
        .estado-justificada  { background: #fff3cd; color: #856404; font-weight: bold; }
        .estado-falta        { background: #f8d7da; color: #721c24; font-weight: bold; }
        .estado-sin-registro { background: #f8f9fa; color: #adb5bd; }

        .tabla tbody tr:nth-child(even) { background: #fafbfc; }

        .totales-box {
            margin-top: 10px;
            background: #1E5180;
            color: #fff;
            padding: 8px 12px;
            font-size: 9pt;
        }
        .totales-box table { width: 100%; border-collapse: collapse; }
        .totales-box td { padding: 2px 0; }
        .totales-label { color: #F28C28; font-weight: bold; }
        .totales-valor { font-weight: bold; }

        .firma-box { margin-top: 20px; text-align: center; font-size: 8pt; }
        .firma-linea { width: 250px; border-top: 1px solid #333; margin: 30px auto 3px auto; }

        .footer {
            text-align: center;
            font-size: 7pt;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td class="logo-cell">
                    @php
                        $logoPath = public_path('img/logo-cabosync.png');
                        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
                    @endphp
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="CaboSync">
                    @endif
                </td>
                <td class="titulo-cell">
                    <h1 class="titulo-principal">REPORTE SEMANAL DE ASISTENCIA</h1>
                    <p class="subtitulo">Sistema de Gestión de Asistencia - CaboSync</p>
                </td>
                <td style="width: 120px; text-align: right; font-size: 7pt; color: #999;">
                    <div>Emitido:</div>
                    <div>{{ $generado_en->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-box">
        <table>
            <tr>
                <td class="info-label">Empresa:</td>
                <td>{{ $empresa->nombre ?? 'TODAS LAS EMPRESAS' }}</td>
                <td class="info-label">Semana:</td>
                <td>{{ $semana['inicio'] }} al {{ $semana['fin'] }} ({{ $semana['week'] }})</td>
            </tr>
            <tr>
                <td class="info-label">Obra:</td>
                <td>{{ $obra->nombre ?? 'Todas las obras' }}</td>
                <td class="info-label">Total empleados:</td>
                <td><strong>{{ $total_empleados }}</strong></td>
            </tr>
        </table>
    </div>

    <table class="tabla">
        <thead>
            <tr>
                <th class="col-empleado">Empleado</th>
                @if($multi_empresa)
                    <th class="col-empresa">Empresa</th>
                @endif
                <th class="col-puesto">Puesto</th>
                @foreach($semana['fechas'] as $fecha => $letra)
                    <th class="col-dia">
                        {{ $letra }}<br>
                        <span style="font-size: 6pt;">{{ \Carbon\Carbon::parse($fecha)->format('d') }}</span>
                    </th>
                @endforeach
                <th class="col-dia" style="background: #143a5c;">Pres.</th>
                <th class="col-dia" style="background: #143a5c;">Falt.</th>
                <th class="col-dia" style="background: #143a5c;">Just.</th>
                <th class="col-dia" style="background: #143a5c;">Desc.</th>
                <th class="col-dia" style="background: #F28C28;">Of</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $fila)
                <tr>
                    <td class="col-empleado">{{ $fila['empleado']->nombre_completo }}</td>
                    @if($multi_empresa)
                        <td class="col-empresa">{{ $fila['empresa'] }}</td>
                    @endif
                    <td class="col-puesto">{{ $fila['empleado']->puesto_cargo }}</td>

                    @foreach($semana['fechas'] as $fecha => $letra)
                        @php
                            $dia = $fila['dias'][$fecha];
                            $estado = $dia['estado'];
                            $simbolo = '—';
                            $clase = 'estado-sin-registro';

                            if ($estado === 'asistencia') {
                                $simbolo = '✓';
                                $clase = 'estado-asistencia';
                            } elseif ($estado === 'asistencia_justificada') {
                                $simbolo = '⚠';
                                $clase = 'estado-justificada';
                            } elseif ($estado === 'falta_injustificada') {
                                $simbolo = '✗';
                                $clase = 'estado-falta';
                            }
                        @endphp
                        <td class="{{ $clase }}">{{ $simbolo }}</td>
                    @endforeach

                    <td><strong>{{ $fila['presentes'] }}</strong></td>
                    <td><strong>{{ $fila['faltas'] }}</strong></td>
                    <td><strong>{{ $fila['justificadas'] }}</strong></td>
                    <td><strong>{{ $fila['dias_descuento'] }}</strong></td>
                    <td><strong>{{ number_format($fila['horas_extra'], 1) }}h</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totales-box">
        <table>
            <tr>
                <td><span class="totales-label">Total empleados:</span> <span class="totales-valor">{{ $total_empleados }}</span></td>
                <td><span class="totales-label">Presentes:</span> <span class="totales-valor">{{ $totales['presentes'] }}</span></td>
                <td><span class="totales-label">Faltas:</span> <span class="totales-valor">{{ $totales['faltas'] }}</span></td>
                <td><span class="totales-label">Justificadas:</span> <span class="totales-valor">{{ $totales['justificadas'] }}</span></td>
                <td><span class="totales-label">Días a descontar:</span> <span class="totales-valor">{{ $totales['dias_descuento'] }}</span></td>
                <td><span class="totales-label">Horas extra:</span> <span class="totales-valor">{{ number_format($totales['horas_extra'], 1) }}h</span></td>
            </tr>
        </table>
    </div>

    <div class="firma-box">
        <div class="firma-linea"></div>
        <div><strong>{{ $responsable ?? 'Responsable de Obra' }}</strong></div>
        <div style="color: #666;">Firma del responsable</div>
    </div>

    <div class="footer">
        Documento generado por CaboSync - ID SOFTWARE HOUSE | Cuautla, Morelos, México
        <br>
        Este documento es propiedad confidencial. Prohibida su reproducción total o parcial.
    </div>

</body>
</html>