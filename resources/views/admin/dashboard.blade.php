@extends('adminlte::page')

@section('title', 'Administrador')

@section('content_header')
    <div class="text-center mt-4 mb-4">
        <img src="{{ asset('images/admin.png') }}" alt="Administrador" class="rounded-circle shadow mb-3" width="120">
        <h1 class="fw-bold" style="color: #2A96D8;">🛠 Bienvenido, Administrador</h1>
        <h2 class="h5 text-dark">{{ Auth::user()->name }} {{ Auth::user()->ap_paterno }}</h2>
        <hr class="w-50 mx-auto mt-3">
    </div>
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center g-4">

            {{-- Tarjeta 1: Usuarios --}}
            <div class="col-md-4">
                <div class="card text-white border-0 shadow text-center p-3" style="background-color: #2A96D8;">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h5 class="card-title fw-bold">Usuarios</h5>
                        <p class="card-text">Gestiona los usuarios registrados en el sistema.</p>
                    </div>
                </div>
            </div>

            {{-- Tarjeta 2: Plantillas --}}
            <div class="col-md-4">
                <div class="card text-white border-0 shadow text-center p-3" style="background-color: #2A96D8;">
                    <div class="card-body">
                        <i class="fas fa-bookmark fa-3x mb-3"></i>
                        <h5 class="card-title fw-bold">Plantillas</h5>
                        <p class="card-text">Crea y administra plantillas académicas base.</p>
                    </div>
                </div>
            </div>

            {{-- Tarjeta 3: Períodos --}}
            <div class="col-md-4">
                <div class="card text-white border-0 shadow text-center p-3" style="background-color: #2A96D8;">
                    <div class="card-body">
                        <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                        <h5 class="card-title fw-bold">Períodos</h5>
                        <p class="card-text">Configura los períodos académicos disponibles.</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- Verificación adicional de roles --}}
        <div class="text-center mt-5">
            @can('is-admin')
                <p class="text-success">✅ Tienes permisos de administrador</p>
            @else
                <p class="text-danger">❌ No tienes permisos</p>
            @endcan
        </div>
    </div>
@endsection
