@extends('adminlte::page')

@section('title', 'Grados')

@section('content_header')
    <h1>Selecciona un Grado</h1>
@stop

@section('content')
    <div class="row">
        @forelse($grados as $grado)
            <div class="col-md-4">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h4>{{ $grado->name }}</h4>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="{{ route('dashboard.materias', $grado->id) }}" class="small-box-footer">
                        Ver Materias <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-warning">No hay grados disponibles.</div>
            </div>
        @endforelse
    </div>
@stop
