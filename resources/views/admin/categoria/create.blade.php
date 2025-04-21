@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Crear Categorias de Moodle</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Crear tu categoria</h2>
    </div>
    <div class="card-body">
        <form action="{{route('admin.categorias.store')}}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="scategoria" class="form-label">Nombre de la categoria</label>
                <select class="form-select" aria-label="Default select example" id="scategoria" name="scategoria">
                    <option value="0">Superior</option>
                    @foreach (json_decode($categorias) as $item)
                        <option value="{{$item->id}}">{{$item->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Nombre de la categoria</label>
                <input type="text" class="form-control" id="name" name="name">
            </div>
            <div class="mb-3">
                <label for="IdNumber" class="form-label">IdNumber</label>
                <input type="text" class="form-control" id="IdNumber" name="idnumber">
            </div>
            <div class="mb-3">
                <label for="Descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" rows="3" name="descripcion"></textarea>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Crear Categoria</button>
                <a  class="btn btn-dark active"  role="button" href="{{route('admin.categorias.index')}}">Cancelar</a>
            </div>
        </form>
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