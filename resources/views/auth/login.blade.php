@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@section('title', 'Iniciar sesión')

@section('auth_header', 'Inicio de sesión')

@section('auth_body')
    <form action="{{ route('login') }}" method="POST">
        @csrf

        <x-adminlte-input 
            name="email" 
            label="Correo electrónico" 
            type="email" 
            required 
            autofocus 
            placeholder="Ingrese su correo electrónico" 
            :value="old('email')" 
        />

        <x-adminlte-input 
            name="password" 
            label="Contraseña" 
            type="password" 
            required 
            placeholder="Ingrese su contraseña" 
        />

        <button type="submit" class="btn btn-primary btn-block">
            Iniciar sesión
        </button>
    </form>
@endsection

@section('auth_footer')
    <p class="text-center text-muted">
        Solo profesores y administradores tienen acceso al sistema.
    </p>
@endsection

@section('css')
    <style>
        body.login-page {
            background-color: #1b1464; /* fondo base para que no quede vacío */
            background-image: url('/images/banner2.jpg');
            background-size: contain; /* <- reduce tamaño (deja la imagen completa visible) */
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .login-box {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
    </style>
@endsection