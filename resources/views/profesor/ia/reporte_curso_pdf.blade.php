<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte completo IA</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin-top: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; }
    </style>
</head>
<body>

<h1>📋 Reporte generado por IA - Cursos del docente</h1>

@foreach($listado as $curso)
    <h2>Curso: {{ $curso['curso'] }}</h2>

    <table>
        <thead>
            <tr>
                <th>Nombre del estudiante</th>
                <th>Nota</th>
                <th>Estado</th>
                <th>Retroalimentación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($curso['estudiantes'] as $est)
                <tr>
                    <td>{{ $est['nombre'] }}</td>
                    <td>{{ $est['nota'] }}</td>
                    <td>{{ $est['estado'] }}</td>
                    <td>{{ $est['retro'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h4>🎯 Recomendación del curso:</h4>
    <p>{{ $curso['recomendacion'] }}</p>
@endforeach

</body>
</html>
