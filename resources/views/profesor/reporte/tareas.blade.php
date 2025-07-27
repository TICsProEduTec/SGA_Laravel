@extends('adminlte::page')

@section('title', 'Tareas del Estudiante')

@section('content_header')
    <h1>Notas de {{ $user->fullname }} en el curso {{ $curso->fullname }}</h1>
@endsection

@section('content')
    <a href="{{ route('profesor.reporte.index') }}" class="btn btn-secondary mb-3">← Volver al reporte</a>

    @if(count($tareas))
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Actividad</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tareas as $tarea)
                    <tr>
                        <td>{{ $tarea['name'] }}</td>
                        <td>
                            @if(is_numeric($tarea['nota']))
                                <span class="{{ $tarea['nota'] < 7 ? 'text-danger' : 'text-success' }}">
                                    {{ $tarea['nota'] }}
                                </span>
                            @else
                                <span class="text-muted">{{ $tarea['nota'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-info">
            No hay tareas disponibles para este curso.
        </div>
    @endif
@endsection
