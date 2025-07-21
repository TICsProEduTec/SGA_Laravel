@extends('adminlte::page')

@section('title', 'Crear Periodo')

@section('content_header')
    <h1>Crear Periodo</h1>
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
        <form action="{{ route('periodos.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Nombre del Periodo</label>
                <input type="text" name="name" class="form-control" placeholder="Ej. Periodo 2025" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3 d-flex justify-content-between">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Crear Periodo
                </button>
                <a href="{{ route('periodos.index') }}" class="btn btn-info">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
            </div>
        </form>
    </div>
@stop

@section('css')
@stop

@section('js')
    <script>console.log("Vista Crear Periodo cargada.");</script>
@stop
