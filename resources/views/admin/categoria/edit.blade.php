@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Editar Categorias de Moodle</h1>
@stop

@section('content')
@php
    foreach (json_decode($ecategoria) as $cat) {
        # code...
    }
@endphp

<div class="card">
    <div class="card-header">
        <h2>Editar tu categoria</h2>
    </div>
    <div class="card-body">
        <form action="{{route('admin.categorias.update', $cat->id)}}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="scategoria" class="form-label">Nombre de la categoria</label>
                <select class="form-select" aria-label="Default select example" id="scategoria" name="scategoria">
                    @if($cat->parent == 0)
                        <option value="0" selected>Superior</option>
                    @else
                        <option value="0">Superior</option>
                    @endif
                    @foreach (json_decode($categorias) as $item)
                        @if($item->id == $cat->parent)
                            <option value="{{$item->id}}" selected>{{$item->name}}</option>
                        @else
                            <option value="{{$item->id}}">{{$item->name}}</option> 
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Nombre de la categoria</label>
                <input type="text" class="form-control" id="name" name="name" value="{{$cat->name}}">
                @error('name')
                    <span class="text-danger">{{$message}}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="IdNumber" class="form-label">IdNumber</label>
                <input type="text" class="form-control" id="IdNumber" name="idnumber" value="{{$cat->idnumber}}">
                @error('idnumber')
                    <span class="text-danger">{{$message}}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="Descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" rows="3" name="descripcion" >
                {{$cat->description}}
                </textarea>
                
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Guardar Categoria</button>
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