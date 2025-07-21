@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Crear Curso</h1>
@stop

@section('content')
    @if (session('info'))
    <div class="mb-3 alert alert-success">
        <strong>{{ session('info') }}</strong>
    </div>
    @endif
<!-- Formulario para crear curso -->
<div class="container">
    <form action="{{route('curso.store2', $plantilla->id)}}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name">Nombre del Curso</label>
            <input type="text" class="form-control" name="name" >
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Crear Curso
            </button>
            <a href="{{route('plantillas.index')}}" class="btn btn-info">Regresar</a>
        </div>
    </form>
</div>
<hr>
<div class="container">
    <table class="table">
        <thead class="table table-dark">
            <tr>
                <th>Nombre del Curso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @foreach ($plantilla->cursos as $curso)
                <tr>
                    <td>{{$curso->name}}</td>
                    <td>
                        <form action="{{route('curso.destroy2', [$curso->id,$plantilla->id])}}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type= "submit" class="btn btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@stop

@section('css')
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script>console.log("Hi, I'm using the Laravel-AdminLTE package!");</script>
@stop
