@extends('adminlte::page')

@section('title', 'Dashboard Docentes')

@section('content_header')
    <h1>Selecciona un Curso</h1>
@stop

@section('content')
    <div class="row">
        @forelse($courses as $course)
            <div class="col-md-4">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h4>{{ $course->name }}</h4>
                        <p>{{ $course->shortname ?? '' }}</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <a href="{{ route('dashboard.materias', $course->id_curso_moodle) }}" class="small-box-footer">
                        Ver Materias <i class="fas fa-arrow-circle-right"></i>
                    </a>

                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-secondary">No hay cursos para mostrar.</div>
            </div>
        @endforelse
    </div>
@stop
