{{-- ==================== CONSULTAR NOTAS ==================== --}}

@extends('adminlte::page')

@section('title', 'Notas del Estudiante')

@section('content_header')
    <h1>
        {{ "{$grado->periodo->name} - {$grado->name} - {$user->ap_paterno} {$user->ap_materno} {$user->name}" }}
    </h1>

    <div class="d-flex justify-content-between mt-3">
        <a href="{{ route('grado.consultarmatricula', $grado->id) }}" class="btn btn-info">Regresar</a>
        <a href="{{ route('grado.generarPDF', ['grado' => $grado->id, 'user' => $user->id]) }}"
           target="_blank" class="btn btn-danger">
            Generar PDF
        </a>
    </div>
@stop

@section('content')
    @if (session('info'))
        <div class="mb-3 alert alert-success">
            <strong>{{ session('info') }}</strong>
        </div>
    @endif

    <div class="container">
        <table class="table">
            <thead class="table-dark">
                <tr>
                    <th>Área</th>
                    <th>Nota</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                @foreach ($l_notas as $areaId => $datos)
                    <tr>
                        <td>{{ $datos['nombre'] }}</td>
                        <td>{{ $datos['nota'] ?? 'Sin nota' }}</td>
                        <td class="d-flex gap-1">
                            {{-- Ver detalle de la materia --}}
                            <a href="{{ route('grado.verDetalleNota', ['grado' => $grado->id, 'user' => $user->id, 'area' => $areaId]) }}"
                               class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@stop

@section('css')
@stop

@section('js')
    <script>console.log("Vista Consultar Notas cargada.");</script>
@stop
