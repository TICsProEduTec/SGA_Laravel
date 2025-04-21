<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Plantilla;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $cursos = Curso::all();
        return view('admin.curso.index', compact('cursos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('admin.curso.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        //validacion
        $request->validate(([
            'name' => 'required',
            'shortname' => 'required|unique:cursos|alpha_dash',
        ]));
        $cursos = Curso::create($request->all());
        return redirect()->route('admin.cursos.index')->with('info', 'El curso se creo correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Curso $curso)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Curso $curso)
    {
        //
        $plantillas = Plantilla::all();
        return view('admin.curso.edit', compact('curso', 'plantillas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Curso $curso)
    {
        //
        $request->validate(([
            'name' => 'required',
            'shortname' => 'required|unique:cursos,shortname,'.$curso->id.'|alpha_dash',
        ]));
        $curso->update($request->all());
        return redirect()->route('admin.cursos.index')->with('info', 'El curso se actualizo correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Curso $curso)
    {
        //
        $curso->delete();
        return redirect()->route('admin.cursos.index')->with('info', 'El curso se elimino correctamente');

    }
}
