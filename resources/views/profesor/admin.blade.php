@extends('adminlte::page')

@section('content')
    <h2>Bienvenido, Administrador</h2>

    <div class="alert alert-info">
        Rol actual en sesión: <strong>{{ session('rol_moodle') }}</strong>
    </div>

    @can('is-admin')
        <p class="text-success">✔️ Tienes permiso de administrador.</p>
    @else
        <p class="text-danger">❌ No tienes permiso de administrador.</p>
    @endcan

    @can('is-profesor')
        <p class="text-success">✔️ Tienes permiso de profesor.</p>
    @else
        <p class="text-danger">❌ No tienes permiso de profesor.</p>
    @endcan
@endsection
