@extends('adminlte::page')

@section('title', 'Tareas del Estudiante')

@section('content_header')
    <h1>Notas de {{ $user->name }} en el curso {{ $curso->nombre }}</h1>
@endsection

@section('content')
    <a href="{{ route('profesor.reporte.index') }}" class="btn btn-secondary mb-3">← Volver al reporte</a>

    @if(count($tareas))
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Actividad</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tareas as $item)
                    <tr>
                        <td>{{ Str::replace('_', ' ', $item['itemname']) }}</td>
                        <td>
                            {{ 
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
            No hay actividades calificadas para este curso.
        </div>
    @endif
@endsection
