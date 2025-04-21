@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Editar Curso</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <center><h3>Rellene correctamente el formulario</h3></center>
        </div>
        <div class="card-body">
            <form action="{{route('admin.cursos.update', $curso->id)}}" method="POST">
                @method('PUT')
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Nombre del Curso</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{$curso->name}}">
                    @error('name')
                        <span class="text-danger">{{$message}}</span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="shortname" class="form-label">Nombre corto del curso</label>
                    <input type="text" class="form-control" id="shortname" name="shortname" value="{{$curso->shortname}}">
                    @error('shortname')
                        <span class="text-danger">{{$message}}</span>
                    @enderror
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Guardar Curso</button>
                    <a class="btn btn-dark" href="{{route('admin.cursos.index')}}" role="button">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop