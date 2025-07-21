<!-- resources/views/grados/detallenota.blade.php -->
@extends('adminlte::page')

@section('title', 'Detalle de Calificaciones')

@section('content')
    <h1>Detalle de {{ $area->name }} - {{ $user->ap_paterno }} {{ $user->ap_materno }} {{ $user->name }}</h1>
    <a href="{{ route('grado.consultarnotas', ['grado'=>$grado->id,'user'=>$user->id]) }}" class="btn btn-secondary mb-3">Regresar</a>

    @if(count($gradeitems))
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Actividad</th>
                    <th>Calificación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gradeitems as $item)
                    <tr>
                        <td>{{ Str::replace('_', ' ', $item['itemname']) }}</td>
                        <td>
                            {{ 
                              // Primero intentamos gradeformatted (sin etiquetas), si no, grade numérico
                              strip_tags($item['gradeformatted'] ?? '') 
                              ?: number_format($item['grade'] ?? 0, 2, ',', '.') 
                            }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-info">
            No hay actividades calificadas para esta materia.
        </div>
    @endif
@stop
