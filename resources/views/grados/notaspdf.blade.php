<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Notas de {{ $user->name }}</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            margin: 30px;
            color: #555;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 300px; /* Tamaño del logo */
            margin-bottom: 15px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }


        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        p {
            font-size: 16px;
            margin-top: 10px;
            color: #666;
        }

        strong {
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

        tr:hover td {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/nombre-col.png') }}" alt="Logo del Colegio" class="logo">
        <div class="title">COLEGIO TÉCNICO PARTICULAR "RAFAEL GALETH"</div>
    </div>

    <h1>Notas del grado “{{ $grado->name }}”</h1>
    <p>
        <strong>Estudiante:</strong> {{ $user->name }}
        @if(!empty($user->email))
            ({{ $user->email }})
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Área</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
            @foreach($l_notas as $nota)
                <tr>
                    <td>{{ $nota['nombre'] }}</td>
                    <td>{{ $nota['nota'] !== null ? $nota['nota'] : 'Sin calificar' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
