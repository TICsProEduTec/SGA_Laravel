<?php

use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\PlantillaController;
use App\Http\Controllers\Admin\InicioController;
use App\Http\Controllers\Admin\UsuarioController;

use App\Models\Inicio;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('admin.index');
});
//Móulo de categorias
Route::resource('categorias', CategoriaController::class)->names('admin.categorias');
Route::get('categorias/{id}/veliminar',[CategoriaController::class, 'veliminar'])->name('admin.categorias.veliminar');
Route::post('categorias/{id}/eliminar',[CategoriaController::class, 'eliminar'])->name('admin.categorias.eliminar');
//Módulo de curso
Route::resource('cursos', CursoController::class)->names('admin.cursos');
//Modulo plantilla
Route::resource('plantillas', PlantillaController::class)->names('admin.plantillas');
Route::get('plantillas/{id}/asignar',[PlantillaController::class, 'asignar'])->name('admin.plantillas.asignar');
Route::post('plantillas/agragarcurso',[PlantillaController::class, 'agregarcurso'])->name('admin.plantillas.agregarcurso');
Route::post('plantillas/eliminarcurso',[PlantillaController::class, 'eliminarcurso'])->name('admin.plantillas.eliminarcurso');
//Módulo de inicio
Route::resource('inicio', InicioController::class)->names('admin.inicios');
//Módulo de usuario
Route::resource('usuarios', UsuarioController::class)->names('admin.usuarios');




/*
Route::get('categorias/{id}/veliminar',[CategoriaController::class, 'veliminar'])->name('admin.categorias.veliminar');
Route::post('categorias/{id}/eliminar',[CategoriaController::class, 'eliminar'])->name('admin.categorias.eliminar');*/