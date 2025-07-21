@extends('adminlte::page')

@section('title', 'Reporte de Notas')

@section('content_header')
    <h1>Reporte de Notas por Curso</h1>
@endsection

@section('content')
    @foreach($datos as $grupo)
        <div class="card mb-4 shadow">
            <div class="card-header bg-primary text-white">
                <strong>{{ $grupo['curso'] }}</strong>
            </div>
            <div class="card-body">
                @if(empty($grupo['estudiantes']))
                    <p>No hay estudiantes registrados.</p>
                @else
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Promedio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grupo['estudiantes'] as $e)
                                <tr>
                                    <td>{{ $e['fullname'] }}</td>
                                    <td>{{ $e['promedio'] }}</td>
                                    <td>
                                        <a href="{{ route('profesor.reporte.tareas', ['userId' => $e['id'], 'cursoId' => $grupo['id']]) }}"
                                        class="btn btn-info btn-sm">
                                            Ver Tareas
                                        </a>


                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endforeach
@endsection
