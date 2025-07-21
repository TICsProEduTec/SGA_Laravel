<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte IA</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #333; }
        h1 { font-size: 20px; margin-bottom: 10px; }
        h2 { margin-top: 20px; font-size: 16px; }
        .estudiante { margin-bottom: 15px; }
        .actividad { margin-top: 10px; font-style: italic; }
    </style>
</head>
<body>
    <h1>📘 Retroalimentación Académica y Actividad de Recuperación</h1>
    @foreach ($estudiantes as $est)
        <div class="estudiante">
            <h2>{{ $est['nombre'] }} (Curso: {{ $est['curso'] }})</h2>
            <p><strong>Nota:</strong> {{ $est['nota'] }}</p>
            <p><strong>Retroalimentación:</strong><br>{{ $est['retro'] }}</p>
            <div class="actividad">
                <strong>Actividad propuesta:</strong><br>
                {!! nl2br(e($est['actividad'])) !!}
            </div>
        </div>
    @endforeach
</body>
</html>
