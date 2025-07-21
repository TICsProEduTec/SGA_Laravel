<!-- resources/views/grados/editarnota.blade.php -->
@extends('adminlte::page')

@section('title', 'Editar Nota de ' . $area->name)

@section('content')
    <h1>Editar Nota - {{ $area->name }} ({{ $user->name }})</h1>

    <div class="mb-4">
        <a href="{{ route('grado.verDetalleNota', ['grado' => $grado->id, 'user' => $user->id, 'area' => $area->id]) }}"
           class="btn btn-secondary">
            Volver
        </a>
    </div>

    @if(session('info'))
        <div class="alert alert-success">
            {{ session('info') }}
        </div>
    @endif

    <form action="{{ route('grado.updateNota', ['grado' => $grado->id, 'user' => $user->id, 'area' => $area->id]) }}"
          method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="nota">Nota</label>
            <input type="text"
                   name="nota"
                   id="nota"
                   class="form-control @error('nota') is-invalid @enderror"
                   value="{{ old('nota', $nota) }}"
                   placeholder="Ej: 9.50">
            @error('nota')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
    </form>
@stop
