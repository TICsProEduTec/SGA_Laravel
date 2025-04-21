@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Categorias de Moodle</h1>
@stop

@section('content')
@if (session('info'))
    <div class="alert alert-success">
        <strong>{{session('info')}}</strong>
    </div>
@endif
<div class="card">
    <div class="card-header">
        <a href="{{route('admin.categorias.create')}}" class="btn btn-primary active" aria-current="page" role="button">Crear Categoria</a>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
              <tr class="bg-dark">
                <th scope="col">Nombre de la Categoria</th>
                <th scope="col" width="25%">Acciones</th>
              </tr>
            </thead>
            <tbody>
                @foreach (json_decode($categorias) as $item)
                    <tr>
                        <td>{{$item->name}}</td>
                        <td>
                            <a href="{{route('admin.categorias.edit', $item->id)}}" class="btn btn-info"  role="button">Editar</a>
                            <a href="{{route('admin.categorias.veliminar', $item->id)}}" class="btn btn-danger"  role="button">Eliminar</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    <link rel="stylesheet" href="/css/admin_custom.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop