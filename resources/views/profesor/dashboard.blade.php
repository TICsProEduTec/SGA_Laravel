@extends('adminlte::page')

@section('title', 'Profesor')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <h3>Mis Cursos</h3>
                    @forelse($materias as $materia)
                        <div class="list-group-item">
                            <h5>{{ $materia->nombre }}</h5>
                            <p>{{ $materia->grado->nombre ?? 'Sin Grado Asignado' }}</p>
                            <!-- Aquí podrías agregar más información del curso -->
                        </div>
                    @empty
                        <p>No tienes cursos asignados.</p>
                    @endforelse
                </div>
            </div>

            <div class="col-md-9">
                <h2>Bienvenido, {{ $profesor->name }}</h2>
            </div>
        </div>
    </div>
@endsection
