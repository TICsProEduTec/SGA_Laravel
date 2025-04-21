@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Consulta de usuario</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <center><h2>Consultar Datos del Estudiante</h2></center>
        </div>
        <div class="card-body">
            <form action="{{route('admin.usuarios.consulta')}}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">Escribe el nombre del usuario</label>
                    <input type="text" class="form-control" id="username" name="username">
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Consultar Usuario</button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop