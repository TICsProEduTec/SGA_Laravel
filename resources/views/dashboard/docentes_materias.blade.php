@extends('adminlte::page')

@section('title', 'Materias del Grado')

@section('content_header')
    <h1>Materias de {{ $grado->nombre }}</h1>
@stop

@section('content')
    <div class="row">
        @forelse($materias as $materia)
            <div class="col-md-4">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h4>{{ $materia->name }}</h4>
                        <p>{{ $materia->shortname }}</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <a href="{{ route('dashboard.show', ['courseId' => $materia->id_course_moodle]) }}?materiaId={{ $materia->id }}"
                       class="small-box-footer">
                        Ver Estudiantes <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">No hay materias para este grado.</div>
            </div>
        @endforelse
    </div>
@stop
