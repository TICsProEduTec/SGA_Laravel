@extends('adminlte::page')

@section('title', 'Mis Cursos Asignados')

@section('content_header')
    <h1>Mis Cursos Asignados</h1>
    <p>Aquí puedes ver los cursos que tienes asignados.</p>
@endsection

@section('content')
    <div class="container">
        @if(isset($message))
            <p>{{ $message }}</p> <!-- Si no hay cursos, mostramos el mensaje -->
        @else
            <div class="row row-cols-1 row-cols-md-3 g-4">
                @foreach($courses as $course)
                    <div class="col mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title text-center">{{ $course['fullname'] }}</h5>
                                <p class="card-text text-center">
                                    <strong>Código del curso:</strong> {{ $course['shortname'] }}<br>
                                    <strong>Descripción:</strong> {{ $course['summary'] ?? 'Sin descripción disponible' }}
                                </p>
                            </div>
                            <div class="card-footer text-center">
                                <a href="{{ route('curso.detalles', $course['id']) }}" class="btn btn-info btn-lg">
                                    <i class="fas fa-book"></i> Ver detalles
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
