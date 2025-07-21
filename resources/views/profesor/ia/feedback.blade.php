<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Retroalimentación IA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 30px;
            font-size: 14px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .seccion {
            margin-bottom: 30px;
        }

        .seccion h3 {
            color: #2c3e50;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .boton-descarga {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3490dc;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .boton-descarga:hover {
            background-color: #2779bd;
        }
    </style>
</head>
<body>
    <h2>Retroalimentación generada por IA</h2>

    <div class="seccion">
        <h3>🧠 Retroalimentación</h3>
        <p>{!! nl2br(e($retroalimentacion ?? 'No disponible')) !!}</p>
    </div>

    <div class="seccion">
        <h3>📌 Recomendaciones</h3>
        <p>{!! nl2br(e($recomendaciones ?? 'No disponible')) !!}</p>
    </div>

    @if(isset($giftPath) && Storage::disk('public')->exists($giftPath))
        <div class="seccion">
            <h3>📄 Banco de Preguntas GIFT</h3>
            <a href="{{ asset('storage/' . $giftPath) }}" class="boton-descarga" download>
                ⬇️ Descargar archivo .gift para Moodle
            </a>
        </div>
    @endif
</body>
</html>
