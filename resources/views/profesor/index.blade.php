@extends('adminlte::page')

@section('title', 'Dashboard del Profesor')

@section('content_header')
    <div class="text-center mt-4 mb-4">
        <img src="{{ asset('images/profesor.png') }}" alt="Profesor" class="rounded-circle shadow mb-3" width="120">

        <h1 class="fw-bold" style="color: #2A96D8;">
            👨‍🏫 Bienvenido, Profesor
        </h1>
        
        <h2 class="h4 text-dark">{{ Auth::user()->name }} {{ Auth::user()->ap_paterno }}</h2>

        <hr class="w-50 mx-auto mt-3">
    </div>
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center g-4">

            @php
                $cards = [
                    ['icon' => 'fas fa-book', 'title' => 'Gestión Académica', 'desc' => 'Administra tus clases y accede a tus asignaturas fácilmente.'],
                    ['icon' => 'fas fa-user-graduate', 'title' => 'Estudiantes', 'desc' => 'Observa el progreso académico de tus estudiantes.'],
                    ['icon' => 'fas fa-chalkboard-teacher', 'title' => 'Recursos Didácticos', 'desc' => 'Prepara materiales de apoyo y actividades de refuerzo.'],
                    ['icon' => 'fas fa-users', 'title' => 'Estudiantes Matriculados', 'desc' => 'Consulta qué estudiantes están registrados en tus cursos.'],
                    ['icon' => 'fas fa-chart-bar', 'title' => 'Visualización de Datos', 'desc' => 'Revisa gráficas de rendimiento, promedios y materias.'],
                    ['icon' => 'fas fa-robot', 'title' => 'Asistente IA', 'desc' => 'Obtén sugerencias y retroalimentaciones inteligentes.'],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="col-md-4">
                    <div class="card text-white border-0 shadow text-center p-3" style="background-color: #2A96D8;">
                        <div class="card-body">
                            <i class="{{ $card['icon'] }} fa-3x mb-3"></i>
                            <h5 class="card-title fw-bold">{{ $card['title'] }}</h5>
                            <p class="card-text">{{ $card['desc'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>
    </div>
@endsection
