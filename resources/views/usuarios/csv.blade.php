@extends('adminlte::page')

@section('title', 'Importar Estudiantes')

@section('content_header')
    <h1>Importar / Exportar Estudiantes desde CSV</h1>
@stop

@section('content')

    {{-- Mensajes de sesión --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Formulario de carga --}}
    <div class="card">
        <div class="card-body">
            <form action="{{ route('usuarios.importarCsv') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="archivo_csv" class="form-label">Selecciona archivo CSV:</label>
                    <input type="file" name="archivo_csv" class="form-control" accept=".csv" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Importar
                </button>

                <a href="{{ route('usuarios.exportarCsv') }}" class="btn btn-success ms-2">
                    <i class="fas fa-file-export"></i> Exportar Usuarios CSV
                </a>
                <a href="{{ route('users.index') }}" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </form>
        </div>
    </div>
@stop
