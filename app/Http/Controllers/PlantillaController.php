<?php

namespace App\Http\Controllers;

use App\Models\Plantilla;
use Illuminate\Http\Request;

class PlantillaController extends Controller
{
    /**
     * Mostrar todas las plantillas
     */
    public function index()
    {
        $plantillas = Plantilla::withCount('cursos')->get();
        return view('plantillas.index', compact('plantillas'));
    }

    /**
     * Mostrar formulario para crear una nueva plantilla
     */
    public function create()
    {
        return view('plantillas.create');
    }

    /**
     * Guardar una nueva plantilla
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $plantilla = new Plantilla();
        $plantilla->name = $request->name;
        $plantilla->save();

        return redirect()->route('plantillas.index')->with('info', 'La plantilla se creó correctamente.');
    }

    /**
     * Mostrar el formulario de edición de una plantilla
     */
    public function edit(Plantilla $plantilla)
    {
        return view('plantillas.edit', compact('plantilla'));
    }

    /**
     * Actualizar una plantilla existente
     */
    public function update(Request $request, Plantilla $plantilla)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $plantilla->name = $request->name;
        $plantilla->save();

        return redirect()->route('plantillas.index')->with('info', 'La plantilla se actualizó correctamente.');
    }

    /**
     * Eliminar una plantilla
     */
    public function destroy(Plantilla $plantilla)
    {
        $plantilla->delete();

        return redirect()->route('plantillas.index')->with('info', 'La plantilla se eliminó correctamente.');
    }
}
