@extends('adminlte::page')

@section('title', 'Crear Grado')

@section('content_header')
    <h1>Crear Grado</h1>
@stop

@section('content')
<div class="container">
    <form action="{{ route('grado.store2', $periodo->id) }}" method="POST">
        @csrf

        {{-- Campo oculto para enviar periodo_id --}}
        <input type="hidden" name="periodo_id" value="{{ $periodo->id }}">

        <div class="mb-3">
            <label for="name">Nombre del Grado</label>
            <input type="text" class="form-control" name="name" required>
        </div>

        <div class="mb-3">
            <label for="plantilla_id">Elegir la Plantilla</label>
            <select name="plantilla_id" class="form-control" required>
                @foreach ($plantillas as $plantilla)
                    <option value="{{ $plantilla->id }}">{{ $plantilla->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Crear Grado</button>
            <a href="{{ route('periodos.index') }}" class="btn btn-info">Regresar</a>
        </div>
    </form>
</div>
@stop

@section('css')
@stop

@section('js')
    <script>console.log("Vista Crear Grado cargada.");</script>
@stop
