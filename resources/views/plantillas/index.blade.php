@extends('adminlte::page')

@section('title', 'Plantillas')

@section('content_header')
    <h1>Plantillas</h1>
@stop

@section('content')
    @if (session('info'))
        <div class="mb-3 alert alert-success">
            <strong>{{ session('info') }}</strong>
        </div>
    @endif

    <div class="container">
        <a href="{{ route('plantillas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear Plantilla
        </a>
    </div>

    <div class="container mt-3">
        <table class="table">
            <thead class="table-dark">
                <tr>
                    <th>Nombre de la Plantilla</th>
                    <th>Cursos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                @foreach ($plantillas as $plantilla)
                    <tr>
                        <td>{{ $plantilla->name }}</td>
                        <td>{{ $plantilla->cursos->count() }}</td>
                        <td>
                            <a href="{{ route('curso.crearcurso', $plantilla->id) }}" class="btn btn-warning">Crear Curso</a>
                            <a href="{{ route('plantillas.edit', $plantilla->id) }}" class="btn btn-success">Editar</a>
                            <form action="{{ route('plantillas.destroy', $plantilla->id) }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar esta plantilla?')">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script>console.log("Vista Plantillas cargada.");</script>
@stop
