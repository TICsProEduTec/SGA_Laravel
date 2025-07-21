@extends('adminlte::page')

@section('title', 'Mis Grados')

@section('content_header')
    <h1>Mis Grados (Materias que impartes)</h1>
@endsection

@section('content')
    @can('is-profesor')

        {{-- Materias registradas --}}
        <h4 class="mt-4">Materias asignadas en el sistema:</h4>
        @if ($materias->isEmpty())
            <div class="alert alert-warning">No tienes materias registradas aún.</div>
        @else
            <ul class="list-group mb-4">
                @foreach ($materias as $materia)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $materia->name }}</strong><br>
                            <small>ID Moodle: {{ $materia->id_course_moodle }}</small>
                        </div>
                        <span class="badge bg-primary">{{ $materia->grado->name ?? 'Sin grado' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Cursos de Moodle no registrados --}}
        <h4>Cursos en Moodle no registrados:</h4>
        @if ($cursosNoRegistrados->isEmpty())
            <div class="alert alert-success">Todos tus cursos están registrados correctamente.</div>
        @else
            <ul class="list-group">
                @foreach ($cursosNoRegistrados as $curso)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $curso['fullname'] }}</strong><br>
                            <small>ID Moodle: {{ $curso['id'] }}</small>
                        </div>
                        <span class="badge bg-secondary">No registrado</span>
                    </li>
                @endforeach
            </ul>
        @endif

    @else
        <div class="alert alert-danger">No tienes permisos para ver esta sección.</div>
    @endcan
@endsection
