@extends('adminlte::page')

@section('title', 'Modificar Plantilla')

@section('content_header')
    <h1>Modificar Plantilla</h1>
@stop

@section('content')
<div class="container">
    <form action="{{ route('plantillas.update', $plantilla->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name">Nombre de la Plantilla</label>
            <input type="text" name="name" class="form-control" value="{{ $plantilla->name }}" required>
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Modificar Plantilla
            </button>
            <a href="{{ route('plantillas.index') }}" class="btn btn-info">Regresar</a>
        </div>
    </form>
</div>
@stop

@section('css')
@stop

@section('js')
    <script>console.log("Vista Editar Plantilla cargada.");</script>
@stop
