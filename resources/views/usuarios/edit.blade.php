@extends('adminlte::page')

@section('title', 'Editar Usuario')

@section('content_header')
    <h1>Editar Usuario</h1>
@stop

@section('content')
<div class="container">
    <form action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Nombre del Usuario</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}">
        </div>

        <div class="mb-3">
            <label for="ap_paterno" class="form-label">Apellido Paterno</label>
            <input type="text" name="ap_paterno" class="form-control" value="{{ old('ap_paterno', $user->ap_paterno) }}">
        </div>

        <div class="mb-3">
            <label for="ap_materno" class="form-label">Apellido Materno</label>
            <input type="text" name="ap_materno" class="form-control" value="{{ old('ap_materno', $user->ap_materno) }}">
        </div>

        <div class="mb-3">
            <label for="cedula" class="form-label">Cédula</label>
            <input type="text" name="cedula" class="form-control" value="{{ old('cedula', $user->cedula) }}">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Correo Electrónico</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
        </div>

        <div class="mb-3">
            <label for="celular" class="form-label">Celular</label>
            <input type="text" name="celular" class="form-control" value="{{ old('celular', $user->celular) }}">
        </div>

        <div class="mb-3 d-flex justify-content-between">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-info">
                <i class="fas fa-arrow-left"></i> Regresar
            </a>
        </div>
    </form>
</div>
@stop

@section('css')
@stop

@section('js')
    <script>console.log("Formulario de edición cargado.");</script>
@stop
