@extends('adminlte::page')

@section('title', 'Reporte de Notas')

@section('content_header')
    <h1>Reporte de Notas por Curso</h1>
@endsection

@section('content')
    @foreach($datos as $grupo)
        <div class="card mb-4 shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $grupo['curso'] }}</strong>
                </div>
                
                {{-- Botón para descargar PDF --}}
                <div>
                    <a href="{{ route('profesor.reporte.generarPdf', ['cursoId' => $grupo['id']]) }}"
                       class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </a>
                </div>
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
                                <th>Estado</th> {{-- Nueva columna --}}
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grupo['estudiantes'] as $e)
                                <tr>
                                    <td>{{ $e['fullname'] }}</td>
                                    <td>{{ $e['promedio'] }}</td>
                                    <td>
                                        <span class="badge {{ $e['estado'] === 'Aprobado' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $e['estado'] }}
                                        </span>
                                    </td>
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
