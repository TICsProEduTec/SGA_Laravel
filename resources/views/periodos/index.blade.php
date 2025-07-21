{{-- resources/views/periodos/index.blade.php --}}
@extends('adminlte::page')

@section('title', 'Lista de Periodos')

@section('content_header')
    <h1>Lista de Periodos</h1>
@stop

@section('content')
    @if (session('info'))
        <div class="alert alert-success">
            <strong>{{ session('info') }}</strong>
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('periodos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear Periodo
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Grados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($periodos as $periodo)
                        <tr>
                            <td>{{ $periodo->name }}</td>
                            <td><a href="{{ route('periodo.consultargrados', $periodo->id) }}">{{ $periodo->grados_count }}</a></td>
                            <td>
                                <a href="{{ route('grado.creargrado', $periodo->id) }}" class="btn btn-sm btn-info">Crear Grado</a>
                                <a href="{{ route('periodos.edit', $periodo->id) }}" class="btn btn-sm btn-success">Editar</a>
                                <form action="{{ route('periodos.destroy', $periodo->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar periodo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('js')
    <script>console.log('Vista de periodos cargada.');</script>
@stop
