<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Retroalimentación</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .estudiante { margin-bottom: 30px; page-break-inside: avoid; }
        .titulo { font-weight: bold; margin-bottom: 5px; }
        .subtitulo { font-style: italic; color: #555; }
        hr { border: 0; border-top: 1px solid #ccc; }
    </style>
</head>
<body>
    <h2>🧠 Retroalimentación por estudiante reprobado</h2>
    @foreach ($estudiantes as $e)
        <div class="estudiante">
            <div class="titulo">👤 {{ $e['nombre'] }} | 📘 {{ $e['curso'] }} | Nota: {{ $e['nota'] }}</div>
            <div class="subtitulo">Retroalimentación:</div>
            <p>{!! nl2br(e($e['retro'])) !!}</p>
            <hr>
        </div>
    @endforeach
</body>
</html>
