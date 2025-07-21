@extends('adminlte::page')

@section('title', 'Mis Cursos Asignados')

@section('content_header')
    <h1>Mis Cursos Asignados</h1>
@endsection

@section('content')
    <div class="container">
        @if(isset($message))
            <p>{{ $message }}</p> <!-- Si no hay cursos, mostramos el mensaje -->
        @else
            <div class="row row-cols-1 row-cols-md-3 g-4">
                @foreach($courses as $course)
                    <div class="col mb-4">
                        <div class="card shadow-lg rounded">
                            <!-- Imagen ajustada con altura menor -->
                            <img src="{{ asset('images/curso-imagen.jpg') }}" class="card-img-top rounded-top img-fluid" alt="Imagen del curso" style="height: 150px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title text-primary">{{ $course['fullname'] }}</h5>
                                <p class="card-text">
                                    <strong>Nombre del curso:</strong> {{ $course['shortname'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
