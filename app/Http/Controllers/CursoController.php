<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Plantilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CursoController extends Controller
{

    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function crearcurso(Plantilla $plantilla)
    {
        return view('cursos.create', compact('plantilla'));
    }

    public function store(Request $request)
    {
        //
    }

    public function store2(Request $request, Plantilla $plantilla)
    {
        $request->validate([
            'name' => 'required',
        ]);

        // Crear el curso localmente como plantilla
        $n_curso = new Curso();
        $n_curso->name = $request->input('name');
        $n_curso->plantilla_id = $plantilla->id;
        $n_curso->save();

        return redirect()->route('plantillas.index')->with('info', 'El curso se creó correctamente como plantilla local.');
    }
    public function show(Curso $curso)
    {
        return view('cursos.show', compact('curso'));
    }

    public function edit(Curso $curso)
    {
        //
    }

    public function update(Request $request, Curso $curso)
    {
        //
    }

    public function destroy(Curso $curso)
    {
        //
    }

    public function destroy2(Curso $curso, Plantilla $plantilla)
    {
        $curso->delete();
        return redirect()->route('curso.crearcurso', $plantilla->id)->with('info', 'El Curso se eliminó correctamente');
    }
}
