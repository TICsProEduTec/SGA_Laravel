@extends('adminlte::page')

@section('title', 'Crear Usuario')

@section('content_header')
    <h1>Crear Usuario</h1>
@stop

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>¡Ups! Algo salió mal:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="container">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Nombre del Usuario</label>
                <input type="text" name="name" class="form-control" placeholder="Ingrese nombre" value="{{ old('name') }}">
            </div>

            <div class="mb-3">
                <label for="ap_paterno" class="form-label">Apellido Paterno</label>
                <input type="text" name="ap_paterno" class="form-control" placeholder="Ingrese apellido paterno" value="{{ old('ap_paterno') }}">
            </div>

            <div class="mb-3">
                <label for="ap_materno" class="form-label">Apellido Materno</label>
                <input type="text" name="ap_materno" class="form-control" placeholder="Ingrese apellido materno" value="{{ old('ap_materno') }}">
            </div>

            <div class="mb-3">
                <label for="cedula" class="form-label">Cédula</label>
                <input type="text" name="cedula" class="form-control" placeholder="Ingrese cédula" value="{{ old('cedula') }}">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" placeholder="ejemplo@correo.com" value="{{ old('email') }}">
            </div>

            <div class="mb-3">
                <label for="celular" class="form-label">Celular</label>
                <input type="text" name="celular" class="form-control" placeholder="0987654321" value="{{ old('celular') }}">
            </div>

            <div class="mb-3 d-flex justify-content-between">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Crear Usuario
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-info">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
            </div>
        </form>
    </div>
@stop

@section('css')
    {{-- Estilos personalizados opcionales --}}
@stop

@section('js')
    <script>console.log("Formulario de creación de usuario cargado.");</script>
@stop
