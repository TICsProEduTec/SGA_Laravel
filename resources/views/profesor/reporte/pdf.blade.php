<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Notas</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            font-size: 12px;
            margin: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 400px;
            margin-bottom: 15px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .sub-title {
            font-size: 16px;
            color: #555;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .info {
            font-size: 14px;
            margin-top: 5px;
            color: #666;
        }

        .info strong {
            font-weight: bold;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f4f4f4;
            color: #333;
            font-weight: bold;
        }

        td {
            background-color: #fff;
            color: #555;
        }

        tr:nth-child(even) td {
            background-color: #f9f9f9;
        }

        .aprobado {
            color: green;
            font-weight: bold;
        }

        .reprobado {
            color: red;
            font-weight: bold;
        }

        .directrices {
            margin-top: 30px;
            font-size: 14px;
            text-align: center;
            font-weight: bold;
            color: #333;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/nombre-col.png') }}" alt="Logo del Colegio" class="logo">
        <div class="title">COLEGIO TÉCNICO PARTICULAR "RAFAEL GALETH"</div>
        <div class="sub-title">Listado de Estudiantes y Notas</div>
    </div>

    <div class="info">
        <strong>Curso:</strong> {{ $curso }}<br>
        <strong>Código:</strong> {{ $shortname }}<br>
        <strong>Fecha:</strong> {{ $fecha }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nombre completo</th>
                <th>Correo</th>
                <th>Promedio</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($estudiantes as $index => $estudiante)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $estudiante['fullname'] }}</td>
                    <td>{{ $estudiante['email'] }}</td>
                    <td>{{ $estudiante['promedio'] }}</td>
                    <td>
                        <span class="{{ $estudiante['estado'] === 'Aprobado' ? 'aprobado' : 'reprobado' }}">
                            {{ $estudiante['estado'] }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="directrices">
        <p>Este reporte ha sido generado para cumplir con los requisitos administrativos del colegio. Los datos proporcionados son confidenciales y deben ser utilizados exclusivamente con fines educativos y administrativos.</p>
    </div>

    <div class="footer">
        <p>© 2025 Colegio Técnico Particular "Rafael Galeth". Todos los derechos reservados.</p>
    </div>
</body>
</html>
