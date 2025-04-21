@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Modulo Curso</h1>
@stop

@section('content')
@if (session('info'))
    <div class="alert alert-success">
        <strong>{{session('info')}}</strong>
    </div>
@endif
    <div class="card">
        <div class="card-header">
            <a class="btn btn-primary" href="{{route('admin.cursos.create')}}" role="button">Crear Curso</a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                  <tr class="bg-dark">
                    <th scope="col">Nombre Largo</th>
                    <th scope="col">Nombre corto</th>
                    <th scope="col">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($cursos as $curso)
                      <tr>
                        <th scope="row">{{$curso->name}}</th>
                        <td>{{$curso->shortname}}</td>
                        <td>
                          <form action="{{route('admin.cursos.destroy', $curso->id)}}" method="POST">
                            @csrf
                            @method('DELETE')
                            <a class="btn btn-primary" href="{{route('admin.cursos.edit', $curso->id)}}" role="button">Editar</a>
                            <button type="submit" class="btn btn-danger" >Elimnar</button>
                          </form>
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
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop