@extends('adminlte::page')

@section('title', 'Grados del Periodo')

@section('content_header')
    <h1>{{ $periodo->name }}</h1>
@stop

@section('content')
    @if (session('info'))
        <div class="mb-3 alert alert-success">
            <strong>{{ session('info') }}</strong>
        </div>
    @endif

    <div class="container mb-3">
        <a href="{{ route('periodos.index') }}" class="btn btn-info">
            <i class="fas fa-arrow-left"></i> Regresar
        </a>
    </div>

    <div class="container">
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Nombre del Grado</th>
                    <th>Estudiantes</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                @forelse ($periodo->grados as $grado)
                    <tr>
                        <td>{{ $grado->name }}</td>
                        <td>{{ $grado->users->count() }}</td>
                        <td>
                            <a href="{{ route('grado.consultarmatricula', $grado->id) }}" class="btn btn-success">
                                <i class="fas fa-user-plus"></i> Matricular
                            </a>

                            <form action="{{ route('grado.destroy', $grado->id) }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('¿Estás seguro de eliminar este grado?')">
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No hay grados registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@stop

@section('css')
@stop

@section('js')
    <script>console.log("Vista Consultar Grados cargada.");</script>
@stop
