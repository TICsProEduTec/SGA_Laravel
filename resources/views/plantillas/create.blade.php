@extends('adminlte::page')

@section('title', 'Crear Plantilla')

@section('content_header')
    <h1>Crear Plantilla</h1>
@stop

@section('content')
<div class="container">
    <form action="{{ route('plantillas.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name">Nombre de la Plantilla</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Crear Plantilla
            </button>
            <a href="{{ route('plantillas.index') }}" class="btn btn-info">Regresar</a>
        </div>
    </form>
</div>
@stop

@section('css')
@stop

@section('js')
    <script>console.log("Vista Crear Plantilla cargada.");</script>
@stop
